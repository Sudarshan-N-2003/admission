<?php
require_once __DIR__ . '/db.php';

$pdo = get_db();

$email = $_POST['email'] ?? '';
$otp   = $_POST['otp'] ?? '';

$stmt = $pdo->prepare("
  SELECT 1 FROM email_otps
  WHERE email=:email
    AND otp=:otp
    AND expires_at >= NOW()
");
$stmt->execute([
  ':email'=>$email,
  ':otp'=>$otp
]);

if ($stmt->fetch()) {
  $pdo->prepare("DELETE FROM email_otps WHERE email=:email")
      ->execute([':email'=>$email]);

  echo json_encode(['status'=>'ok']);
} else {
  echo json_encode(['status'=>'error','msg'=>'Invalid or expired OTP']);
}
