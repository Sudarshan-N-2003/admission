<?php
// submit.php - handles submission, file validation, PDF gen, email send, ID gen
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/vendor/autoload.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['final_submit'])) {
    http_response_code(405);
    exit('Method not allowed');
}

try {
    // validate required fields
    $required = ['student_name','dob','father_name','mother_name','mobile','email','prev_college','prev_combination','permanent_address','category','admission_through'];
    foreach ($required as $r) {
        if (empty(trim($_POST[$r] ?? ''))) throw new Exception('Please fill all required fields: ' . $r);
    }

    // server-side uppercase normalization
    $data = array_map(function($v){ return is_string($v) ? mb_strtoupper(trim($v)) : $v; }, $_POST);

    // file uploads validation and save to temp upload dir
    $uploadDir = __DIR__ . '/uploads/' . time();
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    // constraints
    $max1 = 1 * 1024 * 1024; // 1MB

    // photo
    if (empty($_FILES['photo']['name'])) throw new Exception('Passport photo required');
    $photoPath = validate_and_move($_FILES['photo'], $uploadDir, ['jpg','jpeg'], $max1);

    // marks
    if (empty($_FILES['marks']['name'])) throw new Exception('Marks PDF required');
    $marksPath = validate_and_move($_FILES['marks'], $uploadDir, ['pdf'], $max1);

    // aadhaar optional
    $aadhaarFront = $_FILES['aadhaar_front']['name'] ? validate_and_move($_FILES['aadhaar_front'], $uploadDir, ['jpg','jpeg','pdf'], $max1) : null;
    $aadhaarBack  = $_FILES['aadhaar_back']['name'] ? validate_and_move($_FILES['aadhaar_back'], $uploadDir, ['jpg','jpeg','pdf'], $max1) : null;

    // caste_income depending on category
    if (!str_contains($data['category'], 'NOT APPLICABLE')) {
        if (empty($_FILES['caste_income']['name'])) throw new Exception('Caste & Income PDF required for selected category');
        $casteIncomePath = validate_and_move($_FILES['caste_income'], $uploadDir, ['pdf'], $max1);
    } else {
        $casteIncomePath = null;
    }

    // generate ID
    $college_code = '1VJ';
    $year = fetch_external_year(); // full year
    $yy = substr((string)$year,-2);
    $serial = next_serial_for_year($year);
    $generated_id = sprintf('%s%s%s', $college_code, $yy, $serial);

    // persist record (JSON) - replace with DB in production
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
        'created_at' => date('c')
    ];

    $subdir = __DIR__ . '/submissions';
    if (!is_dir($subdir)) mkdir($subdir, 0755, true);
    file_put_contents($subdir . '/' . $generated_id . '.json', json_encode($record, JSON_PRETTY_PRINT));

    // generate PDF using DOMPDF
    $html = build_application_html($record);
    $pdfPath = $uploadDir . '/' . $generated_id . '_application.pdf';
    create_pdf_from_html($html, $pdfPath);

    // send email with attachment (PHPMailer)
    $emailSent = send_email_with_attachment($data['email'], $data['student_name'], $generated_id, $pdfPath, $data['mobile']);

    // update JSON with email log
    $record['email_sent'] = $emailSent ? ['to'=>$data['email'],'time'=>date('c')] : null;
    file_put_contents($subdir . '/' . $generated_id . '.json', json_encode($record, JSON_PRETTY_PRINT));

    $_SESSION['flash'] = 'Application submitted successfully. Your ID: ' . $generated_id;
    $_SESSION['flash_type'] = 'success';
    // Provide a download link to the PDF (served from the uploads location)
    $_SESSION['pdf_path'] = str_replace(__DIR__, '', $pdfPath);

    header('Location: index.php');
    exit;

} catch (Exception $e) {
    $_SESSION['flash'] = 'Error: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'error';
    header('Location: index.php');
    exit;
}
