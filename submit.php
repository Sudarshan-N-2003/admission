<?php


// generate ID
$college_code = '1VJ';
$year = fetch_external_year(); // full year
$yy = substr((string)$year,-2);
$serial = next_serial_for_year($year);
$generated_id = sprintf('%s%s%s', $college_code, $yy, $serial);


// persist record (JSON) - replace with DB in production
$record = [
'id' => $generated_id,
'data' => $data,
'files' => [
'photo' => $photoPath,
'marks' => $marksPath,
'aadhaar_front' => $aadhaarFront,
'aadhaar_back' => $aadhaarBack,
'caste_income' => $casteIncomePath
],
'created_at' => date('c')
];


$subdir = __DIR__ . '/submissions';
if (!is_dir($subdir)) mkdir($subdir, 0755, true);
file_put_contents($subdir . '/' . $generated_id . '.json', json_encode($record, JSON_PRETTY_PRINT));


// generate PDF using DOMPDF
$html = build_application_html($record);
$pdfPath = $uploadDir . '/' . $generated_id . '_application.pdf';
create_pdf_from_html($html, $pdfPath);


// send email with attachment (PHPMailer)
$emailSent = send_email_with_attachment($data['email'], $data['student_name'], $generated_id, $pdfPath, $data['mobile']);


// update JSON with email log
$record['email_sent'] = $emailSent ? ['to'=>$data['email'],'time'=>date('c')] : null;
file_put_contents($subdir . '/' . $generated_id . '.json', json_encode($record, JSON_PRETTY_PRINT));


$_SESSION['flash'] = 'Application submitted successfully. Your ID: ' . $generated_id;
$_SESSION['flash_type'] = 'success';
// Provide a download link to the PDF (served from the uploads location)
$_SESSION['pdf_path'] = str_replace(__DIR__, '', $pdfPath);


header('Location: index.php');
exit;


} catch (Exception $e) {
$_SESSION['flash'] = 'Error: ' . $e->getMessage();
$_SESSION['flash_type'] = 'error';
header('Location: index.php');
exit;
}
