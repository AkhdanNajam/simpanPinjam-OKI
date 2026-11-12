<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/data.php';

$user = require_login(['oki']);

$idBarang = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idBarang || $idBarang < 1) {
    http_response_code(400);
    exit('ID barang tidak valid.');
}

$row = q_row("
    SELECT
        encode(f.data_foto, 'base64') AS foto_base64,
        f.mime_type
    FROM foto_barang f
    JOIN barang b ON b.id_barang = f.id_barang
    WHERE f.id_barang = :id
      AND b.id_organisasi = :org
      AND f.data_foto IS NOT NULL
    ORDER BY f.is_utama DESC, f.id_foto DESC
    LIMIT 1
", [
    'id' => $idBarang,
    'org' => (int) $user['org']
]);

if (!$row || empty($row['foto_base64'])) {
    http_response_code(404);
    exit('Foto tidak ditemukan.');
}

$mime = $row['mime_type'];

if (!in_array($mime, [
    'image/jpeg',
    'image/png',
    'image/webp'
], true)) {
    http_response_code(415);
    exit('Format foto tidak didukung.');
}

$base64 = preg_replace('/\s+/', '', $row['foto_base64']);
$foto = base64_decode($base64, true);

if ($foto === false || strlen($foto) === 0) {
    http_response_code(500);
    exit('Gagal membaca data foto.');
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . strlen($foto));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');

echo $foto;
exit;
