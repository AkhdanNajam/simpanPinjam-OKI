<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/data.php';
$user = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('profil.php');
csrf_cek('profil.php');

$aksi = $_POST['aksi'] ?? '';
if ($aksi === 'profil' && $user['role'] === 'oki') {
    $nama = trim($_POST['nama'] ?? '');
    $jenis = trim($_POST['jenis'] ?? '');
    $kontak = trim($_POST['kontak'] ?? '');
    $pengurus = trim($_POST['pengurus'] ?? '');
    $errors = [];
    if ($nama === '' || mb_strlen($nama) > 150) $errors[] = "Nama organisasi wajib diisi (maksimal 150 karakter).";
    if (!in_array($jenis, $JENIS_OKI, true)) $errors[] = "Jenis OKI tidak valid.";
    if ($pengurus === '' || mb_strlen($pengurus) > 150) $errors[] = "Nama pengurus wajib diisi (maksimal 150 karakter).";
    if ($kontak !== '' && !preg_match('/^[0-9+\-\s]{6,20}$/', $kontak)) $errors[] = "Kontak hanya boleh angka, +, - dan spasi.";
    if (!$errors) {
        $galat = ubah_profil($user['org'], $user['id'], $nama, $jenis, $kontak, $pengurus);
        if ($galat) $errors[] = $galat;
    }
    if ($errors) {
        set_flash('error', implode(' ', $errors));
        $_SESSION['old'] = compact('nama', 'jenis', 'kontak', 'pengurus');
    } else {
        set_flash('success', 'Profil diperbarui.');
    }
} elseif ($aksi === 'password') {
    $lama = (string)($_POST['lama'] ?? '');
    $baru = (string)($_POST['baru'] ?? '');
    $ulang = (string)($_POST['ulang'] ?? '');
    if (mb_strlen($baru) < 6) set_flash('error', 'Password baru minimal 6 karakter.');
    elseif ($baru !== $ulang) set_flash('error', 'Konfirmasi password tidak sama.');
    else {
        $galat = ganti_password($user['id'], $lama, $baru);
        set_flash($galat ? 'error' : 'success', $galat ?: 'Password berhasil diganti.');
    }
} else {
    set_flash('error', 'Aksi tidak dikenal.');
}
redirect('profil.php');
