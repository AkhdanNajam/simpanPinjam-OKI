<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (ob_get_level() === 0) ob_start();   // agar redirect() tetap bisa dipanggil setelah header ini di-include
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/data.php';

// Halaman yang butuh login wajib mendefinisikan $wajib_login sebelum include header.php:
//   $wajib_login = [];                 -> wajib login, role apa saja
//   $wajib_login = ['admin', 'oki'];   -> wajib login, role tertentu
// Jika tidak didefinisikan (atau diisi false), halaman publik dan tamu tetap bisa mengakses.
$user = (isset($wajib_login) && $wajib_login !== false)
    ? require_login((array)$wajib_login)
    : user_now();
$jumlahNotif = $user ? jumlah_notif_baru($user['id']) : 0;

$__jobsheetRoot = dirname(__DIR__);
$__scriptDir = dirname($_SERVER['SCRIPT_FILENAME']);
$__rel = ltrim(str_replace('\\', '/', substr($__scriptDir, strlen($__jobsheetRoot))), '/');
$base = $__rel === '' ? '' : str_repeat('../', substr_count($__rel, '/') + 1);

if (!function_exists('nav_link')) {
    function nav_link($base, $path, $label) {
        $cur = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $on = substr($cur, -strlen('/' . $path)) === '/' . $path;
        return '<li><a href="' . $base . $path . '"' . ($on ? ' class="aktif" aria-current="page"' : '') . '>' . $label . '</a></li>';
    }
}
$notifLabel = 'Notifikasi' . ($jumlahNotif ? ' <span class="pill">' . (int)$jumlahNotif . '</span>' : '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1">
 <title>SIPINJAM OKI<?php echo isset($page_title) ? ' | ' . e($page_title) : ''; ?></title>
 <link rel="preconnect" href="https://fonts.googleapis.com">
 <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
 <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
 <link rel="stylesheet" href="<?php echo $base; ?>assets/css/style.css">
</head>
<!-- Class selalu "app": tamu dan user login memakai layout sidebar yang sama -->
<body class="app">
 <header>
  <h1><img src="<?php echo $base; ?>assets/img/logo.png" alt="" class="logo"><span>Pinjam OKI<small>Polinema</small></span></h1>
  <button type="button" id="nav-toggle-btn" class="nav-toggle-label" aria-label="Menu">&#9776;</button>
  <nav>
   <ul>
    <!-- Menu utama yang bisa diakses publik (tamu & user) -->
    <?php echo nav_link($base, 'index.php', 'Beranda'); ?>
    <?php echo nav_link($base, 'barang/list.php', 'Katalog Barang'); ?>
    <?php echo nav_link($base, 'oki/list.php', 'Daftar OKI'); ?>

    <?php if ($user): ?>
     <!-- Menu khusus pengguna yang sudah login -->
     <?php if ($user['role'] === 'oki'): ?>
      <?php echo nav_link($base, 'barang/saya.php', 'Barang Saya'); ?>
      <?php echo nav_link($base, 'barang/tambah.php', 'Tambah Barang'); ?>
     <?php endif; ?>

     <?php echo nav_link($base, 'peminjaman/list.php', 'Peminjaman'); ?>

     <?php if ($user['role'] === 'admin'): ?>
      <?php echo nav_link($base, 'kategori/list.php', 'Kategori'); ?>
     <?php endif; ?>

     <?php echo nav_link($base, 'laporan/peminjaman.php', 'Laporan'); ?>
     <?php echo nav_link($base, 'notifikasi.php', $notifLabel); ?>
     <?php echo nav_link($base, 'profil.php', 'Profil'); ?>

     <li>
      <form method="post" action="<?php echo $base; ?>logout.php" class="inline nav-logout">
       <?php echo csrf_field(); ?>
       <button type="submit">Logout</button>
      </form>
     </li>
    <?php else: ?>
     <!-- Menu untuk tamu yang belum login -->
     <?php echo nav_link($base, 'login.php', 'Login'); ?>
    <?php endif; ?>
   </ul>

   <!-- Blok akun di bawah sidebar -->
   <div class="akun">
    <?php if ($user): ?>
     <strong><?php echo e($user['nama'] ?? ''); ?></strong>
     <?php echo $user['role'] === 'admin' ? 'Admin' : 'Pengelola OKI'; ?>
    <?php else: ?>
     <strong>Pengunjung</strong>
     Belum login
    <?php endif; ?>
   </div>
  </nav>
 </header>
 <main>