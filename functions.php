<?php
// functions.php

use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* =========================================================
   TIME + APPLICATION ID HELPERS
========================================================= */

/**
 * Fetch current year from external time API (Asia/Kolkata)
 */
function fetch_external_year(): int {
    $url = 'https://worldtimeapi.org/api/timezone/Asia/Kolkata';

    $ctx = stream_context_create([
        'http' => ['timeout' => 5]
    ]);

    $res = @file_get_contents($url, false, $ctx);
    if ($res) {
        $json = json_decode($res, true);
        if (!empty($json['datetime'])) {
            return (int) substr($json['datetime'], 0, 4);
        }
    }

    return (int) date('Y');
}

/**
 * Generate next serial number per year (safe, file-based)
 * Example: 001, 002, 003
 */
function next_serial_for_year(int $year): string {
    $dir = sys_get_temp_dir() . '/admission_serials';

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $file = $dir . "/serial_$year.txt";
    $last = file_exists($file) ? (int) file_get_contents($file) : 0;
    $next = $last + 1;

    file_put_contents($file, (string)$next);
    return str_pad((string)$next, 3, '0', STR_PAD_LEFT);
}

/* =========================================================
   CLOUDFLARE R2 UPLOAD (IMPORTANT)
========================================================= */

/**
 * Upload file to Cloudflare R2 and return PUBLIC URL
 * ⚠️ NEVER return local path
 */
function upload_to_r2(array $file, string $folder, string $filename): string {

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload failed: ' . $file['name']);
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception('File exceeds 10MB limit: ' . $file['name']);
    }

    $key = trim($folder, '/') . '/' . $filename;

    $s3 = get_r2_client(); // you already have this
    $s3->putObject([
        'Bucket'      => getenv('R2_BUCKET'),
        'Key'         => $key,
        'Body'        => fopen($file['tmp_name'], 'rb'),
        'ContentType' => $file['type']
    ]);

    // ✅ THIS is what must be saved in DB
    return rtrim(getenv('R2_PUBLIC_URL'), '/') . '/' . $key;
}

/* =========================================================
   PDF GENERATION (DOMPDF – HTML ONLY)
========================================================= */

/**
 * Create PDF from HTML and save to path
 */
function create_pdf_from_html(string $html, string $outputPath): void {
    $dompdf = new Dompdf();
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->loadHtml($html);
    $dompdf->render();

    file_put_contents($outputPath, $dompdf->output());
}

/**
 * Build application PDF HTML
 * ⚠️ Uses IMAGE URLs (R2), not local paths
 */
function build_application_pdf_html(array $d): string {

    $photoHtml = '';
    if (!empty($d['photo_url'])) {
        $photoHtml = "
        <div style='position:absolute;right:30px;top:110px;
                    width:110px;height:130px;border:1px solid #000'>
            <img src='{$d['photo_url']}' style='width:110px;height:130px'>
        </div>";
    }

    $year = date('Y', strtotime($d['created_at']));
    $academicYear = $year . ' - ' . ($year + 1);

    return <<<HTML
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
body{font-family:Arial;font-size:12px}
table{border-collapse:collapse;width:100%}
td,th{border:1px solid #000;padding:5px}
.title{text-align:center;font-weight:bold;font-size:14px}
.small{text-align:center;font-size:11px}
.section{margin-top:10px;font-weight:bold}
</style>
</head>
<body>

<div class="title">VIJAYA VITTALA INSTITUTE OF TECHNOLOGY</div>
<div class="small">
35/1, Dodda Gubbi Post, Hennur–Bagalur Road,<br>
Thanisandra, Bengaluru – 560077
</div>

<br>

<table>
<tr>
<td><b>APPLICATION NO</b></td><td>{$d['application_id']}</td>
<td><b>DATE & TIME</b></td><td>{$d['created_at']}</td>
</tr>
</table>

$photoHtml

<div class="section">PERSONAL INFORMATION</div>
<table>
<tr><td>STUDENT NAME</td><td>{$d['student_name']}</td></tr>
<tr><td>GENDER</td><td>{$d['gender']}</td></tr>
<tr><td>RELIGION</td><td>{$d['religion']}</td></tr>
<tr><td>CATEGORY</td><td>{$d['category']}</td></tr>
<tr><td>SUB CASTE</td><td>{$d['sub_caste']}</td></tr>
<tr><td>DOB</td><td>{$d['dob']}</td></tr>
<tr><td>STATE</td><td>{$d['state']}</td></tr>
<tr><td>FATHER / GUARDIAN</td><td>{$d['father_name']}</td></tr>
<tr><td>MOTHER NAME</td><td>{$d['mother_name']}</td></tr>
<tr><td>EMAIL</td><td>{$d['email']}</td></tr>
<tr><td>MOBILE</td><td>{$d['mobile']}</td></tr>
<tr><td>GUARDIAN MOBILE</td><td>{$d['guardian_mobile']}</td></tr>
<tr><td>PERMANENT ADDRESS</td><td>{$d['permanent_address']}</td></tr>
<tr><td>ADMISSION THROUGH</td><td>{$d['admission_through']}</td></tr>
<tr><td>ALLOTTED BRANCH</td><td>{$d['allotted_branch']}</td></tr>
<tr><td>PREVIOUS COMBINATION</td><td>{$d['prev_combination']}</td></tr>
</table>

<br>

<b>ACKNOWLEDGMENT – STUDENT COPY</b><br>
This is to certify that the following documents have been received from
<b>{$d['student_name']}</b> for admission to BE in
<b>{$d['allotted_branch']}</b> for the academic year
<b>$academicYear</b>.

<table>
<tr><th>Sl</th><th>Document</th><th>Status</th></tr>
<tr><td>1</td><td>10th Marks Card</td><td>RECEIVED</td></tr>
<tr><td>2</td><td>12th / Diploma Marks Card</td><td>RECEIVED</td></tr>
<tr><td>3</td><td>Study Certificate</td><td>RECEIVED</td></tr>
<tr><td>4</td><td>Transfer Certificate</td><td>RECEIVED</td></tr>
<tr><td>5</td><td>Photograph</td><td>RECEIVED</td></tr>
</table>

<br><br>
<table style="border:none">
<tr>
<td style="border:none">Student Signature</td>
<td style="border:none;text-align:right">Admission Director</td>
</tr>
</table>

</body>
</html>
HTML;
}

/* =========================================================
   EMAIL SENDING
========================================================= */

function send_email_with_attachment(
    string $to,
    string $name,
    string $applicationId,
    string $pdfPath
): bool {

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
    if (!file_exists($pdfPath)) return false;

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = getenv('SMTP_HOST');
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('SMTP_USER');
        $mail->Password   = getenv('SMTP_PASS');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = getenv('SMTP_PORT') ?: 587;

        $mail->setFrom(getenv('FROM_EMAIL'), getenv('FROM_NAME'));
        $mail->addAddress($to, $name);
        $mail->addAttachment($pdfPath, $applicationId . '.pdf');

        $mail->isHTML(true);
        $mail->Subject = "VVIT Admission Application – $applicationId";
        $mail->Body = "
            <p>Dear <b>$name</b>,</p>
            <p>Your admission application has been submitted successfully.</p>
            <p><b>Application ID:</b> $applicationId</p>
            <p>Please find the attached PDF.</p>
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('Mail Error: ' . $e->getMessage());
        return false;
    }
}
