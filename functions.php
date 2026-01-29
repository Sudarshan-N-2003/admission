<?php
declare(strict_types=1);

/* =====================================================
   AUTOLOAD
===================================================== */
require_once __DIR__ . '/vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* =====================================================
   R2 CLIENT (S3 COMPATIBLE)
===================================================== */
function get_r2_client(): S3Client
{
    return new S3Client([
        'version'  => 'latest',
        'region'   => 'auto',
        'endpoint' => getenv('R2_ENDPOINT'),
        'credentials' => [
            'key'    => getenv('R2_ACCESS_KEY'),
            'secret' => getenv('R2_SECRET_KEY'),
        ],
        'use_path_style_endpoint' => true,
    ]);
}

/* =====================================================
   UPLOAD FILE TO CLOUDFLARE R2
===================================================== */
function upload_to_r2(
    array $file,
    string $folder,
    array $allowedExt,
    int $maxBytes
): string {

    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Upload failed for {$file['name']}");
    }

    if ($file['size'] > $maxBytes) {
        throw new Exception("File too large: {$file['name']}");
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        throw new Exception("Invalid file type: {$file['name']}");
    }

    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
    $key = trim($folder, '/') . '/' . uniqid('file_', true) . '_' . $safeName;

    try {
        $client = get_r2_client();
        $client->putObject([
            'Bucket'      => getenv('R2_BUCKET'),
            'Key'         => $key,
            'SourceFile'  => $file['tmp_name'],
            'ContentType' => mime_content_type($file['tmp_name']),
        ]);
    } catch (AwsException $e) {
        throw new Exception('R2 upload error: ' . $e->getMessage());
    }

    return rtrim(getenv('R2_PUBLIC_URL'), '/') . '/' . $key;
}

/* =====================================================
   FETCH CURRENT ACADEMIC YEAR (SAFE)
===================================================== */
function get_academic_year(): string
{
    $year = (int)date('Y');
    return $year . ' - ' . ($year + 1);
}

/* =====================================================
   GENERATE APPLICATION SERIAL
===================================================== */
function generate_application_id(): string
{
    $year = date('y');
    $dir = sys_get_temp_dir() . '/vvit_serial';

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $file = "$dir/$year.txt";
    $last = file_exists($file) ? (int)file_get_contents($file) : 0;
    $next = $last + 1;
    file_put_contents($file, (string)$next);

    return '1VJ' . $year . str_pad((string)$next, 3, '0', STR_PAD_LEFT);
}

/* =====================================================
   CREATE PDF FROM HTML (DOMPDF)
===================================================== */
function create_pdf(string $html, string $outputPath): void
{
    $dompdf = new Dompdf([
        'isRemoteEnabled' => true
    ]);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->loadHtml($html);
    $dompdf->render();
    file_put_contents($outputPath, $dompdf->output());
}

/* =====================================================
   SEND EMAIL WITH PDF ATTACHMENT
===================================================== */
function send_application_email(
    string $to,
    string $name,
    string $applicationId,
    string $pdfPath
): void {

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = getenv('SMTP_HOST');
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('SMTP_USER');
        $mail->Password   = getenv('SMTP_PASS');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = getenv('SMTP_PORT') ?: 587;

        $mail->setFrom(getenv('FROM_EMAIL'), getenv('FROM_NAME'));
        $mail->addAddress($to, $name);
        $mail->addAttachment($pdfPath);

        $mail->isHTML(true);
        $mail->Subject = "VVIT Admission Application - $applicationId";
        $mail->Body = "
            <p>Dear <b>$name</b>,</p>
            <p>Your admission application has been submitted successfully.</p>
            <p><b>Application ID:</b> $applicationId</p>
            <p>Please find the attached PDF.</p>
            <br>
            <p>Regards,<br>VVIT Admissions</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log('Mail error: ' . $mail->ErrorInfo);
    }
}
