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
    <!-- Menu utama yang selalu tampil -->
    <?php echo nav_link($base, 'index.php', 'Beranda'); ?>

<!-- Menu Khusus Admin -->
<?php if ($user && $user['role'] === 'admin'): ?>
    <?php echo nav_link($base, 'admin/organisasi/list.php', 'Daftar OKI'); ?>
    <?php echo nav_link($base, 'barang/list.php', 'Daftar Barang'); ?>
    <?php echo nav_link($base, 'peminjaman/list.php', 'Daftar Peminjaman'); ?>
    <?php echo nav_link($base, 'kategori/list.php', 'Kategori'); ?>
    <?php endif; ?>

<!-- Menu Khusus OKI -->
<?php if ($user && $user['role'] === 'oki'): ?>
    <?php echo nav_link($base, 'oki/peminjaman/list.php', 'Daftar Peminjaman'); ?>
    <?php echo nav_link($base, 'barang/saya.php', 'Barang Saya'); ?>
    <?php echo nav_link($base, 'barang/tambah.php', 'Tambah Barang'); ?>
    <?php endif; ?>

<!-- Menu Umum -->
<?php echo nav_link($base, 'laporan/peminjaman.php', 'Laporan'); ?>
<?php echo nav_link($base, 'notifikasi.php', $notifLabel); ?>
<?php echo nav_link($base, 'profil.php', 'Profil'); ?>
   </ul>

   <!-- Bagian Bawah: Tombol Log out, Garis Pemisah, dan Info Akun -->
   <div style="margin-top: auto; width: 100%;">
       <?php if ($user): ?>
       <ul style="list-style: none; padding: 0; margin: 0;">
        <li>
         <form method="post" action="<?php echo $base; ?>logout.php" class="inline nav-logout" style="margin: 0;">
          <?php echo csrf_field(); ?>
          <button type="submit" style="width: 100%; text-align: left; background: transparent; border: none; color: inherit; cursor: pointer; padding: 10px 15px; border-radius: 6px; transition: background 0.2s;" onmouseover="this.style.background='rgba(255, 255, 255, 0.1)'" onmouseout="this.style.background='transparent'">Log out</button>
         </form>
        </li>
       </ul>
       <?php endif; ?>

       <hr style="border: none; border-top: 1px solid rgba(255, 255, 255, 0.2); margin: 5px 0;">

       <div class="akun" style="padding: 10px 15px 15px 15px;">
        <?php if ($user): ?>
         <strong><?php echo e($user['nama'] ?? ''); ?></strong><br>
         <small><?php echo $user['role'] === 'admin' ? 'Admin' : 'OKI'; ?></small>
        <?php else: ?>
         <strong>Pengunjung</strong><br>
         <small>Belum login</small>
        <?php endif; ?>
       </div>
   </div>
</nav>
 </header>
 <main>