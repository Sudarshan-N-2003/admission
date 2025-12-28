<?php
session_start();
require_once 'auth.php';
require_once __DIR__ . '/../db.php';

/* ===============================
   GET DB CONNECTION (CRITICAL)
================================ */
$pdo = get_db();

$app = null;
$needsChecklist = false;

/* ===============================
   HANDLE FORM SUBMIT
================================ */
if (!empty($_GET['application_id'])) {

    $applicationId = trim($_GET['application_id']);

    $stmt = $pdo->prepare(
        "SELECT application_id, student_name, document_status, printed_at
         FROM admissions
         WHERE application_id = :id"
    );
    $stmt->execute([':id' => $applicationId]);
    $app = $stmt->fetch();

    if ($app) {
        $status = json_decode($app['document_status'], true) ?? [];

        // Check if checklist is complete
        $requiredDocs = [
            'marks_10',
            'marks_12',
            'study_certificate',
            'transfer_certificate',
            'photo'
        ];

        foreach ($requiredDocs as $doc) {
            if (empty($status[$doc]) || $status[$doc] !== 'RECEIVED') {
                $needsChecklist = true;
                break;
            }
        }

        // If checklist complete → direct print
        if (!$needsChecklist) {
            header("Location: print_pdf.php?id=" . $applicationId);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Print Application</title>
<link rel="stylesheet" href="../assets/styles.css">
</head>
<body>

<div class="container">

<div class="topbar">
  <h2>Print Application</h2>
  <a href="dashboard.php" class="college-btn">Back</a>
</div>

<form method="get">
  <label>Enter Application ID</label>
  <input type="text" name="application_id" required>
  <div class="actions">
    <button type="submit" class="btn-primary">Proceed</button>
  </div>
</form>

<?php if ($app && $needsChecklist): ?>
<hr>
<p><b>Student Name:</b> <?= htmlspecialchars($app['student_name']) ?></p>

<div class="flash info">
  Checklist is not completed. Please verify documents before printing.
</div>

<a href="checklist.php?id=<?= $app['application_id'] ?>"
   class="admin-btn">
   Open Checklist & Print
</a>
<?php endif; ?>

</div>
</body>
</html>
