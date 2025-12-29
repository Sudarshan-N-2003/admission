<?php
require_once 'auth.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Endroid\QrCode\Builder\Builder;

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
$stmt = $pdo->prepare(
    "SELECT *
     FROM admissions
     WHERE application_id = :id"
);
$stmt->execute([':id' => $applicationId]);
$d = $stmt->fetch();

if (!$d) {
    die('Application not found');
}

/* ===============================
   DOCUMENT STATUS
================================ */
$status = json_decode($d['document_status'], true) ?? [];

/* ===============================
   MARK AS PRINTED (LOCK CHECKLIST)
================================ */
if (empty($d['printed_at'])) {
    $pdo->prepare(
        "UPDATE admissions SET printed_at = NOW() WHERE application_id = :id"
    )->execute([':id' => $applicationId]);
}

/* ===============================
   GENERATE QR CODE
================================ */
$qrResult = Builder::create()
    ->data("VVIT Application ID: " . $applicationId)
    ->size(120)
    ->margin(5)
    ->build();

$qrBase64 = base64_encode($qrResult->getString());

/* ===============================
   PREPARE HTML (NO PHP INSIDE STRING)
================================ */
$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body { font-family: Arial, sans-serif; font-size: 13px; }
.header { display:flex; justify-content:space-between; align-items:center; }
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
    <b>Application ID:</b> ' . htmlspecialchars($applicationId) . '<br>
    <b>Student Name:</b> ' . htmlspecialchars($d['student_name']) . '
  </div>
  <div class="qr">
    <img src="data:image/png;base64,' . $qrBase64 . '">
  </div>
</div>

<div class="section">
  <b>Gender:</b> ' . htmlspecialchars($d['gender']) . '<br>
  <b>DOB:</b> ' . htmlspecialchars($d['dob']) . '<br>
  <b>Mobile:</b> ' . htmlspecialchars($d['mobile']) . '<br>
  <b>Guardian Mobile:</b> ' . htmlspecialchars($d['guardian_mobile']) . '<br>
  <b>State:</b> ' . htmlspecialchars($d['state']) . '<br>
  <b>Category:</b> ' . htmlspecialchars($d['category']) . '
</div>

<div class="section">
  <b>Admission Through:</b> ' . htmlspecialchars($d['admission_through']) . '<br>
  <b>Branch:</b> ' . htmlspecialchars($d['allotted_branch']) . '
</div>

<div class="section">
  <h4>Document Checklist</h4>
  <table>
    <tr><th>Sl</th><th>Document</th><th>Status</th></tr>
    <tr><td>1</td><td>10th Marks Card</td><td>' . ($status['marks_10'] ?? '') . '</td></tr>
    <tr><td>2</td><td>12th / Diploma Marks Card</td><td>' . ($status['marks_12'] ?? '') . '</td></tr>
    <tr><td>3</td><td>Study Certificate</td><td>' . ($status['study_certificate'] ?? '') . '</td></tr>
    <tr><td>4</td><td>Transfer Certificate</td><td>' . ($status['transfer_certificate'] ?? '') . '</td></tr>
    <tr><td>5</td><td>Photograph</td><td>' . ($status['photo'] ?? '') . '</td></tr>
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
$dompdf->stream('Application_' . $applicationId . '.pdf', ['Attachment' => true]);
exit;
