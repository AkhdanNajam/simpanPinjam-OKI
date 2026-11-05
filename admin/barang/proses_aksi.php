<?php
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/data.php';
$user = require_login(['admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin/barang/list.php');
csrf_cek('admin/barang/list.php');

$aksi = $_POST['aksi'] ?? '';
$id = (int)($_POST['id'] ?? 0);
if (!get_barang($id)) {
    set_flash('error', 'Barang tidak ditemukan.');
    redirect('admin/barang/list.php');
}

if ($aksi === 'toggle') {
    toggle_barang($id);
    set_flash('success', 'Status barang diperbarui.');
} else {
    set_flash('error', 'Aksi tidak dikenal.');
}
redirect('admin/barang/list.php');
