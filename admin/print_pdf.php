<?php
ob_start();

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;

/* =====================================================
   1. DB + APPLICATION FETCH
===================================================== */
$pdo = get_db();

$id = $_GET['id'] ?? '';
if ($id === '') {
    die('Invalid Application ID');
}

$stmt = $pdo->prepare("SELECT * FROM admissions WHERE application_id = :id");
$stmt->execute([':id' => $id]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) {
    die('Application not found');
}

/* =====================================================
   2. FORM DATA (DIRECT COLUMNS – FIXED)
===================================================== */
$form = $app; // IMPORTANT: data is stored as columns, NOT JSON
$docs = json_decode($app['document_status'] ?? '{}', true);

/* =====================================================
   3. PHOTO
===================================================== */
$photoHtml = '';
if (!empty($app['photo_path']) && file_exists($app['photo_path'])) {
    $img = base64_encode(file_get_contents($app['photo_path']));
    $photoHtml = "<img src='data:image/jpeg;base64,$img' width='90' height='120'>";
}

/* =====================================================
   4. QR CODE (SVG – NO GD REQUIRED)
===================================================== */
$qrText = implode("\n", [
    "Application ID: {$app['application_id']}",
    "Mobile: {$form['mobile']}",
    "Branch: {$form['allotted_branch']}",
    "Admission: {$form['admission_through']}"
]);

$qrSvg = Builder::create()
    ->writer(new SvgWriter())
    ->data($qrText)
    ->size(110)
    ->margin(0)
    ->build()
    ->getString();

/* =====================================================
   5. COMMON HEADER BLOCK
===================================================== */
function headerBlock($photoHtml, $qrSvg, $app) {
    return "
    <table width='100%' style='border:none'>
      <tr>
        <td colspan='3' align='center'>
          <b style='font-size:16px'>VIJAYA VITTALA INSTITUTE OF TECHNOLOGY</b><br>
          <span style='font-size:11px'>
            35/1, Dodda Gubbi Post, Hennur–Bagalur Road,<br>
            Thanisandra, Bengaluru – 560077
          </span>
        </td>
      </tr>
      <tr>
        <td style='font-size:11px'>
          <b>APPLICATION NO:</b> {$app['application_id']}
        </td>
        <td align='center' style='font-size:11px'>
          <b>DATE:</b> {$app['created_at']}
        </td>
        <td align='right'>
          $photoHtml<br>$qrSvg
        </td>
      </tr>
    </table>
    <hr>
    ";
}

/* =====================================================
   6. PERSONAL DETAILS TABLE
===================================================== */
function personalTable($f) {
return "
<table>
<tr><th colspan='4'>PERSONAL DETAILS</th></tr>
<tr><td>Student Name</td><td colspan='3'>{$f['student_name']}</td></tr>
<tr><td>Gender</td><td>{$f['gender']}</td><td>Religion</td><td>{$f['religion']}</td></tr>
<tr><td>Category</td><td>{$f['category']}</td><td>Sub Caste</td><td>{$f['sub_caste']}</td></tr>
<tr><td>DOB</td><td>{$f['dob']}</td><td>State</td><td>{$f['state']}</td></tr>
<tr><td>Father / Guardian</td><td colspan='3'>{$f['father_name']}</td></tr>
<tr><td>Mother</td><td colspan='3'>{$f['mother_name']}</td></tr>
<tr><td>Email</td><td>{$f['email']}</td><td>Mobile</td><td>{$f['mobile']}</td></tr>
<tr><td>Guardian Mobile</td><td colspan='3'>{$f['guardian_mobile']}</td></tr>
<tr><td>Permanent Address</td><td colspan='3'>{$f['permanent_address']}</td></tr>
<tr><td>Admission Through</td><td>{$f['admission_through']}</td>
<td>Allotted Branch</td><td>{$f['allotted_branch']}</td></tr>
<tr><td>Previous Combination</td><td colspan='3'>{$f['prev_combination']}</td></tr>
</table>
";
}

/* =====================================================
   7. DOCUMENT CHECKLIST TABLE
===================================================== */
function docTable($d) {
return "
<table>
<tr><th>Sl</th><th>Document</th><th>Status</th></tr>
<tr><td>1</td><td>10th Marks Card</td><td>".($d['marks_10'] ?? '')."</td></tr>
<tr><td>2</td><td>12th / Diploma Marks Card</td><td>".($d['marks_12'] ?? '')."</td></tr>
<tr><td>3</td><td>Study Certificate</td><td>".($d['study_certificate'] ?? '')."</td></tr>
<tr><td>4</td><td>Transfer Certificate</td><td>".($d['transfer_certificate'] ?? '')."</td></tr>
<tr><td>5</td><td>Photograph</td><td>".($d['photo'] ?? '')."</td></tr>
</table>
";
}

/* =====================================================
   8. FULL HTML (STUDENT + COLLEGE COPY)
===================================================== */
$html = "
<style>
body { font-family: Arial; font-size: 12px; }
table { width:100%; border-collapse:collapse; margin-top:8px; }
td, th { border:1px solid #000; padding:4px; }
th { background:#f0f0f0; }
.center { text-align:center; }
</style>

".headerBlock($photoHtml,$qrSvg,$app)."
".personalTable($form)."

<h4 class='center'>ACKNOWLEDGMENT – STUDENT COPY</h4>
<p>
This is to certify that the documents mentioned below are received from
<b>{$form['student_name']}</b> for admission to BE –
<b>{$form['allotted_branch']}</b> for the academic year 2025–26.
</p>

".docTable($docs)."

<br><br>
<table style='border:none' width='100%'>
<tr>
<td style='border:none'>Student Signature</td>
<td style='border:none' align='right'>Admission Officer</td>
</tr>
</table>

<pagebreak>

<h4 class='center'>ACKNOWLEDGMENT – COLLEGE COPY</h4>

<p>
This is to certify that the documents mentioned below are received from
<b>{$form['student_name']}</b> for admission to BE –
<b>{$form['allotted_branch']}</b> for the academic year 2025–26.
</p>

".docTable($docs)."

<br><br>
<table style='border:none' width='100%'>
<tr>
<td style='border:none'>Student Signature</td>
<td style='border:none' align='right'>Admission Officer</td>
</tr>
</table>
";

/* =====================================================
   9. PDF OUTPUT (FIXED & COMPLETE)
===================================================== */
$dompdf = new Dompdf(['isRemoteEnabled' => true]);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$pdf = $dompdf->output();

ob_end_clean();
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="VVIT_'.$id.'.pdf"');
header('Content-Length: '.strlen($pdf));
echo $pdf;
exit;
