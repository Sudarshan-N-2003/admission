<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

/* ===============================
   1. DB CONNECTION & FETCH DATA
================================*/
$pdo = get_db();
$id = $_GET['id'] ?? '';
if (!$id) die('Invalid Application ID');

$stmt = $pdo->prepare("SELECT * FROM admissions WHERE application_id = :id");
$stmt->execute([':id' => $id]);
$d = $stmt->fetch();
if (!$d) die('Application not found');

/* ===============================
   2. DOCUMENT STATUS JSON
================================*/
$docs = json_decode($d['document_status'] ?? '{}', true);

/* ===============================
   3. PHOTO (SAFE EMBED)
================================*/
$photoHtml = '';
if (!empty($d['photo_path']) && file_exists($d['photo_path'])) {
    $img = base64_encode(file_get_contents($d['photo_path']));
    $photoHtml = "<img src='data:image/jpeg;base64,$img' style='width:110px;height:130px;border:1px solid #000'>";
}

/* ===============================
   4. QR CODE (NO GD DEPENDENCY)
================================*/
$qrText =
"Application ID: {$d['application_id']}\n".
"Mobile: {$d['mobile']}\n".
"Branch: {$d['allotted_branch']}\n".
"Admission Type: {$d['admission_through']}";

$qr = Builder::create()
    ->writer(new PngWriter())
    ->data($qrText)
    ->size(120)
    ->margin(0)
    ->build();

$qrBase64 = base64_encode($qr->getString());

/* ===============================
   5. PDF HTML CONTENT
================================*/
$html = "
<style>
  body { font-family: Arial, sans-serif; font-size: 12px; }
  .page { page-break-after: always; padding: 25px; }
  .header { display: flex; justify-content: space-between; align-items: flex-start; }
  .title { text-align: center; font-family: 'Times New Roman'; font-size: 18px; font-weight: bold; margin-top: -20px; }
  .subtitle { text-align: center; font-size: 13px; margin-bottom: 10px; }
  table { width: 100%; border-collapse: collapse; margin-top: 10px; }
  th, td { border: 1px solid #000; padding: 6px; vertical-align: top; }
  th { background: #f2f2f2; }
  .ack { margin-top: 20px; line-height: 1.5; }
  .sign-row { display: flex; justify-content: space-between; margin-top: 40px; }
  .sign-box { text-align: center; width: 45%; }
</style>

<!-- ================= STUDENT COPY ================= -->
<div class='page'>
  <div class='header'>
    <div>$photoHtml</div>
    <div>
      <img src='data:image/png;base64,$qrBase64' width='110'><br>
      <b>ID:</b> {$d['application_id']}
    </div>
  </div>

  <div class='title'>Vijaya Vittala Institute of Technology</div>
  <div class='subtitle'>Admission Application – Student Copy</div>

  <table>
    <tr><th>Student Name</th><td>{$d['student_name']}</td><th>DOB</th><td>{$d['dob']}</td></tr>
    <tr><th>Gender</th><td>{$d['gender']}</td><th>Religion</th><td>{$d['religion']}</td></tr>
    <tr><th>Category</th><td>{$d['category']}</td><th>Sub Caste</th><td>{$d['sub_caste']}</td></tr>
    <tr><th>Father / Guardian</th><td>{$d['father_name']}</td><th>Mother</th><td>{$d['mother_name']}</td></tr>
    <tr><th>Email</th><td>{$d['email']}</td><th>Mobile</th><td>{$d['mobile']}</td></tr>
    <tr><th>Guardian Mobile</th><td>{$d['guardian_mobile']}</td><th>State</th><td>{$d['state']}</td></tr>
    <tr><th>Address</th><td colspan='3'>{$d['permanent_address']}</td></tr>
    <tr><th>Admission Through</th><td>{$d['admission_through']}</td><th>Allotted Branch</th><td>{$d['allotted_branch']}</td></tr>
    <tr><th>Previous College</th><td>{$d['prev_college']}</td><th>Previous Combination</th><td>{$d['prev_combination']}</td></tr>
  </table>

  <h4 style='margin-top:15px;'>Document Checklist</h4>
  <table>
    <tr><th>Sl</th><th>Document</th><th>Status</th></tr>
    <tr><td>1</td><td>10th Marks Card</td><td>".($docs['marks_10'] ?? '')."</td></tr>
    <tr><td>2</td><td>12th / Diploma Marks Card</td><td>".($docs['marks_12'] ?? '')."</td></tr>
    <tr><td>3</td><td>Study Certificate</td><td>".($docs['study_certificate'] ?? '')."</td></tr>
    <tr><td>4</td><td>Transfer Certificate</td><td>".($docs['transfer_certificate'] ?? '')."</td></tr>
    <tr><td>5</td><td>Photograph</td><td>".($docs['photo'] ?? '')."</td></tr>
  </table>

  <div class='ack'>
    <b>Certification:</b> This is to certify that Mr./Ms. <b>{$d['student_name']}</b> has been admitted
    to <b>{$d['allotted_branch']}</b> branch under <b>{$d['admission_through']}</b> quota for the academic year 2025–26.
  </div>

  <div class='sign-row'>
    <div class='sign-box'>_____________________<br>Student Signature</div>
    <div class='sign-box'>_____________________<br>Admission Director</div>
  </div>
</div>

<!-- ================= COLLEGE COPY ================= -->
<div class='page'>
  <div class='title'>Vijaya Vittala Institute of Technology</div>
  <div class='subtitle'>Admission Application – College Copy</div>

  <table>
    <tr><th>Student Name</th><td>{$d['student_name']}</td><th>DOB</th><td>{$d['dob']}</td></tr>
    <tr><th>Gender</th><td>{$d['gender']}</td><th>Religion</th><td>{$d['religion']}</td></tr>
    <tr><th>Category</th><td>{$d['category']}</td><th>Sub Caste</th><td>{$d['sub_caste']}</td></tr>
    <tr><th>Father / Guardian</th><td>{$d['father_name']}</td><th>Mother</th><td>{$d['mother_name']}</td></tr>
    <tr><th>Email</th><td>{$d['email']}</td><th>Mobile</th><td>{$d['mobile']}</td></tr>
    <tr><th>Guardian Mobile</th><td>{$d['guardian_mobile']}</td><th>State</th><td>{$d['state']}</td></tr>
    <tr><th>Address</th><td colspan='3'>{$d['permanent_address']}</td></tr>
    <tr><th>Admission Through</th><td>{$d['admission_through']}</td><th>Allotted Branch</th><td>{$d['allotted_branch']}</td></tr>
    <tr><th>Previous College</th><td>{$d['prev_college']}</td><th>Previous Combination</th><td>{$d['prev_combination']}</td></tr>
  </table>

  <h4 style='margin-top:15px;'>Document Checklist</h4>
  <table>
    <tr><th>Sl</th><th>Document</th><th>Status</th></tr>
    <tr><td>1</td><td>10th Marks Card</td><td></td></tr>
    <tr><td>2</td><td>12th / Diploma Marks Card</td><td></td></tr>
    <tr><td>3</td><td>Study Certificate</td><td></td></tr>
    <tr><td>4</td><td>Transfer Certificate</td><td></td></tr>
    <tr><td>5</td><td>Photograph</td><td></td></tr>
  </table>

  <div class='ack'>
    <b>Certification:</b> This is to certify that Mr./Ms. <b>{$d['student_name']}</b> has been admitted
    to <b>{$d['allotted_branch']}</b> branch under <b>{$d['admission_through']}</b> quota for the academic year 2025–26.
  </div>

  <div class='sign-row'>
    <div class='sign-box'>_____________________<br>Student Signature</div>
    <div class='sign-box'>_____________________<br>Admission Director</div>
  </div>
</div>
";

/* ===============================
   6. GENERATE PDF
================================*/
$dompdf = new Dompdf();
$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml($html);
$dompdf->render();

$dompdf->stream("VVIT_Application_{$d['application_id']}.pdf", ["Attachment" => true]);
exit;
?>
