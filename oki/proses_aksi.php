<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/data.php';
require_login(['admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('oki/list.php');
csrf_cek('oki/list.php');

$aksi = $_POST['aksi'] ?? '';
$id = (int)($_POST['id'] ?? 0);
if (!org_ada($id)) { set_flash('error', 'OKI tidak ditemukan.'); redirect('oki/list.php'); }

if ($aksi === 'ok' || $aksi === 'no') {
    set_verifikasi($id, $aksi === 'ok' ? 'terverifikasi' : 'ditolak');
    set_flash('success', 'Status verifikasi diperbarui.');
} elseif ($aksi === 'hapus') {
    $galat = hapus_org($id);
    set_flash($galat ? 'error' : 'success', $galat ?: 'OKI dihapus.');
} elseif ($aksi === 'akun') {
    toggle_akun_org($id);
    set_flash('success', 'Status akun diperbarui.');
} else {
    set_flash('error', 'Aksi tidak dikenal.');
}
redirect('oki/list.php');
