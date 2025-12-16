<?php
// functions.php

use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Fetch current year from external source (not server clock)
 */
function fetch_external_year(): int {
    $url = 'https://worldtimeapi.org/api/timezone/Asia/Kolkata';
    $context = stream_context_create([
        'http' => [
            'timeout' => 5
        ]
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response !== false) {
        $data = json_decode($response, true);
        if (isset($data['datetime'])) {
            return (int) substr($data['datetime'], 0, 4);
        }
    }

    // Fallback (rare case)
    return (int) date('Y');
}

/**
 * Generate next serial number per year
 */
function next_serial_for_year(int $year): string {
    $dir = sys_get_temp_dir() . '/admission_data';

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $file = $dir . "/serial_$year.txt";
    $last = file_exists($file) ? (int) file_get_contents($file) : 0;
    $next = $last + 1;

    file_put_contents($file, (string)$next);
    return str_pad((string)$next, 3, '0', STR_PAD_LEFT);
}

/**
 * Validate and move uploaded file (RENDER SAFE)
 */
function validate_and_move(
    array $file,
    string $destDir,
    array $allowedExt,
    int $maxBytes
): string {

    // Check upload array validity
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new Exception('Invalid file upload data');
    }

    // Handle PHP upload errors explicitly
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception(
            'Upload failed (PHP error code ' . $file['error'] . ') for ' . $file['name']
        );
    }

    // File size check
    if ($file['size'] > $maxBytes) {
        throw new Exception('File too large (max ' . ($maxBytes / 1024 / 1024) . 'MB): ' . $file['name']);
    }

    // Extension validation
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        throw new Exception('Invalid file type: ' . $file['name']);
    }

    // Ensure destination directory exists
    if (!is_dir($destDir)) {
        if (!mkdir($destDir, 0755, true)) {
            throw new Exception('Server could not create upload directory');
        }
    }

    // Sanitize filename
    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $file['name']);
    $targetPath = rtrim($destDir, '/') . '/' . uniqid('upload_', true) . '_' . $safeName;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Server failed to save file: ' . $file['name']);
    }

    return $targetPath;
}

/**
 * Create PDF from HTML using Dompdf
 */
function create_pdf_from_html(string $html, string $outputPath): void {
    $dompdf = new Dompdf();
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->loadHtml($html);
    $dompdf->render();

    file_put_contents($outputPath, $dompdf->output());
}

/**
 * Build Application PDF HTML
 */
function build_application_html(array $record): string {
    $d = $record['data'] ?? [];
    $id = $record['id'] ?? '';

    $photoHtml = '';
    if (
        !empty($record['files']['photo']) &&
        file_exists($record['files']['photo'])
    ) {
        $imgData = base64_encode(file_get_contents($record['files']['photo']));
        $photoHtml = "<img src='data:image/jpeg;base64,$imgData'
                          style='width:110px;height:130px;border:1px solid #000'>";
    }

    return "
<!doctype html>
<html>
<head>
<meta charset='utf-8'>
<style>
body{font-family:Arial, sans-serif;font-size:13px}
h3{margin-bottom:6px}
</style>
</head>
<body>

<table width='100%'>
<tr>
<td>$photoHtml</td>
<td align='right'><strong>ID: $id</strong></td>
</tr>
</table>

<h3>{$d['student_name']}</h3>
<p><b>DOB:</b> {$d['dob']}</p>
<p><b>Mobile:</b> {$d['mobile']}</p>
<p><b>Father:</b> {$d['father_name']}</p>
<p><b>Mother:</b> {$d['mother_name']}</p>
<p><b>Address:</b> {$d['permanent_address']}</p>

<hr>
<h3 style='text-align:center;font-family:Times New Roman'>
Vijay Vittal Institute of Technology
</h3>

</body>
</html>";
}

/**
 * Send email with PDF attachment
 */
function send_email_with_attachment(
    string $to,
    string $name,
    string $id,
    string $pdfPath,
    string $mobile
): bool {

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    if (!file_exists($pdfPath)) {
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = getenv('SMTP_USER');
        $mail->Password = getenv('SMTP_PASS');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = getenv('SMTP_PORT') ?: 587;

        $mail->setFrom(getenv('FROM_EMAIL'), getenv('FROM_NAME'));
        $mail->addAddress($to);
        $mail->addAttachment($pdfPath, $id . '.pdf');

        $mail->isHTML(true);
        $mail->Subject = "VVIT Admission Application - $id";
        $mail->Body = "
            <p>Dear $name,</p>
            <p>Your Application ID: <strong>$id</strong></p>
            <p>Please find the attached application PDF.</p>
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('Mail error: ' . $mail->ErrorInfo);
        return false;
    }
}
