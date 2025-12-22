<?php
session_start();

if (empty($_GET['file'])) {
    exit('Invalid request');
}

$file = basename($_GET['file']);
$path = sys_get_temp_dir() . '/' . $file;

if (!file_exists($path)) {
    exit('File not found');
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="'.$file.'"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;