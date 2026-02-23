<?php
// functions.php

// ---------------------------
// CLOUDFLARE R2 CREDENTIALS
// Put your real values here
// ---------------------------
define('R2_ACCOUNT_ID',  '31128b112ea1094724b5efaa0e9f4db8');       // e.g. abc123def456
define('R2_ACCESS_KEY',  '86fc048a07a0cf05a119363c81646e8d');
define('R2_SECRET_KEY',  '69f810cb298c037c7fe2d3149a28c09d26ec8c4ba446cfbcdf143537536ea67d');
define('R2_BUCKET',      'admissions');
define('R2_PUBLIC_URL',  'https://pub-875afe6b4ed54953abbcabce61a5143f.r2.dev');  // e.g. https://files.yourdomain.com
// OR if using R2 dev URL: https://pub-xxx.r2.dev

// ---------------------------
// ACADEMIC YEAR
// Example: 2025-2026
// ---------------------------
function get_academic_year(): string {
    $year = (int)date('Y');
    return $year . '-' . ($year + 1);
}

// ---------------------------
// GENERATE UNIQUE APPLICATION ID
// Example: 1VJ26001
// ---------------------------
function generate_application_id(): string {
    $year = date('y');
    $file = sys_get_temp_dir() . '/vvit_serial_' . $year . '.txt';
    $last = file_exists($file) ? (int)file_get_contents($file) : 0;
    $next = $last + 1;
    file_put_contents($file, $next);
    return '1VJ' . $year . str_pad((string)$next, 3, '0', STR_PAD_LEFT);
}

// ---------------------------
// VALIDATE UPLOADED FILE
// ---------------------------
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

// ---------------------------
// UPLOAD FILE TO CLOUDFLARE R2
// Returns: public URL string on success
// Throws:  Exception on failure
// ---------------------------
function upload_to_r2(array $file, string $application_id, string $fieldName): string {
    $ext        = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $uniqueName = $fieldName . '_' . uniqid() . '.' . $ext;
    $r2Key      = 'admissions/' . $application_id . '/' . $uniqueName;

    $endpoint   = 'https://' . R2_ACCOUNT_ID . '.r2.cloudflarestorage.com';
    $host        = R2_ACCOUNT_ID . '.r2.cloudflarestorage.com';
    $region     = 'auto';
    $service    = 's3';
    $dateISO    = gmdate('Ymd\THis\Z');
    $dateShort  = gmdate('Ymd');

    $fileContent = file_get_contents($file['tmp_name']);
    if ($fileContent === false) {
        throw new Exception('Cannot read uploaded file: ' . $fieldName);
    }

    $contentType = mime_content_type($file['tmp_name']) ?: 'application/octet-stream';
    $payloadHash = hash('sha256', $fileContent);

    // --- Build canonical request ---
    $canonicalUri     = '/' . R2_BUCKET . '/' . $r2Key;
    $canonicalQuery   = '';
    $canonicalHeaders =
        'content-type:' . $contentType . "\n" .
        'host:' . $host . "\n" .
        'x-amz-content-sha256:' . $payloadHash . "\n" .
        'x-amz-date:' . $dateISO . "\n";
    $signedHeaders    = 'content-type;host;x-amz-content-sha256;x-amz-date';

    $canonicalRequest = implode("\n", [
        'PUT',
        $canonicalUri,
        $canonicalQuery,
        $canonicalHeaders,
        $signedHeaders,
        $payloadHash,
    ]);

    // --- String to sign ---
    $credentialScope = $dateShort . '/' . $region . '/' . $service . '/aws4_request';
    $stringToSign    = implode("\n", [
        'AWS4-HMAC-SHA256',
        $dateISO,
        $credentialScope,
        hash('sha256', $canonicalRequest),
    ]);

    // --- Signing key ---
    $signingKey = hash_hmac('sha256', 'aws4_request',
        hash_hmac('sha256', $service,
            hash_hmac('sha256', $region,
                hash_hmac('sha256', $dateShort, 'AWS4' . R2_SECRET_KEY, true),
            true),
        true),
    true);

    $signature = hash_hmac('sha256', $stringToSign, $signingKey);

    // --- Authorization header ---
    $authorization =
        'AWS4-HMAC-SHA256 ' .
        'Credential=' . R2_ACCESS_KEY . '/' . $credentialScope . ', ' .
        'SignedHeaders=' . $signedHeaders . ', ' .
        'Signature=' . $signature;

    // --- Upload via cURL ---
    $url = $endpoint . $canonicalUri;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => 'PUT',
        CURLOPT_POSTFIELDS     => $fileContent,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: '          . $contentType,
            'Content-Length: '        . strlen($fileContent),
            'Host: '                  . $host,
            'x-amz-content-sha256: ' . $payloadHash,
            'x-amz-date: '           . $dateISO,
            'Authorization: '         . $authorization,
        ],
    ]);

    $response   = curl_exec($ch);
    $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError  = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception("R2 upload cURL error ($fieldName): $curlError");
    }
    if ($httpCode !== 200) {
        throw new Exception("R2 upload failed ($fieldName) HTTP $httpCode: $response");
    }

    // Return the public URL
    return rtrim(R2_PUBLIC_URL, '/') . '/' . $r2Key;
}

// ---------------------------
// DOWNLOAD R2 IMAGE TO TEMP FILE (for TCPDF)
// ---------------------------
function downloadImageToTemp(string $url): ?string {
    if (empty($url)) return null;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $imgData  = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($imgData === false || $httpCode !== 200) return null;

    $ext     = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    $ext     = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? $ext : 'jpg';
    $tmpFile = tempnam(sys_get_temp_dir(), 'vvit_') . '.' . $ext;
    file_put_contents($tmpFile, $imgData);

    return $tmpFile;
}
