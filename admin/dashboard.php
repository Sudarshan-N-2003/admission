<?php
require_once 'auth.php';
require_once '../db.php';

$apps = $pdo->query("
    SELECT application_id, student_name, admission_through, allotted_branch, created_at
    FROM admissions
    ORDER BY created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>
<link rel="stylesheet" href="../assets/styles.css">
</head>
<body>

<div class="container">
<h2>Admissions Dashboard</h2>

<table border="1" width="100%">
<tr>
<th>ID</th>
<th>Name</th>
<th>Type</th>
<th>Branch</th>
<th>Date</th>
<th>Action</th>
</tr>

<?php foreach ($apps as $a): ?>
<tr>
<td><?= $a['application_id'] ?></td>
<td><?= htmlspecialchars($a['student_name']) ?></td>
<td><?= $a['admission_through'] ?></td>
<td><?= $a['allotted_branch'] ?></td>
<td><?= date('d-m-Y', strtotime($a['created_at'])) ?></td>
<td>
<a href="view_application.php?id=<?= $a['application_id'] ?>">View</a>
</td>
</tr>
<?php endforeach; ?>

</table>

<a href="logout.php">Logout</a>
</div>

</body>
</html>