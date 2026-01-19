<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../vendor/autoload.php';

// ---------------------------
// GET APPLICATION ID
// ---------------------------
$id = $_GET['id'] ?? '';
if (!$id) {
    exit('Invalid Application ID');
}

// ---------------------------
// FETCH DATA FROM DB
// ---------------------------
$pdo = get_db();

$stmt = $pdo->prepare("
    SELECT *
    FROM admissions
    WHERE application_id = :id
");
$stmt->execute([':id' => $id]);
$d = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$d) {
    exit('Application not found');
}

// ---------------------------
// FIX ACADEMIC YEAR (SAFE)
// ---------------------------
$year = date('Y', strtotime($d['created_at']));
$academic_year = $year . ' - ' . ($year + 1);

// ---------------------------
// TCPDF INIT
// ---------------------------
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('VVIT');
$pdf->SetAuthor('VVIT');
$pdf->SetTitle('Admission Application');
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 15);
$pdf->SetPrintHeader(false);
$pdf->SetPrintFooter(false);

// ==========================================================
// PAGE 1 – STUDENT COPY
// ==========================================================
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 9);

// ---------------------------
// HEADER
// ---------------------------
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, 'VIJAYA VITTALA INSTITUTE OF TECHNOLOGY', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(
    0,
    5,
    "35/1, Dodda Gubbi Post, Hennur-Bagalur Road,\nThanisandra, Bengaluru, Karnataka - 560077",
    0,
    'C'
);
$pdf->Ln(3);

$pdf->Cell(95, 6, 'APPLICATION NO: ' . $d['application_id'], 0, 0);
$pdf->Cell(95, 6, 'DATE & TIME: ' . date('Y-m-d H:i:s', strtotime($d['created_at'])), 0, 1, 'R');

// ---------------------------
// PHOTO (CLOUDFLARE SAFE)
// ---------------------------
$x = 170;
$y = 35;
$pdf->Rect($x, $y, 25, 30);

if (!empty($d['photo_path'])) {
    $tmpPhoto = sys_get_temp_dir() . '/photo_' . uniqid() . '.jpg';
    $imgData = @file_get_contents($d['photo_path']);

    if ($imgData !== false) {
        file_put_contents($tmpPhoto, $imgData);
        if (file_exists($tmpPhoto)) {
            $pdf->Image($tmpPhoto, $x, $y, 25, 30);
        }
    }
}

// ---------------------------
// PERSONAL INFORMATION
// ---------------------------
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 7, 'PERSONAL INFORMATION', 1, 1);

$pdf->SetFont('helvetica', '', 9);

function row($pdf, $l1, $v1, $l2 = '', $v2 = '') {
    $pdf->Cell(40, 7, $l1, 1);
    $pdf->Cell(55, 7, $v1, 1);
    if ($l2 !== '') {
        $pdf->Cell(40, 7, $l2, 1);
        $pdf->Cell(55, 7, $v2, 1);
    } else {
        $pdf->Cell(95, 7, '', 1);
    }
    $pdf->Ln();
}

row($pdf, 'STUDENT NAME', $d['student_name']);
row($pdf, 'GENDER', $d['gender']);
row($pdf, 'RELIGION', $d['religion'], 'CATEGORY', $d['category']);
row($pdf, 'SUB CASTE', $d['sub_caste']);
row($pdf, 'DOB', $d['dob'], 'STATE', $d['state']);
row($pdf, 'FATHER / GUARDIAN', $d['father_name']);
row($pdf, 'MOTHER NAME', $d['mother_name']);
row($pdf, 'EMAIL', $d['email'], 'MOBILE', $d['mobile']);
row($pdf, 'GUARDIAN MOBILE', $d['guardian_mobile']);
row($pdf, 'PERMANENT ADDRESS', $d['permanent_address']);
row($pdf, 'ADMISSION THROUGH', $d['admission_through'], 'ALLOTTED BRANCH', $d['allotted_branch']);
row($pdf, 'PREVIOUS COMBINATION', $d['prev_combination']);

// ---------------------------
// ACKNOWLEDGMENT – STUDENT COPY
// ---------------------------
$pdf->Ln(6);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, 'VIJAYA VITTALA INSTITUTE OF TECHNOLOGY', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'ACKNOWLEDGMENT - STUDENT COPY', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(
    0,
    6,
    "This is to certify that the following documents have been received from {$d['student_name']} for admission to BE in the Branch {$d['allotted_branch']} from the academic year {$academic_year}.",
    0
);

$pdf->Ln(2);

$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(10, 7, 'Sl', 1);
$pdf->Cell(120, 7, 'Document', 1);
$pdf->Cell(50, 7, 'Status', 1);
$pdf->Ln();

$pdf->SetFont('helvetica', '', 9);
$docs = [
    '10th Marks Card',
    '12th / Diploma Marks Card',
    'Study Certificate',
    'Transfer Certificate',
    'Photograph'
];

// Assuming DB has columns like 'received_10th', 'received_12th', etc., as booleans or truthy values for dynamic status.
// If not present, defaults to 'RECEIVED'. Add/alter columns in DB as needed for auto-update.
$statuses = [
    '10th Marks Card' => isset($d['received_10th']) && $d['received_10th'] ? 'RECEIVED' : 'NOT RECEIVED',
    '12th / Diploma Marks Card' => isset($d['received_12th']) && $d['received_12th'] ? 'RECEIVED' : 'NOT RECEIVED',
    'Study Certificate' => isset($d['received_study']) && $d['received_study'] ? 'RECEIVED' : 'NOT RECEIVED',
    'Transfer Certificate' => isset($d['received_transfer']) && $d['received_transfer'] ? 'RECEIVED' : 'NOT RECEIVED',
    'Photograph' => !empty($d['photo_path']) ? 'RECEIVED' : 'NOT RECEIVED'  // Photo uses existing field for example
];

$i = 1;
foreach ($docs as $doc) {
    $pdf->Cell(10, 7, $i++, 1);
    $pdf->Cell(120, 7, $doc, 1);
    $pdf->Cell(50, 7, $statuses[$doc], 1);
    $pdf->Ln();
}

$pdf->Ln(12);
$pdf->Cell(90, 7, 'Student Signature', 0, 0);
$pdf->Cell(90, 7, 'Admission Director', 0, 1, 'R');

// ==========================================================
// PAGE 2 – COLLEGE COPY
// ==========================================================
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 7, 'VIJAYA VITTALA INSTITUTE OF TECHNOLOGY', 0, 1, 'C');

$pdf->Ln(4);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'ACKNOWLEDGMENT - COLLEGE COPY', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(
    0,
    6,
    "This is to certify that the following documents have been received from {$d['student_name']} for admission to BE in the Branch {$d['allotted_branch']} from the academic year {$academic_year}.",
    0
);

$pdf->Ln(2);

$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(10, 7, 'Sl', 1);
$pdf->Cell(120, 7, 'Document', 1);
$pdf->Cell(50, 7, 'Status', 1);
$pdf->Ln();

$pdf->SetFont('helvetica', '', 9);
$i = 1;
foreach ($docs as $doc) {
    $pdf->Cell(10, 7, $i++, 1);
    $pdf->Cell(120, 7, $doc, 1);
    $pdf->Cell(50, 7, $statuses[$doc], 1);
    $pdf->Ln();
}

$pdf->Ln(12);
$pdf->Cell(90, 7, 'Student Signature', 0, 0);
$pdf->Cell(90, 7, 'Admission Director', 0, 1, 'R');

// ---------------------------
// OUTPUT PDF
// ---------------------------
$pdf->Output('VVIT_' . $id . '.pdf', 'I');
exit;
