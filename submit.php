<?php
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

try {

  $pdo = get_db();

  /* -------------------------------
     REQUIRED FIELDS
  -------------------------------- */
  $required = [
    'student_name','dob','gender','religion',
    'category','sub_caste',
    'father_name','mother_name',
    'email','mobile','guardian_mobile',
    'nationality','state','permanent_address',
    'prev_college','prev_combination',
    'admission_through'
  ];

  foreach ($required as $f) {
    if (empty($_POST[$f] ?? '')) {
      throw new Exception("Missing required field: $f");
    }
  }

  /* -------------------------------
     NORMALIZE INPUT
  -------------------------------- */
  $d = [];
  foreach ($_POST as $k => $v) {
    $d[$k] = is_string($v) ? strtoupper(trim($v)) : $v;
  }

  /* -------------------------------
     ADMISSION TYPE LOGIC
  -------------------------------- */
  if ($d['admission_through'] === 'KEA') {
    foreach (['cet_number','cet_rank','seat_allotted','allotted_branch'] as $f) {
      if (empty($d[$f])) throw new Exception("Missing KEA field: $f");
    }
  } else {
    if (empty($d['allotted_branch_management'])) {
      throw new Exception("Missing Management branch");
    }
    $d['allotted_branch'] = $d['allotted_branch_management'];
    $d['seat_allotted'] = 'MANAGEMENT';
  }

  /* -------------------------------
     DUPLICATE CHECK
  -------------------------------- */
  $chk = $pdo->prepare(
    "SELECT 1 FROM admissions WHERE mobile = :m OR email = :e"
  );
  $chk->execute([
    ':m' => $d['mobile'],
    ':e' => $d['email']
  ]);
  if ($chk->fetch()) {
    throw new Exception("Mobile or Email already registered");
  }

  /* -------------------------------
     APPLICATION ID
  -------------------------------- */
  $year = fetch_external_year();
  $serial = next_serial_for_year($year);
  $appId = '1VJ' . substr($year, -2) . $serial;

  /* -------------------------------
     UPLOAD DIRECTORY
  -------------------------------- */
  $base = sys_get_temp_dir() . "/admission/$appId";
  if (!is_dir($base)) mkdir($base, 0777, true);

  $max = 2 * 1024 * 1024;

  $paths = [];
  $paths['photo'] = validate_and_move($_FILES['passport_photo'],$base,['jpg','jpeg','png'],$max);
  $paths['signature'] = validate_and_move($_FILES['student_signature'],$base,['jpg','jpeg','png'],$max);
  $paths['marks_12'] = validate_and_move($_FILES['marks_12'],$base,['pdf'],$max);
  $paths['study'] = validate_and_move($_FILES['study_certificate'],$base,['pdf'],$max);
  $paths['tc'] = validate_and_move($_FILES['transfer_certificate'],$base,['pdf'],$max);

  if ($d['admission_through'] === 'KEA') {
    $paths['kea'] = validate_and_move($_FILES['kea_acknowledgement'],$base,['pdf'],$max);
  } else {
    $paths['mgmt'] = validate_and_move($_FILES['management_receipt'],$base,['pdf'],$max);
  }

  /* -------------------------------
     INSERT INTO DB (CORE PART)
  -------------------------------- */
  $stmt = $pdo->prepare("
    INSERT INTO admissions (
      application_id, student_name, dob, gender, religion,
      category, sub_caste,
      father_name, mother_name,
      email, mobile, guardian_mobile,
      nationality, state, permanent_address,
      prev_college, prev_combination,
      admission_through, cet_number, cet_rank,
      seat_allotted, allotted_branch,
      photo_path, signature_path,
      marks_12_path, study_certificate_path,
      transfer_certificate_path,
      kea_ack_path, management_receipt_path
    )
    VALUES (
      :application_id, :student_name, :dob, :gender, :religion,
      :category, :sub_caste,
      :father_name, :mother_name,
      :email, :mobile, :guardian_mobile,
      :nationality, :state, :permanent_address,
      :prev_college, :prev_combination,
      :admission_through, :cet_number, :cet_rank,
      :seat_allotted, :allotted_branch,
      :photo, :signature,
      :marks_12, :study, :tc,
      :kea, :mgmt
    )
  ");

  $stmt->execute([
    ':application_id' => $appId,
    ':student_name' => $d['student_name'],
    ':dob' => $d['dob'],
    ':gender' => $d['gender'],
    ':religion' => $d['religion'],
    ':category' => $d['category'],
    ':sub_caste' => $d['sub_caste'],
    ':father_name' => $d['father_name'],
    ':mother_name' => $d['mother_name'],
    ':email' => $d['email'],
    ':mobile' => $d['mobile'],
    ':guardian_mobile' => $d['guardian_mobile'],
    ':nationality' => $d['nationality'],
    ':state' => $d['state'],
    ':permanent_address' => $d['permanent_address'],
    ':prev_college' => $d['prev_college'],
    ':prev_combination' => $d['prev_combination'],
    ':admission_through' => $d['admission_through'],
    ':cet_number' => $d['cet_number'] ?? null,
    ':cet_rank' => $d['cet_rank'] ?? null,
    ':seat_allotted' => $d['seat_allotted'],
    ':allotted_branch' => $d['allotted_branch'],
    ':photo' => $paths['photo'],
    ':signature' => $paths['signature'],
    ':marks_12' => $paths['marks_12'],
    ':study' => $paths['study'],
    ':tc' => $paths['tc'],
    ':kea' => $paths['kea'] ?? null,
    ':mgmt' => $paths['mgmt'] ?? null
  ]);

  $_SESSION['flash'] = "Application submitted successfully. ID: $appId";
  $_SESSION['flash_type'] = "success";
  header("Location: success.php");
  exit;

} catch (Exception $e) {
  $_SESSION['flash'] = "Error: " . $e->getMessage();
  $_SESSION['flash_type'] = "error";
  header("Location: index.php");
  exit;
}
