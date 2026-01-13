<?php
// ⚠️ ABSOLUTELY NOTHING BEFORE THIS LINE
declare(strict_types=1);

ob_start();               // ✅ buffer everything
session_start();

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../vendor/autoload.php';

$pdo = get_db();

/* ===============================
   GET APPLICATION ID
================================ */
$applicationId = $_GET['id'] ?? '';
if ($applicationId === '') {
    ob_end_clean();
    die('Invalid Application ID');
}

/* ===============================
   FETCH APPLICATION DATA
================================ */
$stmt = $pdo->prepare("
    SELECT *
    FROM admissions
    WHERE application_id = :id
");
$stmt->execute([':id' => $applicationId]);
$app = $stmt->fetch();

if (!$app) {
    ob_end_clean();
    die('Application not found');
}

/* ===============================
   PREPARE DATA
================================ */
$data = $app; // DB already stores flat data

/* ===============================
   CREATE PDF (TCPDF)
================================ */
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetCreator('VVIT');
$pdf->SetAuthor('VVIT Admission');
$pdf->SetTitle('Admission Application');
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 10);
$pdf->AddPage();

/* ===============================
   HEADER
================================ */
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'VIJAY VITTAL INSTITUTE OF TECHNOLOGY', 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Admission Application', 0, 1, 'C');
$pdf->Ln(4);

/* ===============================
   QR CODE (NO GD REQUIRED)
================================ */
$qrText = implode(' | ', [
    $data['application_id'],
    $data['mobile'],
    $data['allotted_branch'],
    $data['admission_through']
]);

$pdf->write2DBarcode(
    $qrText,
    'QRCODE,H',
    170,
    20,
    30,
    30
);

/* ===============================
   STUDENT DETAILS (70%)
================================ */
$pdf->SetFont('helvetica', '', 10);

function row(TCPDF $pdf, string $label, string $value) {
    $pdf->Cell(45, 7, $label, 1);
    $pdf->Cell(0, 7, $value ?: '-', 1, 1);
}

row($pdf, 'Application ID', $data['application_id']);
row($pdf, 'Student Name', $data['student_name']);
row($pdf, 'Gender', $data['gender']);
row($pdf, 'Religion', $data['religion']);
row($pdf, 'Category', $data['category']);
row($pdf, 'Sub Caste', $data['sub_caste']);
row($pdf, 'DOB', $data['dob']);
row($pdf, 'State', $data['state']);
row($pdf, 'Father Name', $data['father_name']);
row($pdf, 'Mother Name', $data['mother_name']);
row($pdf, 'Email', $data['email']);
row($pdf, 'Mobile', $data['mobile']);
row($pdf, 'Guardian Mobile', $data['guardian_mobile']);
row($pdf, 'Address', $data['permanent_address']);
row($pdf, 'Admission Type', $data['admission_through']);
row($pdf, 'Allotted Branch', $data['allotted_branch']);
row($pdf, 'Previous Combination', $data['prev_combination']);

/* ===============================
   STUDENT COPY (30%)
================================ */
$pdf->Ln(6);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 6, 'Student Copy', 0, 1);

/* ===============================
   PAGE 2 — COLLEGE COPY
================================ */
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'College Copy', 0, 1, 'C');
$pdf->Ln(4);

$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(
    0,
    6,
    "Certified that the above student is admitted to "
    . $data['allotted_branch']
    . " for the academic year " . date('Y') . "-" . (date('Y') + 1)
);

/* ===============================
   OUTPUT PDF (NO ECHO BEFORE THIS)
================================ */
ob_end_clean(); // ✅ clear buffer
$pdf->Output($applicationId . '.pdf', 'I');
exit;
