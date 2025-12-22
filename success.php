<?php
session_start();

if (empty($_SESSION['application_id']) || empty($_SESSION['pdf_path'])) {
    header("Location: index.php");
    exit;
}

$appId = $_SESSION['application_id'];
$pdf  = $_SESSION['pdf_path'];
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Application Submitted</title>
<link rel="stylesheet" href="assets/styles.css">
</head>
<body>

<div class="container">

<h2>Application Submitted Successfully 🎉</h2>

<p><strong>Your Application ID:</strong></p>
<div style="font-size:20px;font-weight:bold;color:#2563eb">
  <?= htmlspecialchars($appId) ?>
</div>

<hr>

<a href="download.php?file=<?= urlencode(basename($pdf)) ?>"
   class="download-btn">
   Download Application PDF
</a>

<p style="margin-top:15px;font-size:14px;color:#555">
Please save your Application ID for future reference.
</p>

</div>

</body>
</html>