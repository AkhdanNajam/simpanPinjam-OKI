<?php
// Koneksi PostgreSQL via PDO
date_default_timezone_set('Asia/Jakarta');

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

$host = getenv('DB_HOST') ?: 'ep-wandering-river-bdu4nu5s.c-8.us-east-2.aws.neon.tech';
$port = getenv('DB_PORT') ?: '5432';
$nama = getenv('DB_NAME') ?: 'simpinjam_oki';
$user = getenv('DB_USER') ?: 'neondb_owner';
$pass = getenv('DB_PASS') ?: 'npg_S7FclQi5vbAx';

    try {
        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$nama", $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec("SET TIME ZONE 'Asia/Jakarta'");
    } catch (PDOException $e) {
        http_response_code(500);
        exit('Gagal terhubung ke database PostgreSQL!<br><b>Detail Error:</b> ' . htmlspecialchars($e->getMessage()));
    }
    return $pdo;
}