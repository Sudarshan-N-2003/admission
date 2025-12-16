<?php
session_start();

require_once __DIR__ . '/functions.php';

try {

    // -----------------------------
    // 1. REQUIRED FIELD VALIDATION
    // -----------------------------
    $required = [
        'student_name',
        'dob',
        'father_name',
        'mother_name',
        'mobile',
        'email',
        'prev_combination',
        'prev_college',
        'permanent_address',
        'category',
        'admission_through'
    ];

    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Normalize input
    $data = [];
    foreach ($_POST as $k => $v) {
        $data[$k] = is_string($v) ? strtoupper(trim($v)) : $v;
    }

    // -----------------------------
    // 2. GENERATE APPLICATION ID
    // -----------------------------
    $year = fetch_external_year();
    $serial = next_serial_for_year($year);
    $application_id = '1VJ' . substr($year, -2) . $serial;

    // -----------------------------
    // 3. RENDER-SAFE DIRECTORIES
    // -----------------------------
    $baseTmp = sys_get_temp_dir() . '/admission_app';

    $uploadDir = $baseTmp . '/uploads/' . $application_id;
    $recordDir = $baseTmp . '/records';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    if (!is_dir($recordDir)) {
        mkdir($recordDir, 0777, true);
    }

    // -----------------------------
    // 4. FILE UPLOADS
    // -----------------------------
    $maxSize = 2 * 1024 * 1024; // 2MB

    $files = [];

    $files['photo'] = validate_and_move(
        $_FILES['photo'],
        $uploadDir,
        ['jpg', 'jpeg'],
        $maxSize
    );

    $files['marks'] = validate_and_move(
        $_FILES['marks'],
        $uploadDir,
        ['pdf'],
        $maxSize
    );

    if (!empty($_FILES['aadhaar_front']['name'])) {
        $files['aadhaar_front'] = validate_and_move(
            $_FILES['aadhaar_front'],
            $uploadDir,
            ['jpg', 'jpeg', 'pdf'],
            $maxSize
        );
    }

    if (!empty($_FILES['aadhaar_back']['name'])) {
        $files['aadhaar_back'] = validate_and_move(
            $_FILES['aadhaar_back'],
            $uploadDir,
            ['jpg', 'jpeg', 'pdf'],
            $maxSize
        );
    }

    if ($data['category'] !== 'NOT APPLICABLE' && !empty($_FILES['caste_income']['name'])) {
        $files['caste_income'] = validate_and_move(
            $_FILES['caste_income'],
            $uploadDir,
            ['pdf'],
            $maxSize
        );
    }

    // -----------------------------
    // 5. SAVE RECORD (JSON)
    // -----------------------------
    $record = [
        'application_id' => $application_id,
        'submitted_at'   => date('c'),
        'data'           => $data,
        'files'          => $files
    ];

    $jsonPath = $recordDir . '/' . $application_id . '.json';
    file_put_contents($jsonPath, json_encode($record, JSON_PRETTY_PRINT));

    // -----------------------------
    // 6. (OPTIONAL) PDF GENERATION
    // -----------------------------
    // Uncomment when dompdf is ready
    /*
    $html = build_application_html([
        'id'    => $application_id,
        'data'  => $data,
        'files' => $files
    ]);

    $pdfPath = $uploadDir . '/' . $application_id . '.pdf';
    create_pdf_from_html($html, $pdfPath);
    */

    // -----------------------------
    // 7. SUCCESS RESPONSE
    // -----------------------------
    $_SESSION['flash'] = "Application submitted successfully. Your ID: $application_id";
    $_SESSION['flash_type'] = 'success';

} catch (Exception $e) {

    $_SESSION['flash'] = 'Error: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'error';
}

header('Location: index.php');
exit;
