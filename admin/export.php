<?php
require_once 'auth.php';
require_once '../db.php';

/*
 Excel headers
*/
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=VVIT_Admissions_" . date('Ymd_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

/*
 Column headings
*/
echo "Application ID\t";
echo "Student Name\t";
echo "Gender\t";
echo "DOB\t";
echo "Mobile\t";
echo "Guardian Mobile\t";
echo "Email\t";
echo "State\t";
echo "Category\t";
echo "Sub Caste\t";
echo "Admission Through\t";
echo "CET Number\t";
echo "CET Rank\t";
echo "Quota\t";
echo "Allotted Branch\t";
echo "Previous Combination\t";
echo "Previous College\t";
echo "Permanent Address\t";
echo "Registered Date & Time\n";

/*
 Fetch data
*/
$stmt = $pdo->query("
    SELECT
        application_id,
        student_name,
        gender,
        dob,
        mobile,
        guardian_mobile,
        email,
        state,
        category,
        sub_caste,
        admission_through,
        cet_number,
        cet_rank,
        seat_allotted,
        allotted_branch,
        prev_combination,
        prev_college,
        permanent_address,
        created_at
    FROM admissions
    ORDER BY created_at DESC
");

while ($row = $stmt->fetch()) {

    echo $row['application_id'] . "\t";
    echo $row['student_name'] . "\t";
    echo $row['gender'] . "\t";
    echo $row['dob'] . "\t";
    echo $row['mobile'] . "\t";
    echo $row['guardian_mobile'] . "\t";
    echo ($row['email'] ?? '') . "\t";
    echo $row['state'] . "\t";
    echo $row['category'] . "\t";
    echo ($row['sub_caste'] ?? '') . "\t";
    echo $row['admission_through'] . "\t";
    echo ($row['cet_number'] ?? '') . "\t";
    echo ($row['cet_rank'] ?? '') . "\t";
    echo ($row['seat_allotted'] ?? '') . "\t";
    echo $row['allotted_branch'] . "\t";
    echo $row['prev_combination'] . "\t";
    echo $row['prev_college'] . "\t";
    echo preg_replace("/\r|\n/", " ", $row['permanent_address']) . "\t";
    echo date('d-m-Y H:i', strtotime($row['created_at'])) . "\n";
}

exit;