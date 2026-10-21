<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/data.php';
require_login(['admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
csrf_cek('index.php');
if (!demo_aktif()) { set_flash('error', 'Reset data demo dinonaktifkan pada mode production.'); redirect('index.php'); }
reset_data_demo();
unset($_SESSION['uid'], $_SESSION['old']);
set_flash('success', 'Data demo dikembalikan ke kondisi awal. Silakan login kembali.');
redirect('login.php');
