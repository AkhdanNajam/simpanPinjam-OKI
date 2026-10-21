<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/data.php';
require_login(['admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('kategori/list.php');
csrf_cek('kategori/list.php');

$aksi = $_POST['aksi'] ?? '';
$id = (int)($_POST['id'] ?? 0);
$nama = trim($_POST['nama'] ?? '');

if ($aksi === 'tambah' || $aksi === 'ubah') {
    if ($nama === '' || mb_strlen($nama) > 100) {
        set_flash('error', 'Nama kategori wajib diisi (maksimal 100 karakter).');
        $_SESSION['old'] = ['nama' => $nama];
    } elseif ($aksi === 'ubah' && !kategori_ada($id)) {
        set_flash('error', 'Kategori tidak ditemukan.');
    } else {
        $galat = simpan_kategori($aksi === 'tambah' ? 0 : $id, $nama);
        if ($galat) { set_flash('error', $galat); $_SESSION['old'] = ['nama' => $nama]; }
        else set_flash('success', $aksi === 'tambah' ? 'Kategori ditambahkan.' : 'Kategori diperbarui.');
    }
} elseif ($aksi === 'hapus') {
    if (!kategori_ada($id)) set_flash('error', 'Kategori tidak ditemukan.');
    else {
        $galat = hapus_kategori($id);
        set_flash($galat ? 'error' : 'success', $galat ?: 'Kategori dihapus.');
    }
} else {
    set_flash('error', 'Aksi tidak dikenal.');
}
redirect('kategori/list.php');
