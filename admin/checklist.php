<?php
require_once 'auth.php';
require_once __DIR__ . '/../db.php';

/* ===============================
   DB CONNECTION
================================ */
$pdo = get_db();

/* ===============================
   GET APPLICATION ID
================================ */
$applicationId = $_GET['id'] ?? '';
if ($applicationId === '') {
    die('Invalid Application ID');
}

/* ===============================
   FETCH APPLICATION
================================ */
$stmt = $pdo->prepare(
    "SELECT application_id, student_name, document_status, printed_at
     FROM admissions
     WHERE application_id = :id"
);
$stmt->execute([':id' => $applicationId]);
$d = $stmt->fetch();

if (!$d) {
    die('Application not found');
}

/* ===============================
   LOCK STATUS (PRINTED)
================================ */
$locked = !empty($d['printed_at']);

/* ===============================
   DOCUMENT STATUS
================================ */
$status = json_decode($d['document_status'], true) ?? [];

/* ===============================
   REAL CHECKLIST COMPLETION
================================ */
$requiredDocs = [
    'marks_10',
    'marks_12',
    'study_certificate',
    'transfer_certificate',
    'photo'
];

$checklistComplete = true;
foreach ($requiredDocs as $doc) {
    if (($status[$doc] ?? '') !== 'RECEIVED') {
        $checklistComplete = false;
        break;
    }
}

/* ===============================
   EFFECTIVE COMPLETION LOGIC
   (LOCKED = COMPLETE FOR UI)
================================ */
$effectiveComplete = $checklistComplete || $locked;

/* ===============================
   SAVE CHECKLIST (ONLY IF NOT LOCKED)
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$locked) {

    $newStatus = $_POST['docs'] ?? [];
    $jsonStatus = json_encode($newStatus);

    $pdo->prepare(
        "UPDATE admissions
         SET document_status = :ds
         WHERE application_id = :id"
    )->execute([
        ':ds' => $jsonStatus,
        ':id' => $applicationId
    ]);

    header("Location: checklist.php?id=" . urlencode($applicationId));
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Document Checklist</title>
<link rel="stylesheet" href="../assets/styles.css">
</head>
<body>

<div class="container">

  <div class="topbar">
    <h2>Document Checklist</h2>
    <a href="dashboard.php" class="college-btn">Back</a>
  </div>

  <p><b>Application ID:</b> <?= htmlspecialchars($applicationId) ?></p>
  <p><b>Student Name:</b> <?= htmlspecialchars($d['student_name']) ?></p>

  <?php if ($locked): ?>
    <div class="flash info">
      Checklist locked — application already printed
    </div>
  <?php endif; ?>

  <?php if (!$effectiveComplete && !$locked): ?>
    <div class="flash error">
      Checklist is not completed. Please verify all documents.
    </div>
  <?php endif; ?>

  <form method="post">

    <table class="table">
      <tr>
        <th>Sl</th>
        <th>Document</th>
        <th>Status</th>
      </tr>

      <?php
      $docs = [
        'marks_10' => '10th Marks Card',
        'marks_12' => '12th / Diploma Marks Card',
        'study_certificate' => 'Study Certificate',
        'transfer_certificate' => 'Transfer Certificate',
        'photo' => 'Photograph'
      ];

      $i = 1;
      foreach ($docs as $key => $label):
      ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><?= htmlspecialchars($label) ?></td>
        <td>
          <select name="docs[<?= $key ?>]" <?= $locked ? 'disabled' : '' ?>>
            <option value="">Pending</option>
            <option value="RECEIVED"
              <?= ($status[$key] ?? '') === 'RECEIVED' ? 'selected' : '' ?>>
              Received
            </option>
          </select>
        </td>
      </tr>
      <?php endforeach; ?>

    </table>

    <div class="actions">

      <?php if (!$locked): ?>
        <button type="submit" class="secondary">Save</button>
      <?php endif; ?>

      <?php if ($effectiveComplete): ?>
        <a href="print_pdf.php?id=<?= urlencode($applicationId) ?>"
           class="btn-primary"
           style="text-decoration:none; padding:10px 18px; border-radius:8px;">
          Print Application
        </a>
      <?php endif; ?>

    </div>

  </form>

</div>

</body>
</html>
