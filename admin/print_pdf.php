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
$admissionYear = '2025-26'; // or calculate dynamically

$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
body {
  font-family: Arial, sans-serif;
  font-size: 12px;
}
.page {
  width: 100%;
}
.top {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
}
.photo {
  width: 120px;
  height: 140px;
  border: 1px solid #000;
}
.idbox {
  font-weight: bold;
  font-size: 14px;
}
.main {
  display: flex;
}
.left {
  width: 60%;
  padding-right: 8px;
}
.right {
  width: 40%;
  padding-left: 8px;
  border-left: 2px solid #000;
}
h3 {
  margin: 6px 0;
}
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 6px;
}
td, th {
  border: 1px solid #000;
  padding: 5px;
}
.center {
  text-align: center;
}
.college-name {
  font-family: "Times New Roman", serif;
  font-size: 16px;
  font-weight: bold;
  text-align: center;
}
</style>
</head>
<body>

<div class="page">

  <!-- TOP ROW -->
  <div class="top">
    <div>
      <img src="' . $qrUrl . '" class="photo">
    </div>
    <div class="idbox">
      Application ID:<br>' . e($applicationId) . '
    </div>
  </div>

  <div class="main">

    <!-- LEFT SIDE : STUDENT COPY -->
    <div class="left">
      <h3>Student Details</h3>

      <p><b>Name:</b> ' . e($d['student_name']) . '</p>
      <p><b>DOB:</b> ' . e($d['dob']) . '</p>
      <p><b>Gender:</b> ' . e($d['gender']) . '</p>
      <p><b>Mobile:</b> ' . e($d['mobile']) . '</p>
      <p><b>Guardian Mobile:</b> ' . e($d['guardian_mobile']) . '</p>
      <p><b>Address:</b> ' . e($d['permanent_address']) . '</p>

      <h3>Admission Details</h3>
      <p><b>Admission Through:</b> ' . e($d['admission_through']) . '</p>
      <p><b>Branch:</b> ' . e($d['allotted_branch']) . '</p>
      <p><b>Quota:</b> ' . e($d['seat_allotted']) . '</p>

      <h3>Submitted Documents</h3>
      <table>
        <tr><th>Sl</th><th>Document</th><th>Status</th></tr>
        <tr><td>1</td><td>10th Marks Card</td><td>' . e($status['marks_10'] ?? '') . '</td></tr>
        <tr><td>2</td><td>12th / Diploma Marks Card</td><td>' . e($status['marks_12'] ?? '') . '</td></tr>
        <tr><td>3</td><td>Study Certificate</td><td>' . e($status['study_certificate'] ?? '') . '</td></tr>
        <tr><td>4</td><td>Transfer Certificate</td><td>' . e($status['transfer_certificate'] ?? '') . '</td></tr>
        <tr><td>5</td><td>Photo</td><td>' . e($status['photo'] ?? '') . '</td></tr>
      </table>
    </div>

    <!-- RIGHT SIDE : COLLEGE COPY -->
    <div class="right">
      <div class="college-name">
        Vijay Vittal Institute of Technology
      </div>

      <p class="center">
        <b>' . e($d['student_name']) . '</b><br>
        Branch: ' . e($d['allotted_branch']) . '<br>
        Admission Year: ' . $admissionYear . '
      </p>

      <table>
        <tr><th>Sl</th><th>Document</th><th>Received Date</th></tr>
        <tr><td>1</td><td>10th Marks Card</td><td></td></tr>
        <tr><td>2</td><td>12th / Diploma Marks Card</td><td></td></tr>
        <tr><td>3</td><td>Study Certificate</td><td></td></tr>
        <tr><td>4</td><td>Transfer Certificate</td><td></td></tr>
        <tr><td>5</td><td>Photo</td><td></td></tr>
      </table>
    </div>

  </div>

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
