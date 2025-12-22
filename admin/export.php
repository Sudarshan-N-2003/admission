<?php
require_once 'auth.php';
require_once '../db.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=admissions_export.xls");

echo "Application ID\tName\tMobile\tBranch\tAdmission Type\tRegistered Date\n";

$stmt = $pdo->query("
  SELECT application_id, student_name, mobile,
         allotted_branch, admission_through, created_at
  FROM admissions
  ORDER BY created_at DESC
");

while ($row = $stmt->fetch()) {
    echo "{$row['application_id']}\t";
    echo "{$row['student_name']}\t";
    echo "{$row['mobile']}\t";
    echo "{$row['allotted_branch']}\t";
    echo "{$row['admission_through']}\t";
    echo "{$row['created_at']}\n";
}
exit;