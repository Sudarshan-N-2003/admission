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

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $imgData  = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($imgData === false || $httpCode !== 200) return null;
    } else {
        ini_set('allow_url_fopen', 1);
        $imgData = @file_get_contents($url);
        if ($imgData === false) return null;
    }

    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) $ext = 'jpg';

    $tmpFile = tempnam(sys_get_temp_dir(), 'vvit_img_') . '.' . $ext;
    file_put_contents($tmpFile, $imgData);
    return $tmpFile;
}

// ---------------------------
// HELPER: Table row with auto-height (no text overflow/overwrite)
// Total usable width = 190mm: Label=38, Value=57 (x2 columns)
// ---------------------------
function row($pdf, $l1, $v1, $l2 = '', $v2 = '') {
    $lw  = 38;
    $vw  = 57;
    $h   = 7;
    $fs  = 9;

    $pdf->SetFont('helvetica', '', $fs);
    $lines1   = $pdf->getNumLines((string)$v1, $vw);
    $lines2   = ($l2 !== '') ? $pdf->getNumLines((string)$v2, $vw) : 1;
    $maxLines = max($lines1, $lines2, 1);
    $cellH    = max($h, $maxLines * $h);

    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // Label 1 (bold)
    $pdf->SetFont('helvetica', 'B', $fs);
    $pdf->MultiCell($lw, $cellH, $l1, 1, 'L', false, 0, $x, $y);

    // Value 1
    $pdf->SetFont('helvetica', '', $fs);
    $pdf->MultiCell($vw, $cellH, (string)$v1, 1, 'L', false, 0, $x + $lw, $y);

    if ($l2 !== '') {
        // Label 2 (bold)
        $pdf->SetFont('helvetica', 'B', $fs);
        $pdf->MultiCell($lw, $cellH, $l2, 1, 'L', false, 0, $x + $lw + $vw, $y);

        // Value 2
        $pdf->SetFont('helvetica', '', $fs);
        $pdf->MultiCell($vw, $cellH, (string)$v2, 1, 'L', false, 0, $x + $lw + $vw + $lw, $y);
    } else {
        // Empty filler cell
        $pdf->MultiCell($lw + $vw, $cellH, '', 1, 'L', false, 0, $x + $lw + $vw, $y);
    }

    $pdf->SetXY(10, $y + $cellH);
}

// ---------------------------
// DOCUMENT LIST
// ---------------------------
$docs = [
    '10th / 12th Marks Card' => $d['marks_12_path']             ?? '',
    'Study Certificate'      => $d['study_certificate_path']    ?? '',
    'Transfer Certificate'   => $d['transfer_certificate_path'] ?? '',
    'Photograph'             => $d['photo_path']                ?? '',
    'Signature'              => $d['signature_path']            ?? '',
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

// ============================
// PAGE 1
// ============================

// --- College Name ---
$pdf->SetFont('helvetica', 'B', 13);
$pdf->Cell(0, 7, 'VIJAYA VITTALA INSTITUTE OF TECHNOLOGY', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(0, 5, "35/1, Dodda Gubbi Post, Hennur–Bagalur Road,\nThanisandra, Bengaluru, Karnataka – 560077", 0, 'C');
$pdf->Ln(2);

// Divider
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(3);

// App No & Date
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(95, 6, 'APPLICATION NO: ' . $d['application_id'], 0, 0, 'L');
$pdf->Cell(95, 6, 'DATE & TIME: ' . date('d-m-Y H:i:s', strtotime($d['created_at'])), 0, 1, 'R');
$pdf->Ln(3);

// ---------------------------
// PHOTO — top right, absolute position
// ---------------------------
$photoX = 172;
$photoY = $pdf->GetY();
$photoW = 28;
$photoH = 35;

$pdf->Rect($photoX, $photoY, $photoW, $photoH);

if (!empty($d['photo_path'])) {
    $tmpPhoto = downloadImageToTemp($d['photo_path']);
    if ($tmpPhoto && file_exists($tmpPhoto)) {
        $ext       = strtolower(pathinfo($tmpPhoto, PATHINFO_EXTENSION));
        $tcpdfType = ($ext === 'jpg' || $ext === 'jpeg') ? 'JPEG' : strtoupper($ext);
        $pdf->Image($tmpPhoto, $photoX + 0.5, $photoY + 0.5, $photoW - 1, $photoH - 1, $tcpdfType);
        unlink($tmpPhoto);
    }
}

// ---------------------------
// PERSONAL INFO TABLE
// Starts BELOW photo box — never overlaps
// ---------------------------
$pdf->SetY($photoY + $photoH + 5);
$pdf->SetX(10);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(30, 80, 160);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 7, 'PERSONAL INFORMATION', 1, 1, 'C', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFillColor(255, 255, 255);

$pdf->SetFont('helvetica', '', 9);
$pdf->SetX(10);

row($pdf, 'STUDENT NAME',      $d['student_name']);
row($pdf, 'GENDER',            $d['gender'],            'RELIGION',        $d['religion']);
row($pdf, 'CATEGORY',          $d['category'],          'SUB CASTE',       $d['sub_caste']);
row($pdf, 'DATE OF BIRTH',     $d['dob'],               'STATE',           $d['state']);
row($pdf, 'AADHAAR NUMBER',    $d['aadhaar_number']);
row($pdf, 'FATHER / GUARDIAN', $d['father_name']);
row($pdf, 'MOTHER NAME',       $d['mother_name']);
row($pdf, 'EMAIL',             $d['email'],             'MOBILE',          $d['mobile']);
row($pdf, 'GUARDIAN MOBILE',   $d['guardian_mobile']);
row($pdf, 'ADDRESS',           $d['permanent_address']);
row($pdf, 'PREV. COLLEGE',     $d['prev_college']);
row($pdf, 'ADMISSION THROUGH', $d['admission_through'], 'ALLOTTED BRANCH', $d['allotted_branch']);
row($pdf, 'PREV. COMBINATION', $d['prev_combination']);

// ---------------------------
// STUDENT COPY CHECKLIST
// ---------------------------
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'ACKNOWLEDGMENT – STUDENT COPY', 0, 1, 'C');

$ay = trim(preg_replace('/\s+/', ' ', $academic_year));
$pdf->SetFont('helvetica', '', 9);
$certifyText = "This is to certify that the following documents have been received from " . $d['student_name'] . " for admission to BE in the Branch " . $d['allotted_branch'] . " for the academic year " . $ay . ".";
$pdf->MultiCell(0, 6, $certifyText, 0, 'L');
$pdf->Ln(3);

// Header row
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(10,  7, 'Sl',       1, 0, 'C', true);
$pdf->Cell(125, 7, 'Document', 1, 0, 'C', true);
$pdf->Cell(55,  7, 'Status',   1, 1, 'C', true);

// Data rows
$pdf->SetFont('helvetica', '', 9);
$i = 1;
foreach ($docs as $doc => $path) {
    $received  = !empty($path);
    $status    = $received ? 'RECEIVED' : 'NOT RECEIVED';
    $fillColor = $received ? [220, 255, 220] : [255, 220, 220];
    $pdf->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
    $pdf->Cell(10,  7, $i++,    1, 0, 'C', true);
    $pdf->Cell(125, 7, $doc,    1, 0, 'L', true);
    $pdf->Cell(55,  7, $status, 1, 1, 'C', true);
}
$pdf->SetFillColor(255, 255, 255);

$pdf->Ln(10);
$pdf->Line(10,  $pdf->GetY(), 85,  $pdf->GetY());
$pdf->Line(125, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(2);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(95,  5, 'Student Signature',  0, 0, 'C');
$pdf->Cell(95,  5, 'Admission Director', 0, 1, 'R');

// ============================
// PAGE 2 – COLLEGE COPY
// ============================
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 13);
$pdf->Cell(0, 7, 'VIJAYA VITTALA INSTITUTE OF TECHNOLOGY', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(0, 5, "35/1, Dodda Gubbi Post, Hennur–Bagalur Road,\nThanisandra, Bengaluru, Karnataka – 560077", 0, 'C');
$pdf->Ln(2);

$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(4);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'ACKNOWLEDGMENT – COLLEGE COPY', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
$certifyText2 = "This is to certify that the following documents have been received from " . $d['student_name'] . " for admission to BE in the Branch " . $d['allotted_branch'] . " for the academic year " . $ay . ".";
$pdf->MultiCell(0, 6, $certifyText2, 0, 'L');
$pdf->Ln(3);

// Header row
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(10,  7, 'Sl',       1, 0, 'C', true);
$pdf->Cell(125, 7, 'Document', 1, 0, 'C', true);
$pdf->Cell(55,  7, 'Status',   1, 1, 'C', true);

// Data rows
$pdf->SetFont('helvetica', '', 9);
$i = 1;
foreach ($docs as $doc => $path) {
    $received  = !empty($path);
    $status    = $received ? 'RECEIVED' : 'NOT RECEIVED';
    $fillColor = $received ? [220, 255, 220] : [255, 220, 220];
    $pdf->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
    $pdf->Cell(10,  7, $i++,    1, 0, 'C', true);
    $pdf->Cell(125, 7, $doc,    1, 0, 'L', true);
    $pdf->Cell(55,  7, $status, 1, 1, 'C', true);
}
$pdf->SetFillColor(255, 255, 255);

$pdf->Ln(10);
$pdf->Line(10,  $pdf->GetY(), 85,  $pdf->GetY());
$pdf->Line(125, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(2);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(95,  5, 'Student Signature',  0, 0, 'C');
$pdf->Cell(95,  5, 'Admission Director', 0, 1, 'R');

// ---------------------------
// OUTPUT PDF
// ---------------------------
$pdf->Output('VVIT_' . $id . '.pdf', 'I');
exit;
