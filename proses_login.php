<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/data.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('login.php');
csrf_cek('login.php');

$email = trim($_POST['email'] ?? '');
$password = (string)($_POST['password'] ?? '');
$ketemu = cari_user_email($email);

if (!$ketemu || !password_verify($password, $ketemu['hash'])) {
    set_flash('error', 'Email atau password salah.');
    $_SESSION['old'] = ['email' => $email];
    redirect('login.php');
}
if (!$ketemu['aktif']) {
    set_flash('error', 'Akun dinonaktifkan admin.');
    redirect('login.php');
}
session_regenerate_id(true);
$_SESSION['uid'] = $ketemu['id'];
// "Remember me": perpanjang cookie sesi 30 hari (tanpa centang = hilang saat browser ditutup).
if (!empty($_POST['remember'])) {
    setcookie(session_name(), session_id(), [
        'expires' => time() + 60 * 60 * 24 * 30, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']),
    ]);
}
set_flash('success', 'Selamat datang, ' . $ketemu['nama'] . '.');
redirect('index.php');