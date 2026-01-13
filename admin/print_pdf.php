<?php
session_start();

/* ===============================
   AUTH + DB
================================ */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use TCPDF;

/* ===============================
   VALIDATE ID
================================ */
$appId = $_GET['id'] ?? '';
if ($appId === '') {
    die('Invalid Application ID');
}

/* ===============================
   FETCH DATA
================================ */
$pdo = get_db();

$stmt = $pdo->prepare("
    SELECT *
    FROM admissions
    WHERE application_id = :id
");
$stmt->execute([':id' => $appId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die('Application not found');
}

/* ===============================
   NORMALIZE DATA
================================ */
function v($key, $row) {
    return htmlspecialchars($row[$key] ?? '', ENT_QUOTES, 'UTF-8');
}

/* ===============================
   CREATE PDF
================================ */
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

$pdf->setCreator('VVIT');
$pdf->setAuthor('VVIT Admissions');
$pdf->setTitle('Admission Application');

$pdf->setMargins(12, 12, 12);
$pdf->setAutoPageBreak(true, 12);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

/* ============================================================
   PAGE 1 — STUDENT COPY
============================================================ */
$pdf->AddPage();

/* ---------- HEADER ---------- */
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'Vijay Vittal Institute of Technology', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Admission Application – Student Copy', 0, 1, 'C');
$pdf->Ln(4);

/* ---------- QR CODE ---------- */
$qrText = implode("\n", [
    'Application ID: ' . v('application_id', $row),
    'Mobile: ' . v('mobile', $row),
    'Branch: ' . v('allotted_branch', $row),
    'Admission: ' . v('admission_through', $row)
]);

$pdf->write2DBarcode(
    $qrText,
    'QRCODE,H',
    165,
    15,
    30,
    30
);

/* ---------- PHOTO ---------- */
if (!empty($row['photo_path']) && file_exists($row['photo_path'])) {
    $pdf->Image($row['photo_path'], 15, 25, 30, 38);
}

/* ---------- INFO TABLE ---------- */
$pdf->Ln(42);
$pdf->SetFont('helvetica', '', 10);

function row($pdf, $label, $value) {
    $pdf->Cell(55, 7, $label, 1);
    $pdf->Cell(0, 7, $value, 1, 1);
}

row($pdf, 'Application ID', v('application_id', $row));
row($pdf, 'Student Name', v('student_name', $row));
row($pdf, 'Gender / DOB', v('gender', $row) . ' / ' . v('dob', $row));
row($pdf, 'Religion / Category', v('religion', $row) . ' / ' . v('category', $row));
row($pdf, 'Sub Caste', v('sub_caste', $row));
row($pdf, 'Father Name', v('father_name', $row));
row($pdf, 'Mother Name', v('mother_name', $row));
row($pdf, 'Mobile / Guardian', v('mobile', $row) . ' / ' . v('guardian_mobile', $row));
row($pdf, 'Email', v('email', $row));
row($pdf, 'Address', v('permanent_address', $row));
row($pdf, 'Previous College', v('prev_college', $row));
row($pdf, 'Previous Combination', v('prev_combination', $row));
row($pdf, 'Admission Through', v('admission_through', $row));
row($pdf, 'Allotted Branch', v('allotted_branch', $row));
row($pdf, 'Allotted Quota', v('seat_allotted', $row));

/* ---------- STUDENT SIGN ---------- */
$pdf->Ln(10);
$pdf->Cell(0, 8, 'Student Signature', 0, 1);
if (!empty($row['signature_path']) && file_exists($row['signature_path'])) {
    $pdf->Image($row['signature_path'], 15, $pdf->GetY(), 40, 15);
}

/* ============================================================
   PAGE 2 — COLLEGE COPY
============================================================ */
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'Vijay Vittal Institute of Technology', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Admission Application – College Copy', 0, 1, 'C');
$pdf->Ln(6);

/* ---------- SUMMARY ---------- */
$pdf->SetFont('helvetica', '', 10);

row($pdf, 'Application ID', v('application_id', $row));
row($pdf, 'Student Name', v('student_name', $row));
row($pdf, 'Mobile', v('mobile', $row));
row($pdf, 'Admission Type', v('admission_through', $row));
row($pdf, 'Branch', v('allotted_branch', $row));

/* ---------- CHECKLIST ---------- */
$pdf->Ln(6);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 8, 'Document Checklist', 0, 1);

$pdf->SetFont('helvetica', '', 10);

$status = json_decode($row['document_status'] ?? '{}', true);

$docs = [
    'marks_10' => '10th Marks Card',
    'marks_12' => '12th / Diploma Marks Card',
    'study_certificate' => 'Study Certificate',
    'transfer_certificate' => 'Transfer Certificate',
    'photo' => 'Photograph'
];

foreach ($docs as $k => $label) {
    $pdf->Cell(120, 7, $label, 1);
    $pdf->Cell(0, 7, ($status[$k] ?? 'PENDING'), 1, 1);
}

/* ---------- FOOTER ---------- */
$pdf->Ln(12);
$pdf->Cell(0, 8, 'Office Seal & Signature', 0, 1);

/* ===============================
   MARK AS PRINTED
================================ */
$pdo->prepare("
    UPDATE admissions
    SET printed_at = NOW()
    WHERE application_id = :id
")->execute([':id' => $appId]);

/* ===============================
   OUTPUT PDF
================================ */
$pdf->Output('VVIT_' . $appId . '.pdf', 'D');
exit;
