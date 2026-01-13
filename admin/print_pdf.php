<?php
ob_start();

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;

/* -------------------------------
   GET APPLICATION ID
-------------------------------- */
$appId = $_GET['id'] ?? '';
if (!$appId) {
  die('Invalid Application ID');
}

/* -------------------------------
   FETCH DATA FROM DB
-------------------------------- */
$pdo = get_db();

$stmt = $pdo->prepare("
  SELECT *
  FROM admissions
  WHERE application_id = :id
");
$stmt->execute([':id' => $appId]);
$d = $stmt->fetch();

if (!$d) {
  die('Application not found');
}

/* -------------------------------
   QR CODE (NO GD REQUIRED)
-------------------------------- */
$qrText = "ID: {$d['application_id']}\n"
        . "MOBILE: {$d['mobile']}\n"
        . "BRANCH: {$d['allotted_branch']}\n"
        . "TYPE: {$d['admission_through']}";

$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($qrText);

/* -------------------------------
   IMAGE PATHS
-------------------------------- */
$photoPath = $d['photo_path'] ? 'file://' . $d['photo_path'] : '';

/* -------------------------------
   BUILD HTML
-------------------------------- */
$html = "
<style>
  body { font-family: Arial, sans-serif; font-size: 12px; }
  .page { page-break-after: always; }
  .header { display:flex; justify-content:space-between; }
  .photo { width:120px; height:140px; border:1px solid #000; }
  .qr { width:120px; }
  .section { margin-top:10px; }
  table { width:100%; border-collapse:collapse; }
  td { padding:4px; vertical-align:top; }
  .line { border-top:1px dashed #000; margin:10px 0; }
  .center { text-align:center; font-family:'Times New Roman'; font-weight:bold; }
</style>

<!-- ================= PAGE 1 ================= -->
<div class='page'>

  <!-- HEADER -->
  <div class='header'>
    <img src='{$photoPath}' class='photo'>
    <div>
      <b>Application ID:</b> {$d['application_id']}<br>
      <b>Date:</b> ".date('d-m-Y')."<br><br>
      <img src='{$qrUrl}' class='qr'>
    </div>
  </div>

  <!-- 70% STUDENT INFO -->
  <div class='section'>
    <table>
      <tr><td><b>Name</b></td><td>{$d['student_name']}</td></tr>
      <tr><td><b>Gender</b></td><td>{$d['gender']}</td></tr>
      <tr><td><b>DOB</b></td><td>{$d['dob']}</td></tr>
      <tr><td><b>Religion</b></td><td>{$d['religion']}</td></tr>
      <tr><td><b>Category</b></td><td>{$d['category']} ({$d['sub_caste']})</td></tr>
      <tr><td><b>Father Name</b></td><td>{$d['father_name']}</td></tr>
      <tr><td><b>Mother Name</b></td><td>{$d['mother_name']}</td></tr>
      <tr><td><b>Mobile</b></td><td>{$d['mobile']}</td></tr>
      <tr><td><b>Email</b></td><td>{$d['email']}</td></tr>
      <tr><td><b>Address</b></td><td>{$d['permanent_address']}</td></tr>
      <tr><td><b>Previous College</b></td><td>{$d['prev_college']}</td></tr>
      <tr><td><b>Combination</b></td><td>{$d['prev_combination']}</td></tr>
      <tr><td><b>Admission Type</b></td><td>{$d['admission_through']}</td></tr>
      <tr><td><b>Branch</b></td><td>{$d['allotted_branch']}</td></tr>
    </table>
  </div>

  <!-- STUDENT COPY (BOTTOM 30%) -->
  <div class='line'></div>
  <div class='center'>STUDENT COPY</div>

</div>

<!-- ================= PAGE 2 ================= -->
<div class='page'>

  <!-- COLLEGE COPY (TOP 30%) -->
  <div class='center'>VIJAY VITTAL INSTITUTE OF TECHNOLOGY</div>
  <div class='center'>COLLEGE COPY</div>

  <div class='section'>
    <p>
      Certified that <b>{$d['student_name']}</b> has been admitted to
      <b>{$d['allotted_branch']}</b> through
      <b>{$d['admission_through']}</b>.
    </p>
  </div>

</div>
";

/* -------------------------------
   GENERATE PDF
-------------------------------- */
$dompdf = new Dompdf(['isRemoteEnabled' => true]);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$pdf = $dompdf->output();

ob_end_clean();
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="'.$appId.'.pdf"');
header('Content-Length: '.strlen($pdf));
echo $pdf;
exit;
