<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/vendor/autoload.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

try {
    // Basic required fields
    $required = [
        'student_name','dob','father_name','mother_name',
        'mobile','email','prev_combination',
        'permanent_address','category','admission_through'
    ];

    foreach ($required as $r) {
        if (empty($_POST[$r])) {
            throw new Exception("Missing required field: $r");
        }
    }

    // Normalize input (uppercase)
    $data = [];
    foreach ($_POST as $k => $v) {
        $data[$k] = is_string($v) ? mb_strtoupper(trim($v)) : $v;
    }

    // KEA vs MANAGEMENT validation
    if ($data['admission_through'] === 'KEA') {
        if (empty($data['cet_number']) || empty($data['cet_rank']) || empty($data['seat_allotted']) || empty($data['allotted_branch'])) {
            throw new Exception('All KEA details are required');
        }
    }

    if ($data['admission_through'] === 'MANAGEMENT') {
        if (empty($data['allotted_branch_management'])) {
            throw new Exception('Branch is required for management admission');
        }
        // unify branch field
        $data['allotted_branch'] = $data['allotted_branch_management'];
    }

    // Upload directory
    $uploadDir = __DIR__ . '/uploads/' . time();
    mkdir($uploadDir, 0755, true);

    $max = 1 * 1024 * 1024;

    // Files
    $photoPath = validate_and_move($_FILES['photo'], $uploadDir, ['jpg','jpeg'], $max);
    $marksPath = validate_and_move($_FILES['marks'], $uploadDir, ['pdf'], $max);

    $aadhaarFront = !empty($_FILES['aadhaar_front']['name'])
        ? validate_and_move($_FILES['aadhaar_front'], $uploadDir, ['jpg','jpeg','pdf'], $max)
        : null;

    $aadhaarBack = !empty($_FILES['aadhaar_back']['name'])
        ? validate_and_move($_FILES['aadhaar_back'], $uploadDir, ['jpg','jpeg','pdf'], $max)
        : null;

    // Caste & income only if applicable
    $casteIncomePath = null;
    if (!str_contains($data['category'], 'NOT APPLICABLE')) {
        if (empty($_FILES['caste_income']['name'])) {
            throw new Exception('Caste & Income document required');
        }
        $casteIncomePath = validate_and_move($_FILES['caste_income'], $uploadDir, ['pdf'], $max);
    }

    // Generate Application ID
    $year = fetch_external_year();
    $serial = next_serial_for_year($year);
    $generated_id = '1VJ' . substr($year, -2) . $serial;

    // Record
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

    // Save locally (DB save happens in functions.php later)
    $dir = __DIR__ . '/submissions';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents("$dir/$generated_id.json", json_encode($record, JSON_PRETTY_PRINT));

    // PDF generation
    $pdfHtml = build_application_html($record);
    $pdfPath = "$uploadDir/{$generated_id}.pdf";
    create_pdf_from_html($pdfHtml, $pdfPath);

    // Email
    send_email_with_attachment(
        $data['email'],
        $data['student_name'],
        $generated_id,
        $pdfPath,
        $data['mobile']
    );

    $_SESSION['flash'] = "Application submitted successfully. ID: $generated_id";
    $_SESSION['flash_type'] = 'success';
    header('Location: index.php');
    exit;

} catch (Exception $e) {
    $_SESSION['flash'] = 'Error: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'error';
    header('Location: index.php');
    exit;
}
