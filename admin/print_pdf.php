<?php
require_once 'auth.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;

/* ===============================
   STOP WARNINGS BREAKING PDF
================================ */
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

/* ===============================
   SAFE HTML ESCAPE
================================ */
function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* ===============================
   DB CONNECTION
================================ */
$pdo = get_db();

/* ===============================
   GET APPLICATION ID
================================ */
$applicationId = $_GET['id'] ?? '';
if ($applicationId === '') {
    die('Invalid Application ID');
}

/* ===============================
   FETCH APPLICATION
================================ */
$stmt = $pdo->prepare("SELECT * FROM admissions WHERE application_id = :id");
$stmt->execute([':id' => $applicationId]);
$d = $stmt->fetch();

if (!$d) {
    die('Application not found');
}

/* ===============================
   MARK AS PRINTED
================================ */
if (empty($d['printed_at'])) {
    $pdo->prepare(
        "UPDATE admissions SET printed_at = NOW() WHERE application_id = :id"
    )->execute([':id' => $applicationId]);
}

/* ===============================
   DOCUMENT STATUS
================================ */
$status = json_decode($d['document_status'], true) ?? [];

/* ===============================
   QR CODE (NO GD REQUIRED)
================================ */
$qrText = urlencode("VVIT Application ID: " . $applicationId);
$qrUrl  = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={$qrText}";

/* ===============================
   BUILD HTML (SAFE)
================================ */
$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
body { font-family: Arial, sans-serif; font-size: 13px; }
.header { display:flex; justify-content:space-between; }
.qr img { width:90px; }
.section { margin-top:10px; }
table { width:100%; border-collapse:collapse; margin-top:10px; }
th, td { border:1px solid #000; padding:6px; }
th { background:#f3f4f6; }
.center { text-align:center; }
</style>
</head>
<body>

<div class="header">
  <div>
    <b>Application ID:</b> '.e($applicationId).'<br>
    <b>Student Name:</b> '.e($d['student_name']).'
  </div>
  <div class="qr">
    <img src="'.$qrUrl.'">
  </div>
</div>

<div class="section">
  <b>Gender:</b> '.e($d['gender']).'<br>
  <b>DOB:</b> '.e($d['dob']).'<br>
  <b>Mobile:</b> '.e($d['mobile']).'<br>
  <b>Guardian Mobile:</b> '.e($d['guardian_mobile']).'<br>
  <b>Email:</b> '.e($d['email']).'<br>
  <b>State:</b> '.e($d['state']).'<br>
  <b>Category:</b> '.e($d['category']).'<br>
  <b>Sub Caste:</b> '.e($d['sub_caste']).'
</div>

<div class="section">
  <b>Admission Through:</b> '.e($d['admission_through']).'<br>
  <b>Allotted Branch:</b> '.e($d['allotted_branch']).'<br>
  <b>Quota:</b> '.e($d['seat_allotted']).'
</div>

<div class="section">
  <h4>Document Checklist</h4>
  <table>
    <tr><th>Sl</th><th>Document</th><th>Status</th></tr>
    <tr><td>1</td><td>10th Marks Card</td><td>'.e($status['marks_10'] ?? '').'</td></tr>
    <tr><td>2</td><td>12th / Diploma Marks Card</td><td>'.e($status['marks_12'] ?? '').'</td></tr>
    <tr><td>3</td><td>Study Certificate</td><td>'.e($status['study_certificate'] ?? '').'</td></tr>
    <tr><td>4</td><td>Transfer Certificate</td><td>'.e($status['transfer_certificate'] ?? '').'</td></tr>
    <tr><td>5</td><td>Photograph</td><td>'.e($status['photo'] ?? '').'</td></tr>
  </table>
</div>

<div class="section center">
  <h3>Vijay Vittal Institute of Technology</h3>
</div>

</body>
</html>
';

/* ===============================
   GENERATE PDF
================================ */
$dompdf = new Dompdf();
$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml($html);
$dompdf->render();
$dompdf->stream(
    'Application_'.$applicationId.'.pdf',
    ['Attachment' => true]
);
exit;
