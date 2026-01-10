<?php
require_once 'auth.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;

/* ===============================
   STRICT MODE FOR PDF
================================ */
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

/* ===============================
   ESCAPE HELPER
================================ */
function e($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

/* ===============================
   DOCUMENT STATUS HELPER
================================ */
function doc_status(array $status, string $key): string {
    return strtoupper($status[$key] ?? 'PENDING');
}

/* ===============================
   BRANCH FULL FORM
================================ */
function branch_full_form(string $code): string {
    $map = [
        'CSE' => 'Computer Science & Engineering',
        'AIML' => 'Artificial Intelligence & Machine Learning',
        'CS (AIML)' => 'Computer Science (AI & ML)',
        'CS (DS)' => 'Computer Science (Data Science)',
        'EC' => 'Electronics & Communication Engineering',
        'ME' => 'Mechanical Engineering',
        'CV' => 'Civil Engineering'
    ];
    return $map[$code] ?? $code;
}

/* ===============================
   DB CONNECTION
================================ */
$pdo = get_db();

/* ===============================
   APPLICATION ID
================================ */
$id = $_GET['id'] ?? '';
if ($id === '') {
    die('Invalid Application ID');
}

/* ===============================
   FETCH APPLICATION
================================ */
$stmt = $pdo->prepare("SELECT * FROM admissions WHERE application_id = :id");
$stmt->execute([':id' => $id]);
$d = $stmt->fetch();

if (!$d) {
    die('Application not found');
}

/* ===============================
   LOCK PRINT (ONLY ONCE)
================================ */
if (empty($d['printed_at'])) {
    $pdo->prepare(
        "UPDATE admissions SET printed_at = NOW() WHERE application_id = :id"
    )->execute([':id' => $id]);
}

/* ===============================
   DOCUMENT STATUS
================================ */
$status = json_decode($d['document_status'], true) ?? [];

/* ===============================
   ADMISSION YEAR
================================ */
$y = (int)date('Y', strtotime($d['created_at']));
$admissionYear = $y . ' - ' . ($y + 1);

/* ===============================
   PHOTO
================================ */
if (!empty($d['photo_path']) && file_exists($d['photo_path'])) {
    $img = base64_encode(file_get_contents($d['photo_path']));
    $photoHtml = "<img src='data:image/jpeg;base64,$img'
        style='width:100px;height:120px;border:1px solid #000'>";
} else {
    $photoHtml = "<div style='width:100px;height:120px;border:1px solid #000'></div>";
}

/* ===============================
   QR PLACEHOLDER (OPTIONAL)
================================ */
$qrHtml = "<div style='width:100px;height:100px;border:1px solid #000;
            text-align:center;font-size:10px;padding-top:40px'>
            QR CODE
           </div>";

/* ===============================
   GENDER CHECKBOX
================================ */
$gMale   = ($d['gender'] === 'MALE') ? '☑' : '☐';
$gFemale = ($d['gender'] === 'FEMALE') ? '☑' : '☐';

/* ===============================
   PDF HTML
================================ */
$appId      = e($id);
$createdAt = e($d['created_at']);
$name      = e($d['student_name']);
$religion  = e($d['religion']);
$category  = e($d['category']);
$subCaste  = e($d['sub_caste']);
$dob       = e($d['dob']);
$state     = e($d['state']);
$father    = e($d['father_name']);
$mother    = e($d['mother_name']);
$email     = e($d['email']);
$mobile    = e($d['mobile']);
$gmobile   = e($d['guardian_mobile']);
$address   = e($d['permanent_address']);
$admission = e($d['admission_through']);
$branch    = e(branch_full_form($d['allotted_branch']));
$prevComb  = e($d['prev_combination']);

$doc10  = doc_status($status, 'marks_10');
$doc12  = doc_status($status, 'marks_12');
$docSC  = doc_status($status, 'study_certificate');
$docTC  = doc_status($status, 'transfer_certificate');
$docP   = doc_status($status, 'photo');







$html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
body { font-family: Arial; font-size: 12px; }
h1, h3 { text-align: center; margin: 6px 0; }
table { width: 100%; border-collapse: collapse; }
td, th { border: 1px solid #000; padding: 6px; vertical-align: top; }
.no-border td { border: none; }
.section { background: #e9d5ff; font-weight: bold; }
.center { text-align: center; }
.right { text-align: right; }
.cut { border-top: 1px dashed #000; margin: 16px 0; }
.signature { margin-top: 40px; }
.page-break { page-break-after: always; }
</style>
</head>

<body>

<h1>VIJAYA VITTALA INSTITUTE OF TECHNOLOGY</h1>
<p class="center">
35/1, Dodda Gubbi Post, Hennur–Bagalur Road,<br>
Thanisandra, Bengaluru – 560077
</p>

<table class="no-border">
<tr>
<td width="25%">$qrHtml</td>
<td width="50%">
<b>APPLICATION NO:</b> {e($id)}<br>
<b>DATE & TIME:</b> {e($d['created_at'])}
</td>
<td width="25%" class="right">$photoHtml</td>
</tr>
</table>

<table>
<tr><td colspan="4" class="section">PERSONAL INFORMATION</td></tr>

<tr><td><b>STUDENT NAME</b></td><td colspan="3">{e($d['student_name'])}</td></tr>

<tr>
<td><b>GENDER</b></td>
<td colspan="3">$gFemale Female &nbsp;&nbsp; $gMale Male</td>
</tr>

<tr>
<td><b>RELIGION</b></td><td>{e($d['religion'])}</td>
<td><b>CATEGORY</b></td><td>{e($d['category'])}</td>
</tr>

<tr><td><b>SUB CASTE</b></td><td colspan="3">{e($d['sub_caste'])}</td></tr>

<tr>
<td><b>DOB</b></td><td>{e($d['dob'])}</td>
<td><b>STATE</b></td><td>{e($d['state'])}</td>
</tr>

<tr><td><b>FATHER / GUARDIAN</b></td><td colspan="3">{e($d['father_name'])}</td></tr>
<tr><td><b>MOTHER NAME</b></td><td colspan="3">{e($d['mother_name'])}</td></tr>

<tr>
<td><b>EMAIL</b></td><td>{e($d['email'])}</td>
<td><b>MOBILE</b></td><td>{e($d['mobile'])}</td>
</tr>

<tr>
<td><b>GUARDIAN MOBILE</b></td><td colspan="3">{e($d['guardian_mobile'])}</td>
</tr>

<tr>
<td><b>PERMANENT ADDRESS</b></td><td colspan="3">{e($d['permanent_address'])}</td>
</tr>

<tr>
<td><b>ADMISSION THROUGH</b></td><td>{e($d['admission_through'])}</td>
<td><b>ALLOTTED BRANCH</b></td>
<td>{e(branch_full_form($d['allotted_branch']))}</td>
</tr>

<tr>
<td><b>PREVIOUS COMBINATION</b></td><td colspan="3">{e($d['prev_combination'])}</td>
</tr>
</table>

<div class="cut"></div>

<h3>DOCUMENT CHECKLIST</h3>

<table>
<tr><th>Sl</th><th>Document</th><th>Status</th></tr>
<tr><td>1</td><td>10th Marks Card</td><td>{doc_status($status,'marks_10')}</td></tr>
<tr><td>2</td><td>12th / Diploma Marks Card</td><td>{doc_status($status,'marks_12')}</td></tr>
<tr><td>3</td><td>Study Certificate</td><td>{doc_status($status,'study_certificate')}</td></tr>
<tr><td>4</td><td>Transfer Certificate</td><td>{doc_status($status,'transfer_certificate')}</td></tr>
<tr><td>5</td><td>Photograph</td><td>{doc_status($status,'photo')}</td></tr>
</table>

<table class="no-border signature">
<tr>
<td>Student Signature</td>
<td class="right">Admission Director</td>
</tr>
</table>

<div class="page-break"></div>

<h3>ACKNOWLEDGMENT – COLLEGE COPY</h3>

<p>
Received the above documents from
<b>{e($d['student_name'])}</b> for admission to
<b>BE – {e(branch_full_form($d['allotted_branch']))}</b>
for the academic year <b>$admissionYear</b>.
</p>

<table>
<tr><th>Sl</th><th>Document</th><th>Status</th></tr>
<tr><td>1</td><td>10th Marks Card</td><td>{doc_status($status,'marks_10')}</td></tr>
<tr><td>2</td><td>12th / Diploma Marks Card</td><td>{doc_status($status,'marks_12')}</td></tr>
<tr><td>3</td><td>Study Certificate</td><td>{doc_status($status,'study_certificate')}</td></tr>
<tr><td>4</td><td>Transfer Certificate</td><td>{doc_status($status,'transfer_certificate')}</td></tr>
<tr><td>5</td><td>Photograph</td><td>{doc_status($status,'photo')}</td></tr>
</table>

<table class="no-border signature">
<tr>
<td>Student Signature</td>
<td class="right">Admission Director</td>
</tr>
</table>

</body>
</html>
HTML;

/* ===============================
   GENERATE PDF
================================ */
$pdf = new Dompdf();
$pdf->setPaper('A4', 'portrait');
$pdf->loadHtml($html);
$pdf->render();
$pdf->stream("VVIT_Application_$id.pdf", ["Attachment" => true]);
exit;
