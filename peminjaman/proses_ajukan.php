<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/data.php';
$user = require_login(['oki']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('barang/list.php');
csrf_cek('barang/list.php');
wajib_verified($user, 'barang/list.php');

$idBarang = (int)($_POST['barang'] ?? 0);
$jumlah = $_POST['jumlah'] ?? '';
$f = trim($_POST['tgl_pinjam'] ?? '');
$t = trim($_POST['tgl_kembali'] ?? '');
$keperluan = trim($_POST['keperluan'] ?? '');
$catatan = trim($_POST['catatan'] ?? '');
$old = compact('jumlah', 'keperluan', 'catatan') + ['tgl_pinjam' => $f, 'tgl_kembali' => $t];
$balik = 'peminjaman/ajukan.php?id=' . $idBarang;

$b = get_barang($idBarang);
if (!$b || $b['status'] !== 'aktif' || $b['org'] === $user['org'] || $b['org_verif'] !== 'terverifikasi') {
    set_flash('error', 'Barang tidak tersedia untuk dipinjam.');
    redirect('barang/list.php');
}

$errors = [];
if ($keperluan === '') $errors[] = "Keperluan wajib diisi.";
if (!int_positif($jumlah)) $errors[] = "Jumlah minimal 1.";
if (!tgl_valid($f) || !tgl_valid($t)) {
    $errors[] = "Isi tanggal pinjam dan kembali dengan benar.";
} else {
    if ($f < date('Y-m-d')) $errors[] = "Tanggal pinjam tidak boleh di masa lalu.";
    if ($t < $f) $errors[] = "Tanggal kembali sebelum tanggal pinjam.";
    elseif (jumlah_hari($f, $t) > $b['maks']) $errors[] = "Melebihi batas maksimal {$b['maks']} hari.";
    if (!$errors && int_positif($jumlah)) {
        $sisa = stok_tersedia($idBarang, $f, $t);
        if ((int)$jumlah > $sisa) $errors[] = "Stok tidak cukup / bentrok jadwal (tersedia $sisa).";
    }
}
if ($errors) {
    set_flash('error', implode(' ', $errors));
    $_SESSION['old'] = $old;
    redirect($balik);
}

$pid = buat_peminjaman($user, $b, (int)$jumlah, $f, $t, $keperluan, $catatan);
if ($pid === null) {
    set_flash('error', 'Stok tidak cukup / bentrok jadwal.');
    $_SESSION['old'] = $old;
    redirect($balik);
}
set_flash('success', 'Pengajuan terkirim, menunggu persetujuan pemilik.');
redirect('peminjaman/list.php');
