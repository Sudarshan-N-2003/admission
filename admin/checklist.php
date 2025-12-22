<?php
require_once 'auth.php';
require_once '../db.php';

$id = $_GET['id'] ?? '';
if (!$id) die('Invalid Application ID');

$stmt = $pdo->prepare("SELECT * FROM admissions WHERE application_id = :id");
$stmt->execute([':id'=>$id]);
$d = $stmt->fetch();
if (!$d) die('Application not found');

$status = json_decode($d['document_status'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = json_encode($_POST['docs']);
    $pdo->prepare(
        "UPDATE admissions SET document_status=:s WHERE application_id=:id"
    )->execute([':s'=>$newStatus, ':id'=>$id]);

    header("Location: checklist.php?id=$id");
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
<h2>Document Checklist</h2>
<p><b>Application ID:</b> <?= $id ?></p>

<form method="post">

<?php
$docs = [
  'marks_10' => '10th Marks Card',
  'marks_12' => '12th / Diploma Marks Card',
  'study_certificate' => 'Study Certificate',
  'transfer_certificate' => 'Transfer Certificate',
  'photo' => 'Photograph'
];

foreach ($docs as $k=>$v):
?>
<label><?= $v ?></label>
<select name="docs[<?= $k ?>]">
  <option value="">Pending</option>
  <option value="RECEIVED" <?= ($status[$k]??'')==='RECEIVED'?'selected':'' ?>>
    Received
  </option>
</select>
<?php endforeach; ?>

<button type="submit">Save Status</button>
</form>

<a href="dashboard.php">Back</a>
</div>

</body>
</html>