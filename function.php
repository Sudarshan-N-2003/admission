<?php
// functions.php - helper utilities

use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function fetch_external_year() {
    // Use worldtimeapi as an external reliable source for current year (Asia/Kolkata)
    $url = 'http://worldtimeapi.org/api/timezone/Asia/Kolkata';
    $ctx = stream_context_create(['http'=>['timeout'=>4]]);
    $res = @file_get_contents($url, false, $ctx);
    if (!$res) return intval(date('Y'));
    $data = json_decode($res, true);
    if (isset($data['datetime'])) return intval(substr($data['datetime'],0,4));
    return intval(date('Y'));
}

function next_serial_for_year($yy) {
    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $file = "$dir/serial_$yy.txt";
    if (!file_exists($file)) file_put_contents($file, "0");
    $n = intval(file_get_contents($file)) + 1;
    file_put_contents($file, (string)$n);
    return str_pad($n, 3, '0', STR_PAD_LEFT);
}

function validate_and_move($fileArray, $destDir, $allowedExt = [], $maxBytes = 1048576) {
    if ($fileArray['error'] !== UPLOAD_ERR_OK) throw new Exception('Upload error: ' . $fileArray['name']);
    $ext = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt)) throw new Exception('Invalid file type for ' . $fileArray['name']);
    if ($fileArray['size'] > $maxBytes) throw new Exception('File too large: ' . $fileArray['name']);
    $safe = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', basename($fileArray['name']));
    $target = rtrim($destDir, '/') . '/' . uniqid() . '_' . $safe;
    if (!move_uploaded_file($fileArray['tmp_name'], $target)) throw new Exception('Unable to move uploaded file');
    return $target;
}

function create_pdf_from_html($html, $outPath) {
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->load_html($html);
    $dompdf->render();
    file_put_contents($outPath, $dompdf->output());
}

function build_application_html($record) {
    // keep layout simple and printable
    $d = $record['data'];
    $id = $record['id'];
    $student_name = htmlspecialchars($d['student_name'] ?? '');
    $dob = htmlspecialchars($d['dob'] ?? '');
    $father = htmlspecialchars($d['father_name'] ?? '');
    $mother = htmlspecialchars($d['mother_name'] ?? '');
    $mobile = htmlspecialchars($d['mobile'] ?? '');
    $permanent = htmlspecialchars($d['permanent_address'] ?? '');
    $prev_college = htmlspecialchars($d['prev_college'] ?? '');
    $branch = htmlspecialchars($d['allotted_branch'] ?? '');
    $year = substr((string)fetch_external_year(), -2);

    $photo_html = '';
    if (!empty($record['files']['photo']) && file_exists($record['files']['photo'])) {
        $bin = base64_encode(file_get_contents($record['files']['photo']));
        $photo_html = "<img src='data:image/jpeg;base64,$bin' style='width:110px;height:130px;object-fit:cover;border:1px solid #333'/>";
    }

    $admyear = '20' . $year . '-' . (intval('20'.$year) + 1);

    $html = "<!doctype html><html><head><meta charset='utf-8'><style>
        body{font-family: Arial, sans-serif}
        .left{width:60%;float:left;padding:10px}
        .right{width:38%;float:right;padding:10px;border-left:1px solid #000}
        h1.center{font-family: 'Times New Roman', Times, serif;text-align:center}
        table{width:100%;border-collapse:collapse}
        td,th{padding:6px;border:1px solid #ddd}
      </style></head><body>
      <div class='left'>
        <div style='display:flex;justify-content:space-between;align-items:center'>
          <div>$photo_html</div>
          <div style='text-align:right;font-weight:bold'>ID: $id</div>
        </div>
        <h2>$student_name</h2>
        <p><strong>DOB:</strong> $dob &nbsp; <strong>Mobile:</strong> $mobile</p>
        <p><strong>Father:</strong> $father</p>
        <p><strong>Mother:</strong> $mother</p>
        <p><strong>Permanent Address:</strong> $permanent</p>
        <p><strong>Previous College:</strong> $prev_college</p>
        <p><strong>Allotted Branch:</strong> $branch &nbsp; <strong>Admission Year:</strong> $admyear</p>
        <h3>Submitted Documents</h3>
        <table>
          <tr><th>Sl.</th><th>Submitted documents</th><th>Submitted date</th></tr>
          <tr><td>1</td><td>Marks cards</td><td></td></tr>
          <tr><td>2</td><td>Transfer Certificate</td><td></td></tr>
          <tr><td>3</td><td>Study certificate</td><td></td></tr>
          <tr><td>4</td><td>Cast & income</td><td></td></tr>
          <tr><td>5</td><td>Passport size photo</td><td></td></tr>
        </table>
      </div>
      <div class='right'>
        <h1 class='center'>Vijay Vittal Institute of Technology</h1>
        <p><strong>College Copy</strong></p>
        <p>Student name: $student_name | Branch: $branch | Admission: $admyear</p>
        <table>
          <tr><th>Sl.</th><th>Submitted documents</th><th>Submitted date</th></tr>
          <tr><td>1</td><td>Marks cards</td><td></td></tr>
          <tr><td>2</td><td>Transfer Certificate</td><td></td></tr>
          <tr><td>3</td><td>Study certificate</td><td></td></tr>
          <tr><td>4</td><td>Cast & income</td><td></td></tr>
          <tr><td>5</td><td>Passport size photo</td><td></td></tr>
        </table>
      </div>
      <div style='clear:both'></div>
    </body></html>";

    return $html;
}

function send_email_with_attachment($toEmail, $studentName, $generated_id, $pdfPath, $mobile) {
    $smtpHost = getenv('SMTP_HOST');
    $smtpUser = getenv('SMTP_USER');
    $smtpPass = getenv('SMTP_PASS');
    $smtpPort = getenv('SMTP_PORT') ?: 587;
    $fromEmail = getenv('FROM_EMAIL') ?: 'noreply@yourdomain.com';
    $fromName = getenv('FROM_NAME') ?: 'VVIT Admissions';

    if (!$toEmail || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) return false;
    if (!file_exists($pdfPath)) return false;

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $smtpPort;

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail);
        $mail->addAttachment($pdfPath, $generated_id . '_application.pdf');

        $wa_link = "https://wa.me/91" . preg_replace('/[^0-9]/','',$mobile) . "?text=" . urlencode("Your application ID: $generated_id");

        $mail->isHTML(true);
        $mail->Subject = "VVIT Application — ID $generated_id";
        $mail->Body = "<p>Dear $studentName,</p><p>Your Application ID: <strong>$generated_id</strong></p><p>PDF attached.</p><p><a href='$wa_link'>Send ID to WhatsApp</a></p><p>Regards,<br/>Admissions Team</p>";
        $mail->AltBody = "Your Application ID: $generated_id\nPlease find attached PDF.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mail error: ' . $mail->ErrorInfo);
        return false;
    }
}
