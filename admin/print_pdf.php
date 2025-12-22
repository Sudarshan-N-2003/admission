<?php
/**
 * admin/print_pdf.php
 * Prints VVIT Admission Application using Application ID
 */

require_once 'auth.php';
require_once '../db.php';
require_once '../vendor/autoload.php';
require_once '../r2.php';

use Dompdf\Dompdf;

/* ===============================
   1. GET & VALIDATE APPLICATION ID
================================ */

$applicationId = $_GET['id'] ?? '';
if ($applicationId === '') {
    die('Invalid Application ID');
}

/* ===============================
   2. FETCH STUDENT DATA FROM DB
================================ */

$stmt = $pdo->prepare(
    "SELECT * FROM admissions WHERE application_id = :id"
);
$stmt->execute([':id' => $applicationId]);
$d = $stmt->fetch();
$status = json_decode($d['document_status'], true) ?? [];

if (!$d) {
    die('Application not found');
}

/* ===============================
   3. PREPARE DATE / YEAR
================================ */

$timestamp = date('d-m-Y H:i', strtotime($d['created_at']));
$academicYear = date('Y') . '-' . (date('Y') + 1);

/* ===============================
   4. FETCH PHOTO & SIGNATURE FROM R2
================================ */

$r2Files = json_decode($d['r2_files'], true) ?? [];

$photoBase64 = '';
$signBase64  = '';

if (!empty($r2Files['passport_photo'])) {
    $tmpPhoto = download_from_r2($r2Files['passport_photo']);
    $photoBase64 = base64_encode(file_get_contents($tmpPhoto));
}

if (!empty($r2Files['student_signature'])) {
    $tmpSign = download_from_r2($r2Files['student_signature']);
    $signBase64 = base64_encode(file_get_contents($tmpSign));
}

/* ===============================
   5. ADMISSION DETAILS BLOCK
================================ */

if ($d['admission_through'] === 'KEA') {
    $admissionBlock = "
        <p><b>Admission Through:</b> KEA</p>
        <p><b>CET Number:</b> {$d['cet_number']}</p>
        <p><b>CET Rank:</b> {$d['cet_rank']}</p>
        <p><b>Quota:</b> {$d['seat_allotted']}</p>
        <p><b>Allotted Branch:</b> {$d['allotted_branch']}</p>
    ";
} else {
    $admissionBlock = "
        <p><b>Admission Through:</b> MANAGEMENT</p>
        <p><b>Allotted Branch:</b> {$d['allotted_branch']}</p>
    ";
}

/* ===============================
   6. BUILD PDF HTML (VVIT FORMAT)
================================ */

$html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body{font-family:Arial;font-size:11px}
h2,h3{text-align:center;margin:4px 0}
.box{border:1px solid #000;padding:6px;margin-bottom:6px}
.row{display:flex}
.col{flex:1;padding:4px}
.label{font-weight:bold}
.table{width:100%;border-collapse:collapse}
.table th,.table td{border:1px solid #000;padding:4px}
.cut{border-top:2px dashed #000;margin:10px 0}
.photo{border:1px solid #000;width:90px;height:110px}
.sign{margin-top:20px;display:flex;justify-content:space-between}
</style>
</head>

<body>

<h2>VIJAYA VITTALA INSTITUTE OF TECHNOLOGY</h2>
<p style="text-align:center">
35/1, Dodda Gubbi Post, Hennur-Bagalur Road, Thanisandra, Bengaluru – 560077
</p>

<div class="row">
  <div class="col"><b>APPLICATION NO:</b> {$d['application_id']}</div>
  <div class="col" style="text-align:right"><b>DATE & TIME:</b> $timestamp</div>
</div>

<div class="box">
<h3>PERSONAL INFORMATION</h3>

<div class="row">
  <div class="col">
    <p><b>Student Name:</b> {$d['student_name']}</p>
    <p><b>DOB:</b> {$d['dob']}</p>
    <p><b>Gender:</b> {$d['gender']}</p>
    <p><b>Category:</b> {$d['category']}</p>
    <p><b>Sub Caste:</b> {$d['sub_caste']}</p>
  </div>
  <div class="photo">
    <img src="data:image/jpeg;base64,$photoBase64"
         style="width:90px;height:110px">
  </div>
</div>

<p><b>Father / Guardian Name:</b> {$d['father_name']}</p>
<p><b>Mother Name:</b> {$d['mother_name']}</p>

<p><b>Mobile:</b> {$d['mobile']} &nbsp;&nbsp;
<b>Guardian Mobile:</b> {$d['guardian_mobile']}</p>

<p><b>Email:</b> {$d['email']}</p>
<p><b>Address:</b> {$d['permanent_address']}</p>
<p><b>State:</b> {$d['state']}</p>

$admissionBlock

<p><b>Previous Combination:</b> {$d['prev_combination']}</p>
<p><b>Previous College:</b> {$d['prev_college']}</p>

</div>

<div class="cut"></div>

<h3>ACKNOWLEDGMENT – STUDENT COPY</h3>
<p>
This is to certify that the student <b>{$d['student_name']}</b> has taken admission
to <b>{$d['allotted_branch']}</b> for the academic year <b>$academicYear</b>.
</p>

<table class="table">
<tr><th>Sl.</th><th>Document</th><th>Status</th><th>Date</th></tr>
<tr><td>1</td><td>10th Marks Card</td><td><?= $status['marks_10'] ?? '' ?></td><td></td></tr>
<tr><td>2</td><td>12th / Diploma Marks Card</td><td><?= $status['marks_12'] ?? '' ?></td><td></td></tr>
<tr><td>3</td><td>Study Certificate</td><td><?= $status['study_certificate'] ?? '' ?></td><td></td></tr>
<tr><td>4</td><td>Transfer Certificate</td><td><?= $status['transfer_certificate'] ?? '' ?></td><td></td></tr>
<tr><td>5</td><td>Photograph</td><td><?= $status['photo'] ?? '' ?></td><td></td></tr>
</table>

<div class="sign">
  <div>
    Student Signature<br>
    <img src="data:image/png;base64,$signBase64" style="width:120px;height:50px">
  </div>
  <div>Admission Officer Signature</div>
</div>

<div class="cut"></div>

<h3>ACKNOWLEDGMENT – COLLEGE COPY</h3>
<p>(Same document list as above)</p>

</body>
</html>
HTML;

/* ===============================
   7. GENERATE & DOWNLOAD PDF
================================ */

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4','portrait');
$dompdf->render();

$dompdf->stream(
// mark as printed (only once)
if ($d['printed_at'] === null) {
    $pdo->prepare(
        "UPDATE admissions SET printed_at = NOW() WHERE application_id = :id"
    )->execute([':id' => $applicationId]);
}
);
exit;