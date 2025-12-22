<?php
require_once 'auth.php';
require_once '../db.php';

$app = null;

if (!empty($_GET['application_id'])) {
    $stmt = $pdo->prepare(
        "SELECT * FROM admissions WHERE application_id = :id"
    );
    $stmt->execute([':id' => $_GET['application_id']]);
    $app = $stmt->fetch();
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
<h2>Print Application</h2>

<form method="get">
  <label>Enter Application ID</label>
  <input type="text" name="application_id" required>
  <button type="submit">Fetch</button>
</form>

<?php if ($app): ?>
<hr>
<p><b>Name:</b> <?= htmlspecialchars($app['student_name']) ?></p>
<p><b>Branch:</b> <?= htmlspecialchars($app['allotted_branch']) ?></p>
<p><b>Admission Type:</b> <?= $app['admission_through'] ?></p>

<a href="print_pdf.php?id=<?= $app['application_id'] ?>"
   class="admin-btn">
   Download PDF
</a>
<?php endif; ?>

</div>
</body>
</html>