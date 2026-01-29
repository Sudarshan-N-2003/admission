<?php
declare(strict_types=1);
session_start();

/* =========================================
   BOOTSTRAP
========================================= */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* =========================================
   BASIC CONFIG
========================================= */
ini_set('post_max_size', '10M');
ini_set('upload_max_filesize', '10M');

$MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB

/* =========================================
   HELPER: FAIL SAFELY
========================================= */
function fail(string $msg): never {
    $_SESSION['flash'] = "Error: $msg";
    $_SESSION['flash_type'] = 'error';
    header("Location: index.php");
    exit;
}

try {

    /* =========================================
       1. REQUIRED FIELD VALIDATION
    ========================================= */
    $required = [
        'student_name','dob','gender','religion',
        'category','sub_caste',
        'father_name','mother_name',
        'email','mobile','guardian_mobile',
        'aadhaar_number',
        'prev_college','prev_combination',
        'nationality','state','permanent_address',
        'admission_through'
    ];

    foreach ($required as $field) {
        if (empty(trim($_POST[$field] ?? ''))) {
            fail("Missing required field: $field");
        }
    }

    /* =========================================
       2. NORMALIZE INPUT
    ========================================= */
    $data = [];
    foreach ($_POST as $k => $v) {
        $data[$k] = is_string($v) ? strtoupper(trim($v)) : $v;
    }

    /* =========================================
       3. ADMISSION TYPE LOGIC
    ========================================= */
    if ($data['admission_through'] === 'KEA') {
        foreach (['cet_number','cet_rank','seat_allotted','allotted_branch'] as $f) {
            if (empty($data[$f] ?? '')) {
                fail("Missing KEA field: $f");
            }
        }
    } elseif ($data['admission_through'] === 'MANAGEMENT') {
        if (empty($data['allotted_branch_management'] ?? '')) {
            fail("Missing Management branch");
        }
        $data['allotted_branch'] = $data['allotted_branch_management'];
        $data['seat_allotted']   = 'MANAGEMENT';
    } else {
        fail("Invalid admission type");
    }

    /* =========================================
       4. DUPLICATE CHECK
    ========================================= */
    $pdo = get_db();

    $chk = $pdo->prepare("
        SELECT 1 FROM admissions
        WHERE mobile = :mobile OR email = :email
        LIMIT 1
    ");
    $chk->execute([
        ':mobile' => $data['mobile'],
        ':email'  => $data['email']
    ]);

    if ($chk->fetch()) {
        fail("This mobile number or email is already registered");
    }

    /* =========================================
       5. GENERATE APPLICATION ID
    ========================================= */
    $year   = fetch_external_year();     // e.g. 2026
    $serial = next_serial_for_year($year); // 001
    $application_id = '1VJ' . substr((string)$year, -2) . $serial;

    /* =========================================
       6. FILE VALIDATION (SIZE ONLY)
    ========================================= */
    $requiredFiles = [
        'passport_photo',
        'marks_12',
        'transfer_certificate',
        'study_certificate',
        'student_signature'
    ];

    foreach ($requiredFiles as $f) {
        if (empty($_FILES[$f]['name'])) {
            fail("Missing required file: $f");
        }
        if ($_FILES[$f]['size'] > $MAX_FILE_SIZE) {
            fail("File too large: $f (max 10MB)");
        }
    }

    /* =========================================
       7. UPLOAD FILES TO CLOUDFLARE R2
    ========================================= */
    $folder = "admission/$application_id";

    $photo_url = upload_to_r2($_FILES['passport_photo'], $folder, 'photo.jpg');
    $signature_url = upload_to_r2($_FILES['student_signature'], $folder, 'signature.png');

    $marks_12_url = upload_to_r2($_FILES['marks_12'], $folder, 'marks_12.pdf');
    $transfer_certificate_url = upload_to_r2($_FILES['transfer_certificate'], $folder, 'transfer_certificate.pdf');
    $study_certificate_url = upload_to_r2($_FILES['study_certificate'], $folder, 'study_certificate.pdf');

    $kea_ack_url = null;
    $management_receipt_url = null;

    if ($data['admission_through'] === 'KEA') {
        if (empty($_FILES['kea_acknowledgement']['name'])) {
            fail("KEA acknowledgement is required");
        }
        $kea_ack_url = upload_to_r2(
            $_FILES['kea_acknowledgement'],
            $folder,
            'kea_acknowledgement.pdf'
        );
    }

    if ($data['admission_through'] === 'MANAGEMENT') {
        if (empty($_FILES['management_receipt']['name'])) {
            fail("Management fee receipt is required");
        }
        $management_receipt_url = upload_to_r2(
            $_FILES['management_receipt'],
            $folder,
            'management_receipt.pdf'
        );
    }

    /* =========================================
       8. INSERT INTO DATABASE
    ========================================= */
    $stmt = $pdo->prepare("
        INSERT INTO admissions (
            application_id,
            student_name, dob, gender, religion,
            category, sub_caste,
            father_name, mother_name,
            email, mobile, guardian_mobile,
            aadhaar_number,
            prev_college, prev_combination,
            nationality, state, permanent_address,
            admission_through, seat_allotted, allotted_branch,
            cet_number, cet_rank,
            photo_url, signature_url,
            marks_12_url, transfer_certificate_url, study_certificate_url,
            kea_ack_url, management_receipt_url,
            created_at
        ) VALUES (
            :application_id,
            :student_name, :dob, :gender, :religion,
            :category, :sub_caste,
            :father_name, :mother_name,
            :email, :mobile, :guardian_mobile,
            :aadhaar_number,
            :prev_college, :prev_combination,
            :nationality, :state, :permanent_address,
            :admission_through, :seat_allotted, :allotted_branch,
            :cet_number, :cet_rank,
            :photo_url, :signature_url,
            :marks_12_url, :transfer_certificate_url, :study_certificate_url,
            :kea_ack_url, :management_receipt_url,
            NOW()
        )
    ");

    $stmt->execute([
        ':application_id' => $application_id,
        ':student_name' => $data['student_name'],
        ':dob' => $data['dob'],
        ':gender' => $data['gender'],
        ':religion' => $data['religion'],
        ':category' => $data['category'],
        ':sub_caste' => $data['sub_caste'],
        ':father_name' => $data['father_name'],
        ':mother_name' => $data['mother_name'],
        ':email' => $data['email'],
        ':mobile' => $data['mobile'],
        ':guardian_mobile' => $data['guardian_mobile'],
        ':aadhaar_number' => $data['aadhaar_number'],
        ':prev_college' => $data['prev_college'],
        ':prev_combination' => $data['prev_combination'],
        ':nationality' => $data['nationality'],
        ':state' => $data['state'],
        ':permanent_address' => $data['permanent_address'],
        ':admission_through' => $data['admission_through'],
        ':seat_allotted' => $data['seat_allotted'],
        ':allotted_branch' => $data['allotted_branch'],
        ':cet_number' => $data['cet_number'] ?? null,
        ':cet_rank' => $data['cet_rank'] ?? null,
        ':photo_url' => $photo_url,
        ':signature_url' => $signature_url,
        ':marks_12_url' => $marks_12_url,
        ':transfer_certificate_url' => $transfer_certificate_url,
        ':study_certificate_url' => $study_certificate_url,
        ':kea_ack_url' => $kea_ack_url,
        ':management_receipt_url' => $management_receipt_url
    ]);

    /* =========================================
       9. SUCCESS
    ========================================= */
    $_SESSION['flash'] = "Application submitted successfully. Your Application ID: $application_id";
    $_SESSION['flash_type'] = 'success';

    header("Location: success.php?id=$application_id");
    exit;

} catch (Throwable $e) {
    fail($e->getMessage());
}
