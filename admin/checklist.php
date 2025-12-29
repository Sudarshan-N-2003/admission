<?php
require_once 'auth.php';
require_once __DIR__ . '/../db.php';

/* ===============================
   GET DB CONNECTION (CRITICAL)
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
   CHECK IF LOCKED (PRINTED)
================================ */
$locked = !empty($d['printed_at']);

/* ===============================
   DOCUMENT STATUS
================================ */
$status = json_decode($app['document_status'], true) ?? [];

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
   FINAL PRINT RULE
================================ */

// If checklist complete → allow print ALWAYS
if ($checklistComplete) {
    header("Location: print_pdf.php?id=" . $applicationId);
    exit;
}
/* ===============================
   SAVE CHECKLIST
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

    if (($_POST['action'] ?? '') === 'print') {
        header("Location: print_pdf.php?id=$applicationId");
        exit;
    }

    header("Location: checklist.php?id=$applicationId");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
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
  <td><?= $label ?></td>
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

<?php if (!$locked): ?>
<div class="actions">
  <button type="submit" class="secondary">Save</button>
  <button type="submit" name="action" value="print" class="btn-primary">
    Save & Print
  </button>
</div>
<?php endif; ?>

</form>

</div>
</body>
</html>
