<?php
session_start();

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {

  /* ==================================================
     1. REQUIRED FIELD VALIDATION (ALL REQUIRED)
  ================================================== */
  $required = [
    'student_name','dob','gender',
    'father_name','mother_name',
    'mobile','guardian_mobile','email',
    'state','permanent_address',
    'nationality','religion',
    'prev_college','prev_combination',
    'category','sub_caste',
    'admission_through'
  ];

  foreach ($required as $f) {
    if (empty(trim($_POST[$f] ?? ''))) {
      throw new Exception("Missing required field: $f");
    }
  }

  /* ==================================================
     2. NORMALIZE INPUT (UPPERCASE)
  ================================================== */
  $data = [];
  foreach ($_POST as $k => $v) {
    $data[$k] = is_string($v) ? strtoupper(trim($v)) : $v;
  }

  /* ==================================================
     3. ADMISSION TYPE LOGIC
  ================================================== */
  if ($data['admission_through'] === 'KEA') {

    foreach (['cet_number','cet_rank','seat_allotted','allotted_branch'] as $f) {
      if (empty($data[$f] ?? '')) {
        throw new Exception("Missing KEA field: $f");
      }
    }

  } elseif ($data['admission_through'] === 'MANAGEMENT') {

    if (empty($data['management_branch'] ?? '')) {
      throw new Exception("Missing Management branch");
    }

    // unify branch key
    $data['allotted_branch'] = $data['management_branch'];
    $data['seat_allotted']   = 'MANAGEMENT';
  }



/* ---------------------------------
   3.5 DUPLICATE ENTRY CHECK
----------------------------------*/
require_once __DIR__ . '/db.php';
$pdo = get_db();

$check = $pdo->prepare("
  SELECT 1
  FROM admissions
  WHERE mobile = :mobile
     OR email  = :email
  LIMIT 1
");

$check->execute([
  ':mobile' => $data['mobile'],
  ':email'  => $data['email']
]);

if ($check->fetch()) {
  throw new Exception(
    "Duplicate entry detected. This mobile number or email is already registered."
  );
}




  

  /* ==================================================
     4. GENERATE APPLICATION ID
  ================================================== */
  $year = fetch_external_year();              // 2025
  $serial = next_serial_for_year($year);      // 001
  $application_id = '1VJ' . substr($year, -2) . $serial;

  /* ==================================================
     5. RENDER-SAFE DIRECTORIES
  ================================================== */
  $baseDir = sys_get_temp_dir() . '/admission_app';
  $uploadDir = $baseDir . '/uploads/' . $application_id;

  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
  }

  /* ==================================================
     6. FILE UPLOADS (ALL REQUIRED)
  ================================================== */
  $maxSize = 2 * 1024 * 1024; // 2MB
  $files = [];

  $files['marks_12'] = validate_and_move(
    $_FILES['marks_12'], $uploadDir, ['pdf'], $maxSize
  );

  $files['study_certificate'] = validate_and_move(
    $_FILES['study_certificate'], $uploadDir, ['pdf'], $maxSize
  );

  $files['transfer_certificate'] = validate_and_move(
    $_FILES['transfer_certificate'], $uploadDir, ['pdf'], $maxSize
  );

  $files['photo'] = validate_and_move(
    $_FILES['photo'], $uploadDir, ['jpg','jpeg'], $maxSize
  );

  if ($data['admission_through'] === 'KEA') {

    if (empty($_FILES['kea_acknowledgement']['name'])) {
      throw new Exception('KEA payment acknowledgement is required');
    }

    $files['kea_acknowledgement'] = validate_and_move(
      $_FILES['kea_acknowledgement'], $uploadDir, ['pdf'], $maxSize
    );
  }

  if ($data['admission_through'] === 'MANAGEMENT') {

    if (empty($_FILES['management_receipt']['name'])) {
      throw new Exception('College fees payment receipt is required');
    }

    $files['management_receipt'] = validate_and_move(
      $_FILES['management_receipt'], $uploadDir, ['pdf'], $maxSize
    );
  }

  /* ==================================================
     7. GENERATE PDF
  ================================================== */
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

  /* ==================================================
     8. SEND EMAIL (NON-BLOCKING)
  ================================================== */
  try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = getenv('SMTP_HOST');
    $mail->SMTPAuth = true;
    $mail->Username = getenv('SMTP_USER');
    $mail->Password = getenv('SMTP_PASS');
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = getenv('SMTP_PORT') ?: 587;

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
    error_log('Mail Error: ' . $e->getMessage());
  }

  /* ==================================================
     9. SUCCESS REDIRECT
  ================================================== */
  $_SESSION['application_id'] = $application_id;
  $_SESSION['pdf_path'] = $pdfPath;
  $_SESSION['flash'] = "Application submitted successfully. Your ID: $application_id";
  $_SESSION['flash_type'] = 'success';

  header("Location: success.php");
  exit;

} catch (Exception $e) {

  $_SESSION['flash'] = 'Error: ' . $e->getMessage();
  $_SESSION['flash_type'] = 'error';
  header("Location: index.php");
  exit;
}
