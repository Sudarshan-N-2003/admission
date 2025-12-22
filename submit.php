<?php
session_start();

require_once __DIR__ . '/functions.php';

try {

    /* ---------------------------------
       1. REQUIRED FIELD VALIDATION
    ----------------------------------*/
    $required = [
        'student_name',
        'dob',
        'gender',
        'father_name',
        'mother_name',
        'mobile',
        'guardian_mobile',
        'email',
        'state',
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

    /* ---------------------------------
       2. NORMALIZE INPUT (UPPERCASE)
    ----------------------------------*/
    $data = [];
    foreach ($_POST as $key => $value) {
        $data[$key] = is_string($value) ? strtoupper(trim($value)) : $value;
    }

    /* ---------------------------------
       3. ADMISSION TYPE VALIDATION
    ----------------------------------*/
    if ($data['admission_through'] === 'KEA') {
        $keaFields = ['cet_number', 'cet_rank', 'seat_allotted', 'allotted_branch'];
        foreach ($keaFields as $f) {
            if (empty($data[$f])) {
                throw new Exception("Missing KEA detail: $f");
            }
        }
    }

    if ($data['admission_through'] === 'MANAGEMENT') {
        if (empty($data['allotted_branch_management'])) {
            throw new Exception("Missing Management branch");
        }
        // unify branch key
        $data['allotted_branch'] = $data['allotted_branch_management'];
    }

    /* ---------------------------------
       4. GENERATE APPLICATION ID
    ----------------------------------*/
    $year = fetch_external_year();              // e.g. 2025
    $serial = next_serial_for_year($year);      // 001
    $application_id = '1VJ' . substr($year, -2) . $serial;

    /* ---------------------------------
       5. RENDER-SAFE DIRECTORIES
    ----------------------------------*/
    $baseTmp = sys_get_temp_dir() . '/admission_app';

    $uploadDir = $baseTmp . '/uploads/' . $application_id;
    $recordDir = $baseTmp . '/records';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    if (!is_dir($recordDir)) {
        mkdir($recordDir, 0777, true);
    }

    /* ---------------------------------
       6. FILE UPLOADS
    ----------------------------------*/
    $maxSize = 2 * 1024 * 1024; // 2MB
    $files = [];

    // 10 + 12 / Equivalent Marks Card
    $files['marks_12'] = validate_and_move(
        $_FILES['marks_12'],
        $uploadDir,
        ['pdf'],
        $maxSize
    );

    // Transfer Certificate
    $files['transfer_certificate'] = validate_and_move(
        $_FILES['transfer_certificate'],
        $uploadDir,
        ['pdf'],
        $maxSize
    );

    // Study Certificate
    $files['study_certificate'] = validate_and_move(
        $_FILES['study_certificate'],
        $uploadDir,
        ['pdf'],
        $maxSize
    );

    // KEA specific document
    if ($data['admission_through'] === 'KEA') {
        if (empty($_FILES['kea_acknowledgement']['name'])) {
            throw new Exception('KEA payment acknowledgement is required');
        }

        $files['kea_acknowledgement'] = validate_and_move(
            $_FILES['kea_acknowledgement'],
            $uploadDir,
            ['pdf'],
            $maxSize
        );
    }

    // Management specific document
    if ($data['admission_through'] === 'MANAGEMENT') {
        if (empty($_FILES['management_receipt']['name'])) {
            throw new Exception('College fees payment receipt is required');
        }

        $files['management_receipt'] = validate_and_move(
            $_FILES['management_receipt'],
            $uploadDir,
            ['pdf'],
            $maxSize
        );
    }

    /* ---------------------------------
       7. SAVE RECORD (JSON – TEMP)
    ----------------------------------*/
    $record = [
        'application_id' => $application_id,
        'submitted_at'   => date('c'),
        'data'           => $data,
        'files'          => $files
    ];

    $jsonPath = $recordDir . '/' . $application_id . '.json';
    file_put_contents($jsonPath, json_encode($record, JSON_PRETTY_PRINT));

    /* ---------------------------------
       8. SUCCESS RESPONSE
    ----------------------------------*/
    $_SESSION['flash'] = "Application submitted successfully. Your ID: $application_id";
    $_SESSION['flash_type'] = 'success';

} catch (Exception $e) {

    $_SESSION['flash'] = 'Error: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'error';
}

header('Location: index.php');
exit;





use Dompdf\Dompdf;

// Build PDF
$pdfHtml = build_application_pdf_html([
    'application_id' => $application_id,
    'data' => $data,
    'files' => $files
]);

$pdfPath = $uploadDir . '/' . $application_id . '.pdf';

$dompdf = new Dompdf();
$dompdf->loadHtml($pdfHtml);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
file_put_contents($pdfPath, $dompdf->output());




use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = getenv('SMTP_HOST');
    $mail->SMTPAuth   = true;
    $mail->Username   = getenv('SMTP_USER');
    $mail->Password   = getenv('SMTP_PASS');
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = getenv('SMTP_PORT') ?: 587;

    $mail->setFrom(getenv('FROM_EMAIL'), getenv('FROM_NAME'));
    $mail->addAddress($data['email'], $data['student_name']);

    $mail->addAttachment($pdfPath, $application_id . '.pdf');

    $mail->isHTML(true);
    $mail->Subject = "VVIT Admission Application – $application_id";

    $mail->Body = "
        <p>Dear <b>{$data['student_name']}</b>,</p>
        <p>Your admission application has been submitted successfully.</p>
        <p><b>Application ID:</b> $application_id</p>
        <p>Please find the attached application PDF.</p>
        <br>
        <p>Regards,<br>VVIT Admissions</p>
    ";

    $mail->send();

} catch (Exception $e) {
    // Email failure should NOT stop submission
    error_log('Mail Error: ' . $mail->ErrorInfo);
}



// store info for success page
$_SESSION['application_id'] = $application_id;
$_SESSION['pdf_path'] = $pdfPath;

// redirect to success page
header("Location: success.php");
exit;