<?php
// admin_actions.php - simple actions: logout, download json, download pdf
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header('Location: admin_login.php'); exit;
}
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

$subdir = __DIR__ . '/submissions';
$path = $subdir . '/' . basename($id) . '.json';

if ($action === 'logout') {
    session_destroy();
    header('Location: admin_login.php'); exit;
}

if ($action === 'download' && file_exists($path)) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="'.basename($path).'"');
    readfile($path); exit;
}

// download PDF if present in the submission directory or uploads
if ($action === 'download_pdf' && file_exists($path)) {
    $record = json_decode(file_get_contents($path), true);
    // if application_pdf key exists and is a local path
    $pdf = $record['files']['application_pdf'] ?? null;
    if ($pdf && file_exists($pdf)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.basename($pdf).'"');
        readfile($pdf); exit;
    } else {
        // search uploads folder for <id>_application.pdf
        foreach (glob(__DIR__ . '/uploads/*/'.$id . '_application.pdf') as $p) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="'.basename($p).'"');
            readfile($p); exit;
        }
    }
    echo 'PDF not found'; exit;
}

echo 'Invalid action';
