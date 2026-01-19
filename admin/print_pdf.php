<?php
// ===============================
// NO OUTPUT BEFORE THIS LINE ❌
// ===============================
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use TCPDF;

// ===============================
// INPUT VALIDATION
// ===============================
$id = $_GET['id'] ?? '';
if ($id === '') {
    die('Invalid Application ID');
}

// ===============================
// FETCH DATA FROM DB
// ===============================
$pdo = get_db();

$stmt = $pdo->prepare("
    SELECT *
    FROM admissions
    WHERE application_id = :id
");
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();

if (!$row) {
    die('Application not found');
}

// ===============================
// DOCUMENT STATUS
// ===============================
$docStatus = json_decode($row['document_status'] ?? '{}', true);

// ===============================
// CREATE PDF
// ===============================
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('VVIT');
$pdf->SetAuthor('VVIT Admissions');
$pdf->SetTitle('Admission Application');
$pdf->SetMargins(12, 12, 12);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

// ===============================
// HEADER
// ===============================
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, 'VIJAYA VITTALA INSTITUTE OF TECHNOLOGY', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 8);
$pdf->MultiCell(
    0,
    4,
    "35/1, Dodda Gubbi Post, Hennur–Bagalur Road,\nThanisandra, Bengaluru, Karnataka – 560077",
    0,
    'C'
);

$pdf->Ln(3);

// ===============================
// APPLICATION META
// ===============================
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(95, 6, 'APPLICATION NO: ' . $row['application_id'], 0, 0);
$pdf->Cell(0, 6, 'DATE & TIME: ' . $row['created_at'], 0, 1, 'R');

$pdf->Ln(2);

// ===============================
// PHOTO
// ===============================
$photoPath = $row['photo_path'];
if ($photoPath && file_exists($photoPath)) {
    $pdf->Image($photoPath, 165, 35, 28, 34);
    $pdf->Rect(165, 35, 28, 34);
}

// ===============================
// PERSONAL INFO TABLE
// ===============================
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 6, 'PERSONAL INFORMATION', 1, 1, 'L', true);

$pdf->SetFont('helvetica', '', 9);

function row2($pdf, $l1, $v1, $l2, $v2) {
    $pdf->Cell(40, 6, $l1, 1);
    $pdf->Cell(55, 6, $v1, 1);
    $pdf->Cell(35, 6, $l2, 1);
    $pdf->Cell(0, 6, $v2, 1, 1);
}

row2($pdf, 'STUDENT NAME', $row['student_name'], 'RELIGION', $row['religion']);
row2($pdf, 'GENDER', $row['gender'], 'SUB CASTE', $row['sub_caste']);
row2($pdf, 'CATEGORY', $row['category'], 'STATE', $row['state']);
row2($pdf, 'DOB', $row['dob'], '', '');
row2($pdf, 'FATHER / GUARDIAN', $row['father_name'], '', '');
row2($pdf, 'MOTHER NAME', $row['mother_name'], '', '');
row2($pdf, 'EMAIL', $row['email'], 'MOBILE', $row['mobile']);
row2($pdf, 'GUARDIAN MOBILE', $row['guardian_mobile'], '', '');

$pdf->Cell(40, 10, 'PERMANENT ADDRESS', 1);
$pdf->MultiCell(0, 10, $row['permanent_address'], 1);

row2(
    $pdf,
    'ADMISSION THROUGH',
    $row['admission_through'],
    'ALLOTTED BRANCH',
    $row['allotted_branch']
);

row2($pdf, 'PREVIOUS COMBINATION', $row['prev_combination'], '', '');

$pdf->Ln(4);

// ===============================
// ACKNOWLEDGEMENT (STUDENT COPY)
// ===============================
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'ACKNOWLEDGEMENT – STUDENT COPY', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(
    0,
    6,
    "This is to certify that the following documents have been received from {$row['student_name']} for admission to BE in the Branch {$row['allotted_branch']} for the academic year {$row['academic_year']}.",
    0
);

$pdf->Ln(2);

// ===============================
// DOCUMENT TABLE
// ===============================
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(10, 6, 'Sl', 1);
$pdf->Cell(120, 6, 'Document', 1);
$pdf->Cell(0, 6, 'Status', 1, 1);

$pdf->SetFont('helvetica', '', 9);

$docs = [
    '10th Marks Card',
    '12th / Diploma Marks Card',
    'Study Certificate',
    'Transfer Certificate',
    'Photograph'
];

$i = 1;
foreach ($docs as $d) {
    $pdf->Cell(10, 6, $i++, 1);
    $pdf->Cell(120, 6, $d, 1);
    $pdf->Cell(0, 6, 'RECEIVED', 1, 1);
}

$pdf->Ln(10);
$pdf->Cell(90, 6, 'Student Signature', 0, 0);
$pdf->Cell(0, 6, 'Admission Director', 0, 1, 'R');

// ===============================
// QR CODE (FIXED POSITION)
// ===============================
$qrData = implode(' | ', [
    $row['application_id'],
    $row['mobile'],
    $row['allotted_branch'],
    $row['admission_through']
]);

$pdf->write2DBarcode($qrData, 'QRCODE,H', 155, 215, 28, 28);

// ===============================
// PAGE 2 – COLLEGE COPY
// ===============================
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, 'VIJAYA VITTALA INSTITUTE OF TECHNOLOGY', 0, 1, 'C');

$pdf->Ln(5);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'ACKNOWLEDGEMENT – COLLEGE COPY', 0, 1, 'C');

$pdf->Ln(3);

$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(
    0,
    6,
    "This is to certify that the following documents have been received from {$row['student_name']} for admission to BE in the Branch {$row['allotted_branch']} for the academic year {$row['academic_year']}.",
    0
);

$pdf->Ln(2);

// Same document table
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(10, 6, 'Sl', 1);
$pdf->Cell(120, 6, 'Document', 1);
$pdf->Cell(0, 6, 'Status', 1, 1);

$pdf->SetFont('helvetica', '', 9);

$i = 1;
foreach ($docs as $d) {
    $pdf->Cell(10, 6, $i++, 1);
    $pdf->Cell(120, 6, $d, 1);
    $pdf->Cell(0, 6, 'RECEIVED', 1, 1);
}

$pdf->Ln(10);
$pdf->Cell(90, 6, 'Student Signature', 0, 0);
$pdf->Cell(0, 6, 'Admission Director', 0, 1, 'R');

// ===============================
// OUTPUT
// ===============================
$pdf->Output($row['application_id'] . '.pdf', 'I');
