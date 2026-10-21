<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/data.php';
$user = require_login(['oki']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('barang/saya.php');
csrf_cek('barang/saya.php');
wajib_verified($user, 'barang/saya.php');

$id = (int)($_POST['id'] ?? 0);
$b = get_barang($id);
if (!$b || $b['org'] !== $user['org']) { set_flash('error', 'Barang tidak ditemukan.'); redirect('barang/saya.php'); }
$balik = 'barang/edit.php?id=' . $id;

$nama = trim($_POST['nama'] ?? '');
$deskripsi = trim($_POST['deskripsi'] ?? '');
$kategori = trim($_POST['kategori'] ?? '');
$jumlah = $_POST['jumlah'] ?? '';
$maks = $_POST['maks'] ?? '';
$kondisi = trim($_POST['kondisi'] ?? '');
$lokasi = trim($_POST['lokasi'] ?? '');

$errors = [];
if ($nama === '') $errors[] = "Nama barang wajib diisi.";
if (mb_strlen($nama) > 150) $errors[] = "Nama barang maksimal 150 karakter.";
if (mb_strlen($deskripsi) > 500) $errors[] = "Deskripsi maksimal 500 karakter.";
if (mb_strlen($lokasi) > 150) $errors[] = "Lokasi maksimal 150 karakter.";
if (!int_positif($kategori) || !kategori_ada((int)$kategori)) $errors[] = "Pilih kategori yang tersedia.";
if (!int_positif($jumlah)) $errors[] = "Jumlah total minimal 1 (bilangan bulat).";
if (!int_positif($maks)) $errors[] = "Maksimal durasi pinjam minimal 1 hari.";
if (!in_array($kondisi, $KONDISI, true)) $errors[] = "Kondisi tidak valid.";

if (!$errors) {
    $galat = ubah_barang($id, (int)$kategori, $nama, $deskripsi, (int)$jumlah, (int)$maks, $kondisi, $lokasi);
    if ($galat !== null) $errors[] = $galat;
}
if ($errors) {
    set_flash('error', implode(' ', $errors));
    $_SESSION['old'] = compact('nama', 'deskripsi', 'kategori', 'jumlah', 'maks', 'kondisi', 'lokasi');
    redirect($balik);
}
set_flash('success', 'Data barang diperbarui.');
redirect('barang/saya.php');
