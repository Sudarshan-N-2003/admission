<?php
// NO OUTPUT BEFORE THIS LINE
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../vendor/autoload.php';

// ---------------------------
// GET APPLICATION ID
// ---------------------------
$id = $_GET['id'] ?? '';
if (!$id) {
    die('Invalid Application ID');
}

// ---------------------------
// FETCH DATA
// ---------------------------
$pdo = get_db();
$stmt = $pdo->prepare("SELECT * FROM admissions WHERE application_id = :id");
$stmt->execute([':id' => $id]);
$d = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$d) {
    die('Application not found');
}

// ---------------------------
// ACADEMIC YEAR
// ---------------------------
$year = date('Y', strtotime($d['created_at']));
$academic_year = $year . ' - ' . ($year + 1);

// ---------------------------
// HELPER: Download image from URL to temp file
// ---------------------------
function downloadImageToTemp($url) {
    if (empty($url)) return null;

    // Try cURL first (more reliable)
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $imgData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($imgData === false || $httpCode !== 200) {
            return null;
        }
    } else {
        // Fallback to file_get_contents
        ini_set('allow_url_fopen', 1);
        $imgData = @file_get_contents($url);
        if ($imgData === false) return null;
    }

    // Determine extension from URL
    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        $ext = 'jpg';
    }

    // Save to temp file
    $tmpFile = tempnam(sys_get_temp_dir(), 'vvit_img_') . '.' . $ext;
    file_put_contents($tmpFile, $imgData);

    return $tmpFile;
}

// ---------------------------
// HELPER: Render a table row
// ---------------------------
function row($pdf, $l1, $v1, $l2 = '', $v2 = '') {
    $pdf->Cell(35, 7, $l1, 1);
    $pdf->Cell(55, 7, (string)$v1, 1);

    if ($l2 !== '') {
        $pdf->Cell(35, 7, $l2, 1);
        $pdf->Cell(55, 7, (string)$v2, 1);
    } else {
        $pdf->Cell(90, 7, '', 1);
    }
    $pdf->Ln();
}

// ---------------------------
// DOCUMENT LIST
// ---------------------------
$docs = [
    '10th / 12th Marks Card' => $d['marks_12_path']           ?? '',
    'Study Certificate'      => $d['study_certificate_path']  ?? '',
    'Transfer Certificate'   => $d['transfer_certificate_path'] ?? '',
    'Photograph'             => $d['photo_path']               ?? '',
    'Signature'              => $d['signature_path']           ?? '',
];

// ---------------------------
// PDF INIT
// ---------------------------
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('VVIT');
$pdf->SetAuthor('VVIT');
$pdf->SetTitle('Admission Application');
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 12);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 9);

// ---------------------------
// PAGE 1 – HEADER
// ---------------------------
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, 'VIJAYA VITTALA INSTITUTE OF TECHNOLOGY', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(
    0,
    5,
    "35/1, Dodda Gubbi Post, Hennur–Bagalur Road,\nThanisandra, Bengaluru, Karnataka – 560077",
    0,
    'C'
);

$pdf->Ln(3);
$pdf->Cell(95, 6, 'APPLICATION NO: ' . $d['application_id'], 0, 0);
$pdf->Cell(95, 6, 'DATE & TIME: ' . date('d-m-Y H:i:s', strtotime($d['created_at'])), 0, 1, 'R');

// ---------------------------
// PHOTO BOX (Fixed for Cloudflare R2)
// ---------------------------
$photoX = 170;
$photoY = 35;
$photoW = 25;
$photoH = 30;

$pdf->Rect($photoX, $photoY, $photoW, $photoH);

if (!empty($d['photo_path'])) {
    $tmpPhoto = downloadImageToTemp($d['photo_path']);

    if ($tmpPhoto && file_exists($tmpPhoto)) {
        $ext = strtolower(pathinfo($tmpPhoto, PATHINFO_EXTENSION));
        $tcpdfType = ($ext === 'jpg' || $ext === 'jpeg') ? 'JPEG' : strtoupper($ext);

        $pdf->Image(
            $tmpPhoto,
            $photoX,
            $photoY,
            $photoW,
            $photoH,
            $tcpdfType
        );

        unlink($tmpPhoto); // Cleanup temp file
    }
}

// ---------------------------
// PERSONAL INFORMATION TABLE
// ---------------------------
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 7, 'PERSONAL INFORMATION', 1, 1);

$pdf->SetFont('helvetica', '', 9);

row($pdf, 'STUDENT NAME',       $d['student_name']);
row($pdf, 'GENDER',             $d['gender'],            'RELIGION',        $d['religion']);
row($pdf, 'CATEGORY',           $d['category'],          'SUB CASTE',       $d['sub_caste']);
row($pdf, 'DOB',                $d['dob'],               'STATE',           $d['state']);
row($pdf, 'FATHER / GUARDIAN',  $d['father_name']);
row($pdf, 'MOTHER NAME',        $d['mother_name']);
row($pdf, 'EMAIL',              $d['email'],             'MOBILE',          $d['mobile']);
row($pdf, 'GUARDIAN MOBILE',    $d['guardian_mobile']);
row($pdf, 'PERMANENT ADDRESS',  $d['permanent_address']);
row($pdf, 'ADMISSION THROUGH',  $d['admission_through'], 'ALLOTTED BRANCH', $d['allotted_branch']);
row($pdf, 'PREVIOUS COMBINATION', $d['prev_combination']);

// ---------------------------
// ACKNOWLEDGMENT – STUDENT COPY
// ---------------------------
$pdf->Ln(6);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'ACKNOWLEDGMENT – STUDENT COPY', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(
    0,
    6,
    "This is to certify that the following documents have been received from {$d['student_name']} for admission to BE in the Branch {$d['allotted_branch']} for the academic year {$academic_year}.",
    0
);

$pdf->Ln(2);

// Document checklist header
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(10,  7, 'Sl',       1);
$pdf->Cell(120, 7, 'Document', 1);
$pdf->Cell(50,  7, 'Status',   1);
$pdf->Ln();

// Document checklist rows
$pdf->SetFont('helvetica', '', 9);
$i = 1;
foreach ($docs as $doc => $path) {
    $status = !empty($path) ? 'RECEIVED' : 'NOT RECEIVED';
    $pdf->Cell(10,  7, $i++,    1);
    $pdf->Cell(120, 7, $doc,    1);
    $pdf->Cell(50,  7, $status, 1);
    $pdf->Ln();
}

$pdf->Ln(12);
$pdf->Cell(90, 7, 'Student Signature',  0, 0);
$pdf->Cell(90, 7, 'Admission Director', 0, 1, 'R');

// ---------------------------
// PAGE 2 – COLLEGE COPY
// ---------------------------
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 7, 'VIJAYA VITTALA INSTITUTE OF TECHNOLOGY', 0, 1, 'C');

$pdf->Ln(4);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'ACKNOWLEDGMENT – COLLEGE COPY', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(
    0,
    6,
    "This is to certify that the following documents have been received from {$d['student_name']} for admission to BE in the Branch {$d['allotted_branch']} for the academic year {$academic_year}.",
    0
);

$pdf->Ln(2);

// Document checklist header
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(10,  7, 'Sl',       1);
$pdf->Cell(120, 7, 'Document', 1);
$pdf->Cell(50,  7, 'Status',   1);
$pdf->Ln();

// Document checklist rows
$pdf->SetFont('helvetica', '', 9);
$i = 1;
foreach ($docs as $doc => $path) {
    $status = !empty($path) ? 'RECEIVED' : 'NOT RECEIVED';
    $pdf->Cell(10,  7, $i++,    1);
    $pdf->Cell(120, 7, $doc,    1);
    $pdf->Cell(50,  7, $status, 1);
    $pdf->Ln();
}

$pdf->Ln(12);
$pdf->Cell(90, 7, 'Student Signature',  0, 0);
$pdf->Cell(90, 7, 'Admission Director', 0, 1, 'R');

// ---------------------------
// OUTPUT PDF
// ---------------------------
$pdf->Output('VVIT_' . $id . '.pdf', 'I');
exit;
