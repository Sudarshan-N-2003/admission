<?php
// functions.php - helper utilities


use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPM
// functions.php - DB-aware helpers (requires db.php)
require_once __DIR__ . '/db.php';
use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* ---------- Year & Serial helpers ---------- */

function fetch_external_year() {
    $url = 'http://worldtimeapi.org/api/timezone/Asia/Kolkata';
    $ctx = stream_context_create(['http'=>['timeout'=>4]]);
    $res = @file_get_contents($url, false, $ctx);
    if (!$res) return intval(date('Y'));
    $data = json_decode($res, true);
    if (isset($data['datetime'])) return intval(substr($data['datetime'],0,4));
    return intval(date('Y'));
}

function next_serial_for_year_db($year) {
    // Use DB transaction + row lock to increment atomically
    $pdo = get_db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT last_serial FROM serial_counters WHERE year = ? FOR UPDATE");
        $stmt->execute([$year]);
        $row = $stmt->fetch();
        if ($row) {
            $n = intval($row['last_serial']) + 1;
            $stmt2 = $pdo->prepare("UPDATE serial_counters SET last_serial = ? WHERE year = ?");
            $stmt2->execute([$n, $year]);
        } else {
            $n = 1;
            $stmt2 = $pdo->prepare("INSERT INTO serial_counters (year, last_serial) VALUES (?, ?)");
            $stmt2->execute([$year, $n]);
        }
        $pdo->commit();
        return str_pad($n, 3, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/* ---------- File / upload helpers (unchanged) ---------- */

function validate_and_move($fileArray, $destDir, $allowedExt = [], $maxBytes = 1048576) {
    if ($fileArray['error'] !== UPLOAD_ERR_OK) throw new Exception('Upload error: ' . ($fileArray['name'] ?? ''));
    $ext = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt)) throw new Exception('Invalid file type for ' . $fileArray['name']);
    if ($fileArray['size'] > $maxBytes) throw new Exception('File too large: ' . $fileArray['name']);
    $safe = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', basename($fileArray['name']));
    $target = rtrim($destDir, '/') . '/' . uniqid() . '_' . $safe;
    if (!move_uploaded_file($fileArray['tmp_name'], $target)) throw new Exception('Unable to move uploaded file');
    return $target;
}

/* ---------- PDF ---------- */

function create_pdf_from_html($html, $outPath) {
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->load_html($html);
    $dompdf->render();
    file_put_contents($outPath, $dompdf->output());
}

/* ---------- HTML builder ---------- */

function build_application_html($record) {
    // Same HTML builder as before (keeps using $record['files'] local paths or S3 URLs)
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
    if (!empty($record['files']['photo']) && (stripos($record['files']['photo'], 'http') === 0 || file_exists($record['files']['photo']))) {
        if (file_exists($record['files']['photo'])) {
            $bin = base64_encode(file_get_contents($record['files']['photo']));
            $photo_html = "<img src='data:image/jpeg;base64,$bin' style='width:110px;height:130px;object-fit:cover;border:1px solid #333'/>";
        } else {
            // external URL (S3) - embed as url
            $photo_html = "<img src='{$record['files']['photo']}' style='width:110px;height:130px;object-fit:cover;border:1px solid #333'/>";
        }
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

/* ---------- DB persistence ---------- */

function save_submission_db($record) {
    // $record = ['id'=>..., 'data'=>array, 'files'=>array, 'created_at'=>...]
    $pdo = get_db();
    $sql = "INSERT INTO submissions (id, student_name, email, mobile, data, files, submitted_documents, created_at, email_sent)
            VALUES (:id, :student_name, :email, :mobile, :data, :files, :submitted_documents, :created_at, :email_sent)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $record['id'],
        ':student_name' => $record['data']['student_name'] ?? null,
        ':email' => $record['data']['email'] ?? null,
        ':mobile' => $record['data']['mobile'] ?? null,
        ':data' => json_encode($record['data'], JSON_UNESCAPED_UNICODE),
        ':files' => json_encode($record['files'], JSON_UNESCAPED_UNICODE),
        ':submitted_documents' => json_encode($record['submitted_documents'] ?? new stdClass(), JSON_UNESCAPED_UNICODE),
        ':created_at' => $record['created_at'] ?? date('Y-m-d H:i:s'),
        ':email_sent' => $record['email_sent'] ? 1 : 0
    ]);
    return true;
}

function update_submission_files_db($id, $filesArray) {
    $pdo = get_db();
    $stmt = $pdo->prepare("UPDATE submissions SET files = :files WHERE id = :id");
    $stmt->execute([':files'=>json_encode($filesArray, JSON_UNESCAPED_UNICODE), ':id'=>$id]);
}

function mark_email_sent_db($id) {
    $pdo = get_db();
    $stmt = $pdo->prepare("UPDATE submissions SET email_sent = 1 WHERE id = :id");
    $stmt->execute([':id'=>$id]);
}

function fetch_submission_db($id) {
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT * FROM submissions WHERE id = :id");
    $stmt->execute([':id'=>$id]);
    $row = $stmt->fetch();
    if (!$row) return null;
    // parse JSON fields
    $row['data'] = json_decode($row['data'], true);
    $row['files'] = json_decode($row['files'], true);
    $row['submitted_documents'] = json_decode($row['submitted_documents'], true);
    return $row;
}

function list_submissions_db($limit=50, $offset=0, $search='') {
    $pdo = get_db();
    if ($search !== '') {
        $sql = "SELECT id, student_name, email, mobile, created_at FROM submissions
                WHERE id LIKE :s OR student_name LIKE :s
                ORDER BY created_at DESC LIMIT :lim OFFSET :off";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':s', "%{$search}%");
        $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $sql = "SELECT id, student_name, email, mobile, created_at FROM submissions
                ORDER BY created_at DESC LIMIT :lim OFFSET :off";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

function count_submissions_db($search='') {
    $pdo = get_db();
    if ($search !== '') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM submissions WHERE id LIKE :s OR student_name LIKE :s");
        $stmt->execute([':s'=>"%{$search}%"]);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) as c FROM submissions");
    }
    return intval($stmt->fetchColumn());
}
