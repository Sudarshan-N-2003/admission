<?php
require_once 'auth.php';
require_once '../db.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;

$id = $_GET['id'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM admissions WHERE application_id=:id");
$stmt->execute([':id'=>$id]);
$data = $stmt->fetch();

if (!$data) die('Invalid ID');

// TEMP BASIC FORMAT (we’ll replace later)
$html = "
<h2 style='text-align:center'>Vijay Vittal Institute of Technology</h2>
<p><b>Application ID:</b> {$data['application_id']}</p>
<p><b>Name:</b> {$data['student_name']}</p>
<p><b>DOB:</b> {$data['dob']}</p>
<p><b>Branch:</b> {$data['allotted_branch']}</p>
<p><b>Admission Type:</b> {$data['admission_through']}</p>
<p><b>Registered On:</b> {$data['created_at']}</p>
";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4','portrait');
$dompdf->render();
$dompdf->stream($id.'.pdf',['Attachment'=>true]);
exit;