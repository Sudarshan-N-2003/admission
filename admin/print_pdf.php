<?php
declare(strict_types=1);
ob_start();
session_start();

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../vendor/autoload.php';

$pdo = get_db();

/* ===============================
   GET APPLICATION
================================ */
$id = $_GET['id'] ?? '';
if ($id === '') {
    ob_end_clean();
    die('Invalid Application ID');
}

$stmt = $pdo->prepare("SELECT * FROM admissions WHERE application_id = :id");
$stmt->execute([':id' => $id]);
$a = $stmt->fetch();

if (!$a) {
    ob_end_clean();
    die('Application not found');
}

/* ===============================
   TCPDF INIT
================================ */
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 10);
$pdf->SetPrintHeader(false);
$pdf->SetPrintFooter(false);

/* ===============================
   COMMON FUNCTION
================================ */
function cellRow(TCPDF $pdf, string $l1, string $v1, string $l2 = '', string $v2 = '') {
    $pdf->Cell(35, 7, $l1, 1);
    $pdf->Cell(60, 7, $v1 ?: '-', 1);
    if ($l2 !== '') {
        $pdf->Cell(35, 7, $l2, 1);
        $pdf->Cell(0, 7, $v2 ?: '-', 1);
    } else {
        $pdf->Cell(0, 7, '', 0);
    }
    $pdf->Ln();
}

/* ===============================
   PAGE 1 – STUDENT COPY
================================ */
$pdf->AddPage();

/* Header */
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'VIJAY VITTAL INSTITUTE OF TECHNOLOGY', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Admission Application Form', 0, 1, 'C');
$pdf->Ln(3);

/* Photo */
if (!empty($a['photo_path']) && file_exists($a['photo_path'])) {
    $pdf->Image($a['photo_path'], 10, 28, 30, 35);
}

/* QR Code */
$qr = implode(' | ', [
    $a['application_id'],
    $a['mobile'],
    $a['allotted_branch'],
    $a['admission_through']
]);

$pdf->write2DBarcode($qr, 'QRCODE,H', 170, 28, 25, 25);

/* Application Info */
$pdf->SetXY(45, 28);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Application ID: ' . $a['application_id'], 0, 1);
$pdf->SetX(45);
$pdf->Cell(0, 6, 'Date: ' . date('d-m-Y', strtotime($a['created_at'])), 0, 1);

$pdf->Ln(4);

/* Student Details Table */
$pdf->SetFont('helvetica', '', 9);

cellRow($pdf, 'Student Name', $a['student_name'], 'Gender', $a['gender']);
cellRow($pdf, 'Religion', $a['religion'], 'Category', $a['category']);
cellRow($pdf, 'Sub Caste', $a['sub_caste'], 'DOB', $a['dob']);
cellRow($pdf, 'State', $a['state'], 'Nationality', $a['nationality']);
cellRow($pdf, 'Father Name', $a['father_name'], 'Mother Name', $a['mother_name']);
cellRow($pdf, 'Email', $a['email'], 'Mobile', $a['mobile']);
cellRow($pdf, 'Guardian Mobile', $a['guardian_mobile']);
cellRow($pdf, 'Address', $a['permanent_address']);
cellRow($pdf, 'Admission Type', $a['admission_through'], 'Branch', $a['allotted_branch']);
cellRow($pdf, 'Previous Qualification', $a['prev_combination']);

$pdf->Ln(4);

/* Checklist – Student Copy */
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'Documents Checklist (Student Copy)', 1, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(10, 7, 'Sl', 1);
$pdf->Cell(100, 7, 'Document', 1);
$pdf->Cell(40, 7, 'Status', 1);
$pdf->Cell(0, 7, 'Submitted Date', 1);
$pdf->Ln();

$docs = [
    '10th / 12th Marks Card',
    'Study Certificate',
    'Transfer Certificate',
    'Caste / Income (If Applicable)',
    'Photograph'
];

$i = 1;
foreach ($docs as $d) {
    $pdf->Cell(10, 7, (string)$i++, 1);
    $pdf->Cell(100, 7, $d, 1);
    $pdf->Cell(40, 7, '', 1);
    $pdf->Cell(0, 7, '', 1);
    $pdf->Ln();
}

/* ===============================
   PAGE 2 – COLLEGE COPY
================================ */
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'College Copy', 0, 1, 'C');
$pdf->Ln(3);

$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(
    0,
    6,
    "Certified that the above student is admitted to the "
    . $a['allotted_branch']
    . " branch for the academic year "
    . date('Y') . "-" . (date('Y') + 1)
    . " at Vijay Vittal Institute of Technology."
);

$pdf->Ln(4);

/* Checklist – College Copy */
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'Documents Checklist (College Use)', 1, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(10, 7, 'Sl', 1);
$pdf->Cell(100, 7, 'Document', 1);
$pdf->Cell(40, 7, 'Status', 1);
$pdf->Cell(0, 7, 'Verified Date', 1);
$pdf->Ln();

$i = 1;
foreach ($docs as $d) {
    $pdf->Cell(10, 7, (string)$i++, 1);
    $pdf->Cell(100, 7, $d, 1);
    $pdf->Cell(40, 7, '', 1);
    $pdf->Cell(0, 7, '', 1);
    $pdf->Ln();
}

$pdf->Ln(15);
$pdf->Cell(0, 6, 'Office Signature: ___________________________', 0, 1);

/* ===============================
   OUTPUT
================================ */
ob_end_clean();
$pdf->Output($id . '.pdf', 'I');
exit;
