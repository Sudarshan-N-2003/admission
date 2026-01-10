<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;

/* ============================
   1. FETCH APPLICATION
============================ */
$pdo = get_db();

$id = $_GET['id'] ?? '';
if ($id === '') {
    die('Invalid Application ID');
}

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

/* ============================
   2. DECODE STORED JSON
============================ */
$form = json_decode($app['data'], true) ?? [];
$docs = json_decode($app['document_status'], true) ?? [];

/* ============================
   3. PHOTO (BASE64)
============================ */
$photoHtml = '';
if (!empty($app['photo_path']) && file_exists($app['photo_path'])) {
    $img = base64_encode(file_get_contents($app['photo_path']));
    $photoHtml = "<img src='data:image/jpeg;base64,$img' width='90'>";
}

/* ============================
   4. QR CODE (SVG – NO GD)
============================ */
$qrText = implode("\n", [
    "Application ID: {$app['application_id']}",
    "Mobile: " . ($form['mobile'] ?? ''),
    "Branch: " . ($form['allotted_branch'] ?? ''),
    "Admission: " . ($form['admission_through'] ?? '')
]);

$qrSvg = Builder::create()
    ->writer(new SvgWriter())
    ->data($qrText)
    ->size(110)
    ->margin(0)
    ->build()
    ->getString();

/* ============================
   5. COMMON HTML HEADER
============================ */
function headerBlock($photoHtml, $qrSvg, $app) {
    return "
    <table width='100%'>
      <tr>
        <td align='center' colspan='3'>
          <b>VIJAYA VITTALA INSTITUTE OF TECHNOLOGY</b><br>
          <small>
            35/1, Dodda Gubbi Post, Hennur-Bagalur Road,<br>
            Thanisandra, Bengaluru, Karnataka – 560077
          </small>
        </td>
      </tr>
      <tr>
        <td>
          <b>APPLICATION NO:</b> {$app['application_id']}
        </td>
        <td align='center'>
          <b>DATE & TIME:</b> {$app['created_at']}
        </td>
        <td align='right'>
          $photoHtml<br>
          $qrSvg
        </td>
      </tr>
    </table>
    <hr>
    ";
}

/* ============================
   6. PERSONAL INFO TABLE
============================ */
function personalInfoTable($form) {
return "
<table>
<tr class='section'><td colspan='4'>PERSONAL INFORMATION</td></tr>

<tr><td>STUDENT NAME</td><td colspan='3'>{$form['student_name']}</td></tr>
<tr><td>GENDER</td><td>{$form['gender']}</td><td>RELIGION</td><td>{$form['religion']}</td></tr>
<tr><td>CATEGORY</td><td>{$form['category']}</td><td>SUB CASTE</td><td>{$form['sub_caste']}</td></tr>
<tr><td>DOB</td><td>{$form['dob']}</td><td>STATE</td><td>{$form['state']}</td></tr>
<tr><td>FATHER / GUARDIAN</td><td colspan='3'>{$form['father_name']}</td></tr>
<tr><td>MOTHER NAME</td><td colspan='3'>{$form['mother_name']}</td></tr>
<tr><td>EMAIL</td><td>{$form['email']}</td><td>MOBILE</td><td>{$form['mobile']}</td></tr>
<tr><td>GUARDIAN MOBILE</td><td colspan='3'>{$form['guardian_mobile']}</td></tr>
<tr><td>PERMANENT ADDRESS</td><td colspan='3'>{$form['permanent_address']}</td></tr>
<tr><td>ADMISSION THROUGH</td><td>{$form['admission_through']}</td>
<td>ALLOTTED BRANCH</td><td>{$form['allotted_branch']}</td></tr>
<tr><td>PREVIOUS COMBINATION</td><td colspan='3'>{$form['prev_combination']}</td></tr>
</table>
";
}

/* ============================
   7. DOCUMENT TABLE
============================ */
function docTable($docs) {
return "
<table>
<tr><th>Sl</th><th>Document</th><th>Status</th></tr>
<tr><td>1</td><td>10th Marks Card</td><td>".($docs['marks_10'] ?? '')."</td></tr>
<tr><td>2</td><td>12th / Diploma Marks Card</td><td>".($docs['marks_12'] ?? '')."</td></tr>
<tr><td>3</td><td>Study Certificate</td><td>".($docs['study_certificate'] ?? '')."</td></tr>
<tr><td>4</td><td>Transfer Certificate</td><td>".($docs['transfer_certificate'] ?? '')."</td></tr>
<tr><td>5</td><td>Photograph</td><td>".($docs['photo'] ?? '')."</td></tr>
</table>
";
}

/* ============================
   8. BUILD FULL HTML
============================ */
$html = "
<style>
body { font-family: Arial; font-size: 12px; }
table { width:100%; border-collapse:collapse; margin-top:8px; }
td, th { border:1px solid #000; padding:4px; }
.section { background:#e6e6fa; font-weight:bold; }
.center { text-align:center; }
</style>

".headerBlock($photoHtml,$qrSvg,$app)."

".personalInfoTable($form)."

<h4 class='center'>ACKNOWLEDGMENT – STUDENT COPY</h4>
<p>
This is to certify that the following documents have been received from
<b>{$form['student_name']}</b> for admission to BE in the branch
<b>{$form['allotted_branch']}</b> for the academic year <b>2025-2026</b>.
</p>

".docTable($docs)."

<br><br>
<table width='100%' style='border:none'>
<tr>
<td style='border:none'>Student Signature</td>
<td style='border:none' align='right'>Admission Director</td>
</tr>
</table>

<pagebreak>

<h4 class='center'>ACKNOWLEDGMENT – COLLEGE COPY</h4>

<p>
This is to certify that the following documents have been received from
<b>{$form['student_name']}</b> for admission to BE in the branch
<b>{$form['allotted_branch']}</b> for the academic year <b>2025-2026</b>.
</p>

".docTable($docs)."

<br><br>
<table width='100%' style='border:none'>
<tr>
<td style='border:none'>Student Signature</td>
<td style='border:none' align='right'>Admission Director</td>
</tr>
</table>
";

/* ============================
   9. GENERATE PDF
============================ */
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4','portrait');
$dompdf->render();

$dompdf->stream(
    "VVIT_Application_{$app['application_id']}.pdf",
    ['Attachment' => true]
);
