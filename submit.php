<?php
// submit.php
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

try {

    /* ===============================
       REQUIRED FIELD VALIDATION
    =============================== */
    $required = [
        'student_name','dob','gender','religion',
        'category','sub_caste','father_name','mother_name',
        'email','mobile','aadhaar_number','guardian_mobile',
        'prev_college','prev_combination','nationality',
        'state','permanent_address','admission_through'
    ];

    foreach ($required as $field) {
        if (empty(trim($_POST[$field] ?? ''))) {
            throw new Exception("Missing required field: $field");
        }
    }

    /* ===============================
       NORMALIZE DATA
    =============================== */
    $data = [];
    foreach ($_POST as $k => $v) {
        $data[$k] = is_string($v) ? strtoupper(trim($v)) : $v;
    }

    /* ===============================
       ADMISSION LOGIC
    =============================== */
    if ($data['admission_through'] === 'KEA') {
        foreach (['cet_number','cet_rank','seat_allotted','allotted_branch'] as $f) {
            if (empty($data[$f] ?? '')) {
                throw new Exception("Missing KEA field: $f");
            }
        }
    }

    if ($data['admission_through'] === 'MANAGEMENT') {
        if (empty($data['allotted_branch_management'] ?? '')) {
            throw new Exception("Missing Management branch");
        }
        $data['allotted_branch'] = $data['allotted_branch_management'];
        $data['seat_allotted']   = 'MANAGEMENT';
    }

    /* ===============================
       FILE VALIDATION
    =============================== */
    $max = 10 * 1024 * 1024; // 10MB

    validate_file($_FILES['passport_photo'],      ['jpg','jpeg','png'], $max);
    validate_file($_FILES['student_signature'],   ['jpg','jpeg','png'], $max);
    validate_file($_FILES['marks_12'],            ['pdf'],              $max);
    validate_file($_FILES['transfer_certificate'],['pdf'],              $max);
    validate_file($_FILES['study_certificate'],   ['pdf'],              $max);

    if ($data['admission_through'] === 'KEA') {
        validate_file($_FILES['kea_acknowledgement'], ['pdf'], $max);
    }

    if ($data['admission_through'] === 'MANAGEMENT') {
        validate_file($_FILES['management_receipt'], ['pdf'], $max);
    }

    /* ===============================
       GENERATE APPLICATION ID
    =============================== */
    $application_id = generate_application_id();
    $academic_year  = get_academic_year();

    /* ===============================
       UPLOAD FILES TO CLOUDFLARE R2
    =============================== */
    $photo_path                  = upload_to_r2($_FILES['passport_photo'],       $application_id, 'photo');
    $signature_path              = upload_to_r2($_FILES['student_signature'],     $application_id, 'signature');
    $marks_12_path               = upload_to_r2($_FILES['marks_12'],              $application_id, 'marks_12');
    $transfer_certificate_path   = upload_to_r2($_FILES['transfer_certificate'],  $application_id, 'transfer_certificate');
    $study_certificate_path      = upload_to_r2($_FILES['study_certificate'],     $application_id, 'study_certificate');

    $kea_ack_path        = '';
    $management_receipt_path = '';

    if ($data['admission_through'] === 'KEA') {
        $kea_ack_path = upload_to_r2($_FILES['kea_acknowledgement'], $application_id, 'kea_acknowledgement');
    }

    if ($data['admission_through'] === 'MANAGEMENT') {
        $management_receipt_path = upload_to_r2($_FILES['management_receipt'], $application_id, 'management_receipt');
    }

    /* ===============================
       DATABASE INSERT
    =============================== */
    $pdo = get_db();

    $stmt = $pdo->prepare("
        INSERT INTO admissions (
            application_id, academic_year,
            student_name, dob, gender, religion,
            category, sub_caste,
            father_name, mother_name,
            email, mobile, aadhaar_number, guardian_mobile,
            prev_college, prev_combination,
            nationality, state, permanent_address,
            admission_through, allotted_branch, seat_allotted,
            photo_path, signature_path, marks_12_path,
            transfer_certificate_path, study_certificate_path,
            kea_ack_path, management_receipt_path,
            created_at
        ) VALUES (
            :application_id, :academic_year,
            :student_name, :dob, :gender, :religion,
            :category, :sub_caste,
            :father_name, :mother_name,
            :email, :mobile, :aadhaar_number, :guardian_mobile,
            :prev_college, :prev_combination,
            :nationality, :state, :permanent_address,
            :admission_through, :allotted_branch, :seat_allotted,
            :photo_path, :signature_path, :marks_12_path,
            :transfer_certificate_path, :study_certificate_path,
            :kea_ack_path, :management_receipt_path,
            NOW()
        )
    ");

    $stmt->execute([
        ':application_id'            => $application_id,
        ':academic_year'             => $academic_year,
        ':student_name'              => $data['student_name'],
        ':dob'                       => $data['dob'],
        ':gender'                    => $data['gender'],
        ':religion'                  => $data['religion'],
        ':category'                  => $data['category'],
        ':sub_caste'                 => $data['sub_caste'],
        ':father_name'               => $data['father_name'],
        ':mother_name'               => $data['mother_name'],
        ':email'                     => $data['email'],
        ':mobile'                    => $data['mobile'],
        ':aadhaar_number'            => $data['aadhaar_number'],
        ':guardian_mobile'           => $data['guardian_mobile'],
        ':prev_college'              => $data['prev_college'],
        ':prev_combination'          => $data['prev_combination'],
        ':nationality'               => $data['nationality'],
        ':state'                     => $data['state'],
        ':permanent_address'         => $data['permanent_address'],
        ':admission_through'         => $data['admission_through'],
        ':allotted_branch'           => $data['allotted_branch'],
        ':seat_allotted'             => $data['seat_allotted'] ?? '',
        ':photo_path'                => $photo_path,
        ':signature_path'            => $signature_path,
        ':marks_12_path'             => $marks_12_path,
        ':transfer_certificate_path' => $transfer_certificate_path,
        ':study_certificate_path'    => $study_certificate_path,
        ':kea_ack_path'              => $kea_ack_path,
        ':management_receipt_path'   => $management_receipt_path,
    ]);

    $_SESSION['flash']      = "Application submitted successfully. ID: $application_id";
    $_SESSION['flash_type'] = 'success';

    header('Location: success.php');
    exit;

} catch (Exception $e) {

    $_SESSION['flash']      = 'Error: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'error';
    header('Location: index.php');
    exit;
}
