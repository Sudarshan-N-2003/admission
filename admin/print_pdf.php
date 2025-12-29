<?php
require_once 'auth.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;

/* ===============================
   SAFETY: NO WARNINGS IN PDF
================================ */
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

/* ===============================
   SAFE ESCAPE
================================ */
function e($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

/* ===============================
   DB
================================ */
$pdo = get_db();

/* ===============================
   GET ID
================================ */
$id = $_GET['id'] ?? '';
if ($id === '') die('Invalid Application ID');

/* ===============================
   FETCH DATA
================================ */
$stmt = $pdo->prepare("SELECT * FROM admissions WHERE application_id = :id");
$stmt->execute([':id' => $id]);
$d = $stmt->fetch();
if (!$d) die('Application not found');

/* ===============================
   MARK PRINTED
================================ */
if (empty($d['printed_at'])) {
    $pdo->prepare("UPDATE admissions SET printed_at = NOW() WHERE application_id = :id")
        ->execute([':id' => $id]);
}

/* ===============================
   DOC STATUS
================================ */
$status = json_decode($d['document_status'], true) ?? [];

/* ===============================
   ADMISSION YEAR
================================ */
$y = (int)date('Y', strtotime($d['created_at']));
$admissionYear = $y . ' - ' . ($y + 1);

/* ===============================
   PHOTO
================================ */
$photoHtml = '';
if (!empty($d['photo_path']) && file_exists($d['photo_path'])) {
    $img = base64_encode(file_get_contents($d['photo_path']));
    $photoHtml = "<img src='data:image/jpeg;base64,$img' style='width:100px;height:120px;border:1px solid #000'>";
} else {
    $photoHtml = "<div style='width:100px;height:120px;border:1px solid #000'></div>";
}

/* ===============================
   GENDER CHECKBOX
================================ */
$gM = $d['gender'] === 'M' ? '☑' : '☐';
$gF = $d['gender'] === 'F' ? '☑' : '☐';

/* ===============================
   HTML
================================ */
$html = "
<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8'>
<style>
body{font-family:Arial;font-size:12px}
h2,h3{text-align:center;margin:4px 0}
table{width:100%;border-collapse:collapse}
td,th{border:1px solid #000;padding:6px}
.no-border td{border:none}
.section-title{background:#e9d5ff;font-weight:bold}
.cut{border-top:1px dashed #000;margin:10px 0}
.right{text-align:right}
.center{text-align:center}
.signature{margin-top:40px}
.page-break{page-break-after:always}
</style>
</head>
<body>

<h2>VIJAYA VITTALA INSTITUTE OF TECHNOLOGY</h2>
<p class='center'>
35/1, Dodda Gubbi Post, Hennur-Bagalur Road,<br>
Thanisandra, Bengaluru, Karnataka - 560077
</p>

<table class='no-border'>
<tr>
<td><b>APPLICATION NO:</b> ".e($id)."</td>
<td class='right'><b>DATE & TIME:</b> ".e($d['created_at'])."</td>
<td class='right'>$photoHtml</td>
</tr>
</table>

<table>
<tr><td colspan='4' class='section-title'>PERSONAL INFORMATION</td></tr>
<tr>
<td><b>STUDENT NAME</b></td><td colspan='3'>".e($d['student_name'])."</td>
</tr>
<tr>
<td><b>GENDER</b></td>
<td colspan='3'> $gF Female &nbsp;&nbsp; $gM Male </td>
</tr>
<tr>
<td><b>RELIGION</b></td><td>".e($d['religion'])."</td>
<td><b>CATEGORY</b></td><td>".e($d['category'])."</td>
</tr>
<tr>
<td><b>SUB CASTE</b></td><td colspan='3'>".e($d['sub_caste'])."</td>
</tr>
<tr>
<td><b>DOB</b></td><td>".e($d['dob'])."</td>
<td><b>STATE</b></td><td>".e($d['state'])."</td>
</tr>
<tr>
<td><b>FATHER / GUARDIAN</b></td><td colspan='3'>".e($d['father_name'])."</td>
</tr>
<tr>
<td><b>MOTHER NAME</b></td><td colspan='3'>".e($d['mother_name'])."</td>
</tr>
<tr>
<td><b>EMAIL</b></td><td>".e($d['email'])."</td>
<td><b>MOBILE</b></td><td>".e($d['mobile'])."</td>
</tr>
<tr>
<td><b>GUARDIAN MOBILE</b></td><td colspan='3'>".e($d['guardian_mobile'])."</td>
</tr>
<tr>
<td><b>PERMANENT ADDRESS</b></td><td colspan='3'>".e($d['permanent_address'])."</td>
</tr>
<tr>
<td><b>ADMISSION THROUGH</b></td><td>".e($d['admission_through'])."</td>
<td><b>ALLOTTED BRANCH</b></td><td>".e($d['allotted_branch'])."</td>
</tr>
<tr>
<td><b>PREVIOUS COMBINATION</b></td><td colspan='3'>".e($d['prev_combination'])."</td>
</tr>
</table>

<div class='cut'></div>

<h3>ACKNOWLEDGMENT – STUDENT COPY</h3>
<p>
This is to certify that the following documents have been received from
<b>".e($d['student_name'])."</b> for admission to BE for the academic year
<b>$admissionYear</b>.
</p>

<table>
<tr><th>Sl</th><th>Documents</th></tr>
<tr><td>1</td><td>10th Marks Card</td></tr>
<tr><td>2</td><td>12th / Diploma Marks Card</td></tr>
<tr><td>3</td><td>Study Certificate</td></tr>
<tr><td>4</td><td>Transfer Certificate</td></tr>
<tr><td>5</td><td>Photograph</td></tr>
</table>

<table class='no-border signature'>
<tr>
<td>Student Signature</td>
<td class='right'>Admission Director</td>
</tr>
</table>

<div class='page-break'></div>

<h3>ACKNOWLEDGMENT – COLLEGE COPY</h3>
<p>
Documents received from <b>".e($d['student_name'])."</b>
for BE admission ($admissionYear).
</p>

<table>
<tr><th>Sl</th><th>Documents</th></tr>
<tr><td>1</td><td>10th Marks Card</td></tr>
<tr><td>2</td><td>12th / Diploma Marks Card</td></tr>
<tr><td>3</td><td>Study Certificate</td></tr>
<tr><td>4</td><td>Transfer Certificate</td></tr>
<tr><td>5</td><td>Photograph</td></tr>
</table>

</body>
</html>
";

/* ===============================
   PDF
================================ */
$pdf = new Dompdf();
$pdf->setPaper('A4', 'portrait');
$pdf->loadHtml($html);
$pdf->render();
$pdf->stream("VVIT_Application_$id.pdf", ['Attachment' => true]);
exit;
