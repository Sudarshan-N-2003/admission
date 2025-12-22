<?php
require_once 'auth.php';
?>
<!DOCTYPE html>
<html>
<head>
<title>Office Dashboard</title>
<link rel="stylesheet" href="../assets/styles.css">
<style>
.admin-btn{
  display:block;
  text-align:center;
  padding:14px;
  margin:15px 0;
  background:#1e40af;
  color:#fff;
  border-radius:8px;
  text-decoration:none;
  font-weight:bold;
}
</style>
</head>
<body>

<div class="container">
  <h2>Admission Office Panel</h2>

  <a href="print.php" class="admin-btn">Print Application</a>
  <a href="export.php" class="admin-btn">Export Data</a>

  <a href="logout.php" style="display:block;margin-top:20px;">Logout</a>
</div>

</body>
</html>