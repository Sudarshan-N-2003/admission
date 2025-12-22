<?php
require_once 'auth.php';
require_once '../db.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;

$id = $_GET['id'] ?? '';
if (!$id) die('Invalid Application ID');

$stmt = $pdo->prepare("SELECT * FROM admissions WHERE application_id = :id");
$stmt->execute([':id' => $id]);
$d = $stmt->fetch();

if (!$d) die('Application not found');

$timestamp = date('d-m-Y H:i', strtotime($d['created_at']));
$yearRange = date('Y') . '-' . (date('Y') + 1);

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
.table th,.table td{border:1px solid #000;padding:4px;text-align:left}
.cut{border-top:2px dashed #000;margin:10px 0}
.photo{border:1px solid #000;width:90px;height:110px;text-align:center;font-size:10px}
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
  <div class="col" style="text-align:right"><b>TIMESTAMP:</b> $timestamp</div>
</div>

<div class="box">
<h3>PERSONAL INFORMATION</h3>

<div class="row">
  <div class="col"><span class="label">STUDENT NAME:</span> {$d['student_name']}</div>
  <div class="photo">Passport<br>Size Photo</div>
</div>

<p><b>GENDER:</b> {$d['gender']} &nbsp;&nbsp;
<b>RELIGION:</b> {$d['religion']} &nbsp;&nbsp;
<b>CATEGORY:</b> {$d['category']} &nbsp;&nbsp;
<b>SUB CASTE:</b> {$d['sub_caste']}</p>

<p><b>DATE OF BIRTH:</b> {$d['dob']}</p>
<p><b>FATHER / GUARDIAN NAME:</b> {$d['father_name']}</p>
<p><b>MOTHER / GUARDIAN NAME:</b> {$d['mother_name']}</p>

<p><b>E-MAIL ID:</b> {$d['email']}</p>
<p><b>GUARDIAN MOBILE NUMBER:</b> {$d['guardian_mobile']} &nbsp;&nbsp;
<b>MOBILE:</b> {$d['mobile']}</p>

<p><b>PERMANENT ADDRESS:</b> {$d['permanent_address']}</p>

<p><b>STATE:</b> {$d['state']}</p>

<p>
<b>ADMISSION THROUGH:</b> {$d['admission_through']} &nbsp;&nbsp;
<b>ALLOTTED BRANCH:</b> {$d['allotted_branch']}
</p>

<p><b>PREVIOUS COMBINATION:</b> {$d['prev_combination']}</p>
</div>

<div class="cut"></div>

<h3>ACKNOWLEDGMENT – STUDENT COPY</h3>
<p>
This is to certify that the following documents have been received from
<b>{$d['student_name']}</b>.
Taken admission for BE in the Branch <b>{$d['allotted_branch']}</b>
from the academic year <b>$yearRange</b>
</p>

<table class="table">
<tr><th>Sl #</th><th>DOCUMENTS TO BE SUBMITTED</th><th>STATUS</th><th>SUBMITTED DATE</th></tr>
<tr><td>1</td><td>10TH MARKS CARD</td><td></td><td></td></tr>
<tr><td>2</td><td>12TH / DIPLOMA MARKS CARD</td><td></td><td></td></tr>
<tr><td>3</td><td>STUDY CERTIFICATE</td><td></td><td></td></tr>
<tr><td>4</td><td>TRANSFER CERTIFICATE</td><td></td><td></td></tr>
<tr><td>5</td><td>PHOTOGRAPH</td><td></td><td></td></tr>
</table>

<div class="sign">
<div>STUDENT SIGNATURE</div>
<div>ADMISSION DIRECTOR SIGNATURE</div>
</div>

<div class="cut"></div>

<h3>ACKNOWLEDGMENT – COLLEGE COPY</h3>
<p>(Same document list as above)</p>

</body>
</html>
HTML;

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4','portrait');
$dompdf->render();
$dompdf->stream($id.'_VVIT_Application.pdf',['Attachment'=>true]);
exit;