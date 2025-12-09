<?php
// db.php - Postgres (Neon) PDO helper
function get_db() {
    static $pdo = null;
    if ($pdo) return $pdo;

    // If a single DATABASE_URL is provided (Neon format), use it first.
    $databaseUrl = getenv('DATABASE_URL') ?: getenv('NEON_DATABASE_URL') ?: null;

    if ($databaseUrl) {
        // DATABASE_URL may look like: postgres://user:pass@host:5432/dbname
        $parts = parse_url($databaseUrl);
        if ($parts === false) {
            throw new Exception("Invalid DATABASE_URL");
        }

        $user = $parts['user'] ?? '';
        $pass = $parts['pass'] ?? '';
        $host = $parts['host'] ?? '127.0.0.1';
        $port = $parts['port'] ?? 5432;
        $db   = ltrim($parts['path'] ?? '', '/');

        // Build DSN with sslmode=require (Neon requires TLS)
        $dsn = "pgsql:host={$host};port={$port};dbname={$db};sslmode=require";
    } else {
        // Fallback to individual env vars
        $host = getenv('NEON_HOST') ?: getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('NEON_PORT') ?: getenv('DB_PORT') ?: '5432';
        $db   = getenv('NEON_DB') ?: getenv('DB_NAME') ?: 'vvit_admission';
        $user = getenv('NEON_USER') ?: getenv('DB_USER') ?: 'postgres';
        $pass = getenv('NEON_PASS') ?: getenv('DB_PASS') ?: '';
        $dsn = "pgsql:host={$host};port={$port};dbname={$db};sslmode=require";
    }

    $opt = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    // Create PDO connection
    $pdo = new PDO($dsn, $user, $pass, $opt);
    return $pdo;
}
