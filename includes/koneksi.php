<?php
// Koneksi PostgreSQL via PDO. Ubah nilai default di bawah, atau set environment variable
// DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS.
date_default_timezone_set('Asia/Jakarta');

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '5432';
    $nama = getenv('DB_NAME') ?: 'sipinjam_oki';
    $user = getenv('DB_USER') ?: 'postgres';
    $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'admin';

    try {
        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$nama", $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec("SET TIME ZONE 'Asia/Jakarta'");
    } catch (PDOException $e) {
        http_response_code(500);
        exit('Tidak bisa terhubung ke database PostgreSQL. Periksa pengaturan di includes/koneksi.php '
           . 'dan pastikan ekstensi pdo_pgsql aktif.');
    }
    return $pdo;
}
