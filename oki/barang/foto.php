<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/data.php';

$idFoto   = filter_input(INPUT_GET, 'id_foto', FILTER_VALIDATE_INT);
$idBarang = filter_input(INPUT_GET, 'id_barang', FILTER_VALIDATE_INT);
if (!$idBarang) {
    $idBarang = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
}

$user = user_now();
$userOrg = (int)($user['org'] ?? 0);
$isAdminFlag = (($user['role'] ?? '') === 'admin') ? 1 : 0;

$row = null;

if ($idFoto && $idFoto > 0) {
    $row = q_row("
        select
            encode(f.data_foto, 'base64') as foto_base64,
            f.mime_type
        from foto_barang f
        join barang b on b.id_barang = f.id_barang
        join organisasi o on o.id_organisasi = b.id_organisasi
        where f.id_foto = :id_foto
          and f.data_foto is not null
          and (
              (b.status = 'aktif' and o.status_verifikasi = 'terverifikasi')
              or :is_admin = 1
              or (:user_org > 0 and b.id_organisasi = :user_org)
          )
        limit 1
    ", [
        'id_foto'  => $idFoto,
        'is_admin' => $isAdminFlag,
        'user_org' => $userOrg,
    ]);
} elseif ($idBarang && $idBarang > 0) {
    $row = q_row("
        select
            encode(f.data_foto, 'base64') as foto_base64,
            f.mime_type
        from foto_barang f
        join barang b on b.id_barang = f.id_barang
        join organisasi o on o.id_organisasi = b.id_organisasi
        where f.id_barang = :id_barang
          and f.data_foto is not null
          and (
              (b.status = 'aktif' and o.status_verifikasi = 'terverifikasi')
              or :is_admin = 1
              or (:user_org > 0 and b.id_organisasi = :user_org)
          )
        order by f.is_utama desc nulls last, f.id_foto asc
        limit 1
    ", [
        'id_barang' => $idBarang,
        'is_admin'  => $isAdminFlag,
        'user_org'  => $userOrg,
    ]);
} else {
    http_response_code(400);
    exit('Parameter foto tidak valid.');
}

if (!$row || empty($row['foto_base64'])) {
    http_response_code(404);
    exit('Foto tidak ditemukan.');
}

$mime = trim((string)($row['mime_type'] ?? ''));
$base64 = preg_replace('/\s+/', '', (string)$row['foto_base64']);
$foto = base64_decode($base64, true);

if ($foto === false || strlen($foto) === 0) {
    http_response_code(500);
    exit('Gagal membaca data foto.');
}

if ($mime === '' && class_exists('finfo')) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)($finfo->buffer($foto) ?: '');
}

if (!in_array($mime, [
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/gif',
], true)) {
    http_response_code(415);
    exit('Format foto tidak didukung.');
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . strlen($foto));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=300');

echo $foto;
exit;