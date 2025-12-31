<?php
require_once __DIR__ . '/db.php';

$pdo = get_db();

$mobile = $_POST['mobile'] ?? '';
$email  = $_POST['email'] ?? '';

if (!$mobile && !$email) {
  echo json_encode(['status' => 'ok']);
  exit;
}

$stmt = $pdo->prepare("
  SELECT 1 FROM admissions
  WHERE mobile = :mobile OR email = :email
  LIMIT 1
");

$stmt->execute([
  ':mobile' => $mobile,
  ':email'  => $email
]);

if ($stmt->fetch()) {
  echo json_encode([
    'status' => 'exists',
    'message' => 'Already registered'
  ]);
} else {
  echo json_encode([
    'status' => 'ok'
  ]);
}
