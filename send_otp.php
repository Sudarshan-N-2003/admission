<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

$pdo = get_db();

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  echo json_encode(['status'=>'error','msg'=>'Invalid email']);
  exit;
}

$otp = rand(100000, 999999);
$expiry = date('Y-m-d H:i:s', time() + 300); // 5 mins

$pdo->prepare("
  INSERT INTO email_otps (email, otp, expires_at)
  VALUES (:email,:otp,:exp)
  ON CONFLICT (email)
  DO UPDATE SET otp=:otp, expires_at=:exp
")->execute([
  ':email'=>$email,
  ':otp'=>$otp,
  ':exp'=>$expiry
]);

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->SMTPDebug = 2;
$mail->Debugoutput = 'error_log';
$mail->Host = getenv('SMTP_HOST');
$mail->SMTPAuth = true;
$mail->Username = getenv('SMTP_USER');
$mail->Password = getenv('SMTP_PASS');
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = getenv('SMTP_PORT') ?: 587;

$mail->setFrom(getenv('FROM_EMAIL'), getenv('FROM_NAME'));
$mail->addAddress($email);
$mail->Subject = "VVIT Admission Email Verification OTP";
$mail->Body = "Your OTP is: $otp\nValid for 5 minutes.";

$mail->send();

echo json_encode(['status'=>'ok']);
