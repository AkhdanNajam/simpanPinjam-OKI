<?php
$page_title = "Beranda";
// $wajib_login sengaja TIDAK didefinisikan: beranda bersifat publik, tamu boleh melihatnya.
include __DIR__ . '/includes/header.php';
// Sudah login -> ke dashboard sesuai role (admin/ atau oki/).
if ($user) redirect($user['role'] === 'admin' ? 'admin/index.php' : 'oki/index.php');
$flash = get_flash();
?>
<section>
 <?php if ($flash): ?>
 <p class="flash flash-<?php echo e($flash['type']); ?>"><?php echo e($flash['pesan']); ?></p>
 <?php endif; ?>
 <h2>Selamat Datang di Sistem Peminjaman Barang OKI Polinema</h2>
 <p>Silakan jelajahi katalog barang yang tersedia atau masuk ke akun Anda untuk mulai melakukan peminjaman.</p>
 <p class="akses-cepat">
  <a class="btn" href="katalog/list.php">Lihat Katalog Barang</a>
  <a class="btn" href="login.php">Login ke Sistem</a>
 </p>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>