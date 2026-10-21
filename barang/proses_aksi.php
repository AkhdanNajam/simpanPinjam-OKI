<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/data.php';
$user = require_login(['oki', 'admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('barang/list.php');
csrf_cek('barang/list.php');
$kembali = $user['role'] === 'admin' ? 'barang/list.php' : 'barang/saya.php';
if ($user['role'] === 'oki') wajib_verified($user, $kembali);

$aksi = $_POST['aksi'] ?? '';
$id = (int)($_POST['id'] ?? 0);
$b = get_barang($id);
if (!$b || ($user['role'] === 'oki' && $b['org'] !== $user['org'])) {
    set_flash('error', 'Barang tidak ditemukan.');
    redirect($kembali);
}

if ($aksi === 'toggle') {
    toggle_barang($id);
    set_flash('success', 'Status barang diperbarui.');
} elseif ($aksi === 'hapus' && $user['role'] === 'oki') {
    if (barang_pernah_dipinjam($id)) {
        set_flash('error', 'Barang punya riwayat peminjaman, nonaktifkan saja.');
        redirect($kembali);
    }
    hapus_barang($id);
    set_flash('success', 'Barang dihapus.');
} else {
    set_flash('error', 'Aksi tidak dikenal.');
}
redirect($kembali);
