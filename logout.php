<?php
session_start();
require_once __DIR__ . '/includes/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
csrf_cek('index.php');
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $c = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $c['path'], $c['domain'], $c['secure'], $c['httponly']);
}
session_destroy();
session_start();
session_regenerate_id(true);
set_flash('success', 'Anda sudah keluar.');
redirect('login.php');
