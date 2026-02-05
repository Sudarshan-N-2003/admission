<?php
// functions.php

/**
 * Generate academic year safely (no API, no error)
 * Example: 2025-2026
 */
function get_academic_year(): string {
    $year = (int)date('Y');
    return $year . '-' . ($year + 1);
}

/**
 * Generate unique application ID
 * Example: 1VJ26001
 */
function generate_application_id(): string {
    $year = date('y'); // last 2 digits
    $file = sys_get_temp_dir() . '/vvit_serial_' . $year . '.txt';

    $last = file_exists($file) ? (int)file_get_contents($file) : 0;
    $next = $last + 1;

    file_put_contents($file, $next);

    return '1VJ' . $year . str_pad((string)$next, 3, '0', STR_PAD_LEFT);
}

/**
 * Validate uploaded file (NO MOVE – R2/cloud safe)
 */
function validate_file(array $file, array $allowedExt, int $maxBytes): void {

    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload failed: ' . ($file['name'] ?? 'Unknown'));
    }

    if ($file['size'] > $maxBytes) {
        throw new Exception('File too large: ' . $file['name']);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        throw new Exception('Invalid file type: ' . $file['name']);
    }
}
