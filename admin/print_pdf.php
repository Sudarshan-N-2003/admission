<?php
// admin/print_pdf.php
// ⚠️ ABSOLUTELY NO OUTPUT BEFORE THIS LINE

error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../vendor/autoload.php';

/* ===============================
   INPUT
================================ */
$applicationId = $_GET['id'] ?? '';
if ($applicationId === '') {
    exit;
}

/* ===============================
   FETCH DATA
================================ */
$pdo = get_db();

$stmt = $pdo->prepare("SELECT * FROM admissions WHERE application_id = :id");
$stmt->execute([':id' => $applicationId]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) {
    exit;
}

/* ===============================
   DOCUMENT STATUS
================================ */
$docStatus = json_decode($app['document_status'] ?? '{}', true);

function docStatus($key, $arr) {
    return ($arr[$key] ?? '') === 'RECEIVED' ? 'RECEIVED' : '';
}

/* ===============================
   QR CONTENT
================================ */
$qrText = implode(" | ", [
  "APP ID: {$app['application_id']}",
  "MOBILE: {$app['mobile']}",
  "BRANCH: {$app['allotted_branch']}",
  "TYPE: {$app['admission_through']}"
]);

/* ===============================
   CREATE PDF
================================ */
$pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('VVIT');
$pdf->SetAuthor('VVIT');
$pdf->SetTitle('Admission Application');
$pdf->SetMargins(12, 12, 12);
$pdf->SetAutoPageBreak(true, 12);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

/* ===============================
   PAGE 1
================================ */
$pdf->AddPage();

/* HEADER */
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 6, 'VIJAYA VITTALA INSTITUTE OF TECHNOLOGY', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(
  0, 5,
  "35/1, Dodda Gubbi Post, Hennur–Bagalur Road,\nThanisandra, Bengaluru, Karnataka – 560077",
  0, 'C'
);

$pdf->Ln(3);

/* APPLICATION INFO */
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(95, 6, "APPLICATION NO: {$app['application_id']}", 0, 0);
$pdf->Cell(0, 6, "DATE & TIME: {$app['created_at']}", 0, 1);

/* PHOTO */
$pdf->Rect(165, 35, 30, 35);
if (!empty($app['photo_path']) && file_exists($app['photo_path'])) {
    $pdf->Image($app['photo_path'], 165, 35, 30, 35);
}

/* TABLE FUNCTION */
function row2($pdf, $l1, $v1, $l2='', $v2='') {
    $pdf->Cell(45, 7, $l1, 1);
    $pdf->Cell(65, 7, $v1, 1);
    if ($l2 !== '') {
        $pdf->Cell(35, 7, $l2, 1);
        $pdf->Cell(0, 7, $v2, 1);
    } else {
        $pdf->Cell(0, 7, '', 1);
    }
    $pdf->Ln();
}

/* PERSONAL INFO */
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 7, 'PERSONAL INFORMATION', 1, 1);

$pdf->SetFont('helvetica', '', 9);
row2($pdf, 'STUDENT NAME', $app['student_name']);
row2($pdf, 'GENDER', $app['gender'], 'RELIGION', $app['religion']);
row2($pdf, 'CATEGORY', $app['category'], 'SUB CASTE', $app['sub_caste']);
row2($pdf, 'DOB', $app['dob'], 'STATE', $app['state']);
row2($pdf, 'FATHER / GUARDIAN', $app['father_name']);
row2($pdf, 'MOTHER NAME', $app['mother_name']);
row2($pdf, 'EMAIL', $app['email'], 'MOBILE', $app['mobile']);
row2($pdf, 'GUARDIAN MOBILE', $app['guardian_mobile']);
row2($pdf, 'PERMANENT ADDRESS', $app['permanent_address']);
row2($pdf, 'ADMISSION THROUGH', $app['admission_through'], 'ALLOTTED BRANCH', $app['allotted_branch']);
row2($pdf, 'PREVIOUS COMBINATION', $app['prev_combination']);

/* STUDENT COPY */
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 7, 'ACKNOWLEDGMENT – STUDENT COPY', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(
  0, 6,
  "This is to certify that the following documents have been received from {$app['student_name']} for admission to BE in the Branch {$app['allotted_branch']} from the academic year 2025 – 2026.",
  0
);

/* DOC TABLE */
$pdf->Ln(2);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(10, 7, 'Sl', 1);
$pdf->Cell(120, 7, 'Document', 1);
$pdf->Cell(0, 7, 'Status', 1);
$pdf->Ln();

$pdf->SetFont('helvetica', '', 9);
$docs = [
  '10th Marks Card' => 'marks_10',
  '12th / Diploma Marks Card' => 'marks_12',
  'Study Certificate' => 'study_certificate',
  'Transfer Certificate' => 'transfer_certificate',
  'Photograph' => 'photo'
];

$i = 1;
foreach ($docs as $name => $key) {
    $pdf->Cell(10, 7, $i++, 1);
    $pdf->Cell(120, 7, $name, 1);
    $pdf->Cell(0, 7, docStatus($key, $docStatus), 1);
    $pdf->Ln();
}

/* SIGNATURES */
$pdf->Ln(10);
$pdf->Cell(80, 6, 'Student Signature', 0);
$pdf->Cell(0, 6, 'Admission Director', 0, 1, 'R');

/* QR CODE */
$pdf->write2DBarcode($qrText, 'QRCODE,H', 160, 245, 35, 35);

/* ===============================
   PAGE 2 – COLLEGE COPY
================================ */
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'VIJAYA VITTALA INSTITUTE OF TECHNOLOGY', 0, 1, 'C');

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'ACKNOWLEDGMENT – COLLEGE COPY', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(
  0, 6,
  "This is to certify that the following documents have been received from {$app['student_name']} for admission to BE in the Branch {$app['allotted_branch']} from the academic year 2025 – 2026.",
  0
);

/* SAME TABLE */
$pdf->Ln(2);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(10, 7, 'Sl', 1);
$pdf->Cell(120, 7, 'Document', 1);
$pdf->Cell(0, 7, 'Status', 1);
$pdf->Ln();

$pdf->SetFont('helvetica', '', 9);
$i = 1;
foreach ($docs as $name => $key) {
    $pdf->Cell(10, 7, $i++, 1);
    $pdf->Cell(120, 7, $name, 1);
    $pdf->Cell(0, 7, docStatus($key, $docStatus), 1);
    $pdf->Ln();
}

$pdf->Ln(15);
$pdf->Cell(80, 6, 'Student Signature', 0);
$pdf->Cell(0, 6, 'Admission Director', 0, 1, 'R');

/* ===============================
   OUTPUT
================================ */
$pdf->Output("VVIT_Application_{$applicationId}.pdf", 'I');
exit;
