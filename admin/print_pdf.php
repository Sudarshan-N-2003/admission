<?php
session_start();

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;

/* ===============================
   VALIDATE INPUT
================================ */
$id = $_GET['id'] ?? '';
if (!$id) {
    die('Invalid Application ID');
}

/* ===============================
   DB FETCH
================================ */
$pdo = get_db();

$stmt = $pdo->prepare("
    SELECT *
    FROM admissions
    WHERE application_id = :id
");
$stmt->execute([':id' => $id]);
$app = $stmt->fetch();

if (!$app) {
    die('Application not found');
}

/* ===============================
   DOCUMENT STATUS
================================ */
$docStatus = json_decode($app['document_status'] ?? '{}', true);

/* ===============================
   QR CODE (NO GD REQUIRED)
================================ */
$qrData = implode(' | ', [
    'APP ID: ' . $app['application_id'],
    'MOBILE: ' . $app['mobile'],
    'BRANCH: ' . ($app['allotted_branch'] ?? ''),
    'TYPE: ' . $app['admission_through']
]);

$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data='
       . urlencode($qrData);

/* ===============================
   HTML TEMPLATE (MATCHES SAMPLE)
================================ */
$html = '
<style>
body { font-family: DejaVu Sans; font-size: 12px; }
h1,h2,h3 { text-align:center; margin:5px 0; }
table { width:100%; border-collapse:collapse; margin-top:10px; }
td, th { border:1px solid #000; padding:6px; }
.section { background:#e9d8fd; font-weight:bold; }
.no-border td { border:none; }
.copy-title { text-align:center; font-weight:bold; margin-top:20px; }
.sign { margin-top:40px; }
</style>

<h2>VIJAYA VITTALA INSTITUTE OF TECHNOLOGY</h2>
<p style="text-align:center">
35/1, Dodda Gubbi Post, Hennur–Bagalur Road,<br>
Thanisandra, Bengaluru, Karnataka – 560077
</p>

<table>
<tr class="section"><td colspan="4">PERSONAL INFORMATION</td></tr>

<tr>
  <td>STUDENT NAME</td>
  <td colspan="3">'.($form['student_name'] ?? '').'</td>
</tr>

<tr>
  <td>GENDER</td>
  <td>'.($form['gender'] ?? '').'</td>
  <td>RELIGION</td>
  <td>'.($form['religion'] ?? '').'</td>
</tr>

<tr>
  <td>CATEGORY</td>
  <td>'.($form['category'] ?? '').'</td>
  <td>SUB CASTE</td>
  <td>'.($form['sub_caste'] ?? '').'</td>
</tr>

<tr>
  <td>DOB</td>
  <td>'.($form['dob'] ?? '').'</td>
  <td>STATE</td>
  <td>'.($form['state'] ?? '').'</td>
</tr>

<tr>
  <td>FATHER / GUARDIAN</td>
  <td colspan="3">'.($form['father_name'] ?? '').'</td>
</tr>

<tr>
  <td>MOTHER NAME</td>
  <td colspan="3">'.($form['mother_name'] ?? '').'</td>
</tr>

<tr>
  <td>EMAIL</td>
  <td>'.($form['email'] ?? '').'</td>
  <td>MOBILE</td>
  <td>'.($form['mobile'] ?? '').'</td>
</tr>

<tr>
  <td>GUARDIAN MOBILE</td>
  <td colspan="3">'.($form['guardian_mobile'] ?? '').'</td>
</tr>

<tr>
  <td>PERMANENT ADDRESS</td>
  <td colspan="3">'.($form['permanent_address'] ?? '').'</td>
</tr>

<tr>
  <td>ADMISSION THROUGH</td>
  <td>'.($form['admission_through'] ?? '').'</td>
  <td>ALLOTTED BRANCH</td>
  <td>'.($form['allotted_branch'] ?? '').'</td>
</tr>

<tr>
  <td>PREVIOUS COMBINATION</td>
  <td colspan="3">'.($form['prev_combination'] ?? '').'</td>
</tr>
</table>
';

/* ===============================
   DOCUMENT TABLE (FUNCTION)
================================ */
function docTable($docStatus) {
    $rows = [
        '10th Marks Card' => $docStatus['marks_10'] ?? '',
        '12th / Diploma Marks Card' => $docStatus['marks_12'] ?? '',
        'Study Certificate' => $docStatus['study_certificate'] ?? '',
        'Transfer Certificate' => $docStatus['transfer_certificate'] ?? '',
        'Photograph' => $docStatus['photo'] ?? '',
    ];

    $html = '<table><tr><th>Sl</th><th>Document</th><th>Status</th></tr>';
    $i = 1;
    foreach ($rows as $name => $status) {
        $html .= "<tr>
            <td>$i</td>
            <td>$name</td>
            <td>".($status ?: '')."</td>
        </tr>";
        $i++;
    }
    $html .= '</table>';

    return $html;
}

/* ===============================
   STUDENT COPY
================================ */
$html .= '
<h3 class="copy-title">ACKNOWLEDGMENT – STUDENT COPY</h3>
<p>
This is to certify that the following documents have been received from
<b>'.$app['student_name'].'</b> for admission to BE in the Branch
<b>'.$app['allotted_branch'].'</b> from the academic year
<b>2025 - 2026</b>.
</p>
'.docTable($docStatus).'
<div class="sign">
<table class="no-border">
<tr>
<td>Student Signature</td>
<td style="text-align:right">Admission Director</td>
</tr>
</table>
</div>
';

/* ===============================
   PAGE BREAK + COLLEGE COPY
================================ */
$html .= '<div style="page-break-before:always"></div>';

$html .= '
<h2>VIJAYA VITTALA INSTITUTE OF TECHNOLOGY</h2>
<h3 class="copy-title">ACKNOWLEDGMENT – COLLEGE COPY</h3>
<p>
This is to certify that the following documents have been received from
<b>'.$app['student_name'].'</b> for admission to BE in the Branch
<b>'.$app['allotted_branch'].'</b> from the academic year
<b>2025 - 2026</b>.
</p>
'.docTable($docStatus).'
<div class="sign">
<table class="no-border">
<tr>
<td>Student Signature</td>
<td style="text-align:right">Admission Director</td>
</tr>
</table>
</div>
';

/* ===============================
   GENERATE PDF
================================ */
$dompdf = new Dompdf(['defaultFont' => 'DejaVu Sans']);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream($app['application_id'].'.pdf', ['Attachment' => true]);

/* ===============================
   LOCK PRINT
================================ */
$pdo->prepare("
    UPDATE admissions
    SET printed_at = NOW()
    WHERE application_id = :id
")->execute([':id' => $id]);
