<?php
require_once 'auth.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;

/* ===============================
   STRICT PDF MODE
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
   DOCUMENT STATUS
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
   DB
================================ */
$pdo = get_db();

/* ===============================
   APPLICATION ID
================================ */
$id = $_GET['id'] ?? '';
if ($id === '') die('Invalid Application ID');

/* ===============================
   FETCH DATA
================================ */
$stmt = $pdo->prepare("SELECT * FROM admissions WHERE application_id = :id");
$stmt->execute([':id' => $id]);
$d = $stmt->fetch();
if (!$d) die('Application not found');

/* ===============================
   LOCK PRINT
================================ */
if (empty($d['printed_at'])) {
    $pdo->prepare(
        "UPDATE admissions SET printed_at = NOW() WHERE application_id = :id"
    )->execute([':id' => $id]);
}

/* ===============================
   PREPARE DATA
================================ */
$status = json_decode($d['document_status'], true) ?? [];

$appId      = e($id);
$createdAt  = e($d['created_at']);
$name       = e($d['student_name']);
$religion   = e($d['religion']);
$category   = e($d['category']);
$subCaste   = e($d['sub_caste']);
$dob        = e($d['dob']);
$state      = e($d['state']);
$father     = e($d['father_name']);
$mother     = e($d['mother_name']);
$email      = e($d['email']);
$mobile     = e($d['mobile']);
$gmobile    = e($d['guardian_mobile']);
$address    = e($d['permanent_address']);
$admission  = e($d['admission_through']);
$branch     = e(branch_full_form($d['allotted_branch']));
$prevComb   = e($d['prev_combination']);

$y = (int)date('Y', strtotime($d['created_at']));
$admissionYear = $y . ' - ' . ($y + 1);

/* ===============================
   PHOTO
================================ */
if (!empty($d['photo_path']) && file_exists($d['photo_path'])) {
    $img = base64_encode(file_get_contents($d['photo_path']));
    $photoHtml = "<img src='data:image/jpeg;base64,$img' style='width:100px;height:120px;border:1px solid #000'>";
} else {
    $photoHtml = "<div style='width:100px;height:120px;border:1px solid #000'></div>";
}

/* ===============================
   QR PLACEHOLDER
================================ */
$qrHtml = "<div style='width:100px;height:100px;border:1px solid #000;
text-align:center;font-size:10px;line-height:100px'>QR</div>";

/* ===============================
   DOC STATUS
================================ */
$doc10 = doc_status($status,'marks_10');
$doc12 = doc_status($status,'marks_12');
$docSC = doc_status($status,'study_certificate');
$docTC = doc_status($status,'transfer_certificate');
$docP  = doc_status($status,'photo');

/* ===============================
   BUILD HTML (NO HEREDOC)
================================ */
$html  = '<html><head><meta charset="utf-8">';
$html .= '<style>
body{font-family:Arial;font-size:12px}
h1,h3{text-align:center}
table{width:100%;border-collapse:collapse}
td,th{border:1px solid #000;padding:6px}
.no-border td{border:none}
.section{background:#e9d5ff;font-weight:bold}
.cut{border-top:1px dashed #000;margin:14px 0}
.right{text-align:right}
.page-break{page-break-after:always}
</style></head><body>';

$html .= '<h1>VIJAYA VITTALA INSTITUTE OF TECHNOLOGY</h1>';

$html .= '<table class="no-border"><tr>
<td>'.$qrHtml.'</td>
<td><b>APPLICATION NO:</b> '.$appId.'<br><b>DATE:</b> '.$createdAt.'</td>
<td class="right">'.$photoHtml.'</td>
</tr></table>';

$html .= '<table>
<tr><td colspan="4" class="section">PERSONAL INFORMATION</td></tr>
<tr><td>STUDENT NAME</td><td colspan="3">'.$name.'</td></tr>
<tr><td>RELIGION</td><td>'.$religion.'</td><td>CATEGORY</td><td>'.$category.'</td></tr>
<tr><td>SUB CASTE</td><td colspan="3">'.$subCaste.'</td></tr>
<tr><td>DOB</td><td>'.$dob.'</td><td>STATE</td><td>'.$state.'</td></tr>
<tr><td>FATHER</td><td colspan="3">'.$father.'</td></tr>
<tr><td>MOTHER</td><td colspan="3">'.$mother.'</td></tr>
<tr><td>EMAIL</td><td>'.$email.'</td><td>MOBILE</td><td>'.$mobile.'</td></tr>
<tr><td>GUARDIAN MOBILE</td><td colspan="3">'.$gmobile.'</td></tr>
<tr><td>ADDRESS</td><td colspan="3">'.$address.'</td></tr>
<tr><td>ADMISSION</td><td>'.$admission.'</td><td>BRANCH</td><td>'.$branch.'</td></tr>
<tr><td>PREVIOUS COMBINATION</td><td colspan="3">'.$prevComb.'</td></tr>
</table>';

$html .= '<div class="cut"></div><h3>DOCUMENT CHECKLIST</h3>';

$html .= '<table>
<tr><th>Sl</th><th>Document</th><th>Status</th></tr>
<tr><td>1</td><td>10th Marks Card</td><td>'.$doc10.'</td></tr>
<tr><td>2</td><td>12th / Diploma Marks Card</td><td>'.$doc12.'</td></tr>
<tr><td>3</td><td>Study Certificate</td><td>'.$docSC.'</td></tr>
<tr><td>4</td><td>Transfer Certificate</td><td>'.$docTC.'</td></tr>
<tr><td>5</td><td>Photograph</td><td>'.$docP.'</td></tr>
</table>';

$html .= '<p>This is to certify admission to <b>'.$branch.'</b> for academic year <b>'.$admissionYear.'</b>.</p>';

$html .= '</body></html>';

/* ===============================
   PDF OUTPUT
================================ */
$pdf = new Dompdf();
$pdf->setPaper('A4', 'portrait');
$pdf->loadHtml($html);
$pdf->render();
$pdf->stream("VVIT_Application_$id.pdf", ['Attachment' => true]);
exit;
