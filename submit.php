<?php
// submit.php (DB-enabled)
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/vendor/autoload.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['final_submit'])) {
    http_response_code(405); exit('Method not allowed');
}

try {
    $required = ['student_name','dob','father_name','mother_name','mobile','email','prev_college','prev_combination','permanent_address','category','admission_through'];
    foreach ($required as $r) if (empty(trim($_POST[$r] ?? ''))) throw new Exception('Please fill all required fields: ' . $r);

    // normalize
    $data = array_map(function($v){ return is_string($v) ? mb_strtoupper(trim($v)) : $v; }, $_POST);

    // upload dir
    $uploadDir = __DIR__ . '/uploads/' . time();
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $max1 = 1 * 1024 * 1024;

    $photoPath = validate_and_move($_FILES['photo'], $uploadDir, ['jpg','jpeg'], $max1);
    $marksPath = validate_and_move($_FILES['marks'], $uploadDir, ['pdf'], $max1);
    $aadhaarFront = $_FILES['aadhaar_front']['name'] ? validate_and_move($_FILES['aadhaar_front'], $uploadDir, ['jpg','jpeg','pdf'], $max1) : null;
    $aadhaarBack  = $_FILES['aadhaar_back']['name'] ? validate_and_move($_FILES['aadhaar_back'], $uploadDir, ['jpg','jpeg','pdf'], $max1) : null;

    if (!str_contains($data['category'], 'NOT APPLICABLE')) {
        if (empty($_FILES['caste_income']['name'])) throw new Exception('Caste & Income PDF required for selected category');
        $casteIncomePath = validate_and_move($_FILES['caste_income'], $uploadDir, ['pdf'], $max1);
    } else {
        $casteIncomePath = null;
    }

    // generate ID using DB-safe serial
    $college_code = '1VJ';
    $year = fetch_external_year();
    $yy = substr((string)$year, -2);
    $serial = next_serial_for_year_db($year); // DB atomic
    $generated_id = sprintf('%s%s%s', $college_code, $yy, $serial);

    // prepare record
    $record = [
        'id' => $generated_id,
        'data' => $data,
        'files' => [
            'photo' => $photoPath,
            'marks' => $marksPath,
            'aadhaar_front' => $aadhaarFront,
            'aadhaar_back' => $aadhaarBack,
            'caste_income' => $casteIncomePath
        ],
        'submitted_documents' => new stdClass(),
        'created_at' => date('Y-m-d H:i:s'),
        'email_sent' => 0
    ];

    // save to DB
    save_submission_db($record);

    // generate PDF
    $html = build_application_html($record);
    $pdfPath = $uploadDir . '/' . $generated_id . '_application.pdf';
    create_pdf_from_html($html, $pdfPath);

    // update DB with files (include PDF path)
    $record['files']['application_pdf'] = $pdfPath;
    update_submission_files_db($generated_id, $record['files']);

    // send email with attachment
    $emailSent = send_email_with_attachment($data['email'], $data['student_name'], $generated_id, $pdfPath, $data['mobile']);
    if ($emailSent) mark_email_sent_db($generated_id);

    $_SESSION['flash'] = 'Application submitted successfully. Your ID: ' . $generated_id;
    $_SESSION['flash_type'] = 'success';
    header('Location: index.php');
    exit;

} catch (Exception $e) {
    error_log('Submit error: '.$e->getMessage());
    $_SESSION['flash'] = 'Error: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'error';
    header('Location: index.php');
    exit;
}
