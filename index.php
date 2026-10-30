<?php
$page_title = "Beranda";
// $wajib_login sengaja TIDAK didefinisikan: beranda bersifat publik, tamu boleh melihatnya.

include __DIR__ . '/includes/header.php';
$flash = get_flash();
$admin = $user && $user['role'] === 'admin';
$oki = $user && $user['role'] === 'oki';
$org = $oki ? get_org($user['org']) : null;
$terverif = $oki && (($org['verif'] ?? '') === 'terverifikasi');
$d = $admin ? dashboard_admin() : ($oki ? dashboard_oki($user['org']) : null);
$terbaru = $user ? peminjaman_terbaru($oki ? $user['org'] : 0, 5) : [];
?>
<section>
 <?php if ($flash): ?>
 <p class="flash flash-<?php echo e($flash['type']); ?>"><?php echo e($flash['pesan']); ?></p>
 <?php endif; ?>

 <?php if (!$user): ?>
 <!-- Tampilan untuk tamu (belum login) -->
 <h2>Selamat Datang di Sistem Peminjaman Barang OKI Polinema</h2>
 <p>Silakan jelajahi katalog barang yang tersedia atau masuk ke akun Anda untuk mulai melakukan peminjaman.</p>
 <p class="akses-cepat">
  <a class="btn" href="barang/list.php">Lihat Katalog Barang</a>
  <a class="btn" href="login.php">Login ke Sistem</a>
 </p>

 <?php elseif ($oki): ?>
 <!-- Dashboard pengelola OKI -->
 <h2>Halo, <?php echo e($org['nama'] ?? $user['nama']); ?>!</h2>
 <p>Selamat datang di sistem peminjaman barang antar-OKI Polinema.
 <?php if (!$terverif): ?><br><strong>OKI Anda belum terverifikasi Admin</strong>; Anda baru bisa melengkapi <a href="profil.php">profil</a>.<?php endif; ?></p>
 <div class="stat-grid">
  <div class="stat"><strong><?php echo (int)($d['total_barang'] ?? 0); ?></strong><span>Total barang milik OKI Anda</span></div>
  <div class="stat"><strong><?php echo (int)($d['masuk'] ?? 0); ?></strong><span>Pengajuan masuk (perlu diproses)</span></div>
  <div class="stat"><strong><?php echo (int)($d['aktif'] ?? 0); ?></strong><span>Peminjaman aktif</span></div>
  <div class="stat"><strong><?php echo (int)($d['total'] ?? 0); ?></strong><span>Total peminjaman (riwayat)</span></div>
 </div>
 <h3>Akses Cepat</h3>
 <p class="akses-cepat">
  <a class="btn" href="barang/list.php">Cari Barang</a>
  <?php if ($terverif): ?>
  <a class="btn" href="barang/saya.php">Kelola Barang</a>
  <a class="btn" href="barang/tambah.php">Tambah Barang</a>
  <a class="btn" href="peminjaman/list.php">Peminjaman Saya</a>
  <a class="btn" href="laporan/peminjaman.php">Laporan</a>
  <?php else: ?>
  <a class="btn" href="profil.php">Lengkapi Profil</a>
  <?php endif; ?>
 </p>

 <?php elseif ($admin): ?>
 <!-- Dashboard admin -->
 <h2>Dashboard Admin</h2>
 <div class="stat-grid">
  <div class="stat"><strong><?php echo (int)($d['oki_menunggu'] ?? 0); ?></strong><span>OKI menunggu verifikasi</span></div>
  <div class="stat"><strong><?php echo (int)($d['oki_total'] ?? 0); ?></strong><span>Total OKI</span></div>
  <div class="stat"><strong><?php echo (int)($d['barang_aktif'] ?? 0); ?></strong><span>Barang aktif</span></div>
  <div class="stat"><strong><?php echo (int)($d['berjalan'] ?? 0); ?></strong><span>Peminjaman berjalan</span></div>
  <div class="stat"><strong><?php echo (int)($d['terlambat'] ?? 0); ?></strong><span>Terlambat dikembalikan</span></div>
  <div class="stat"><strong><?php echo (int)($d['total'] ?? 0); ?></strong><span>Total peminjaman</span></div>
 </div>
 <h3>Akses Cepat</h3>
 <p class="akses-cepat">
  <a class="btn" href="oki/list.php">Verifikasi OKI</a>
  <a class="btn" href="kategori/list.php">Kelola Kategori</a>
  <a class="btn" href="barang/list.php">Moderasi Barang</a>
  <a class="btn" href="peminjaman/list.php">Semua Peminjaman</a>
  <a class="btn" href="laporan/peminjaman.php">Laporan</a>
 </p>
 <?php endif; ?>

 <?php if ($user): ?>
 <h3>Peminjaman Terbaru <a class="lihat-semua" href="peminjaman/list.php">Lihat semua &rarr;</a></h3>
 <div class="table-responsive">
  <table>
   <thead><tr><th>#</th><th>Barang</th><th>Peminjam &rarr; Pemilik</th><th>Tanggal</th><th>Status</th></tr></thead>
   <tbody>
   <?php if (!$terbaru): ?>
    <tr><td colspan="5">Belum ada peminjaman.</td></tr>
   <?php else: foreach ($terbaru as $p): ?>
    <tr>
     <td><a href="peminjaman/detail.php?id=<?php echo (int)$p['id']; ?>"><?php echo (int)$p['id']; ?></a></td>
     <td><?php echo e($p['item_teks']); ?></td>
     <td><?php echo e($p['peminjam_nama']); ?> &rarr; <?php echo e($p['pemilik_nama']); ?></td>
     <td><?php echo tgl_id($p['tgl_pinjam']); ?> – <?php echo tgl_id($p['tgl_rencana']); ?></td>
     <td><?php echo badge(status_tampil($p)); ?></td>
    </tr>
   <?php endforeach; endif; ?>
   </tbody>
  </table>
 </div>
 <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>