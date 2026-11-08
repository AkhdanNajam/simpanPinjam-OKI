<?php
$page_title = "Dashboard Admin";
$wajib_login = ['admin'];
include __DIR__ . '/../includes/header.php';
$flash = get_flash();
$d = dashboard_admin();
$terbaru = peminjaman_terbaru(0, 5);
?>
<section class="dash-admin">
 <?php if ($flash): ?>
 <p class="flash flash-<?php echo e($flash['type']); ?>"><?php echo e($flash['pesan']); ?></p>
 <?php endif; ?>
 <h2>Dashboard Admin</h2>

 <div class="stat-grid">
  <div class="stat"><strong><?php echo (int)($d['oki_menunggu'] ?? 0); ?></strong><span>OKI Menunggu verifikasi</span></div>
  <div class="stat"><strong><?php echo (int)($d['oki_total'] ?? 0); ?></strong><span>Total OKI</span></div>
  <div class="stat"><strong><?php echo (int)($d['barang_aktif'] ?? 0); ?></strong><span>Barang aktif</span></div>
  <div class="stat"><strong><?php echo (int)($d['berjalan'] ?? 0); ?></strong><span>Peminjaman berjalan</span></div>
  <div class="stat"><strong><?php echo (int)($d['terlambat'] ?? 0); ?></strong><span>Terlambat dikembalikan</span></div>
  <div class="stat"><strong><?php echo (int)($d['total'] ?? 0); ?></strong><span>Total Peminjaman</span></div>
 </div>

 <h3>Akses Cepat</h3>
 <p class="akses-cepat">
  <a class="btn" href="organisasi/list.php">Verifikasi OKI</a>
  <a class="btn" href="kategori/list.php">Kelola Kategori</a>
  <a class="btn" href="peminjaman/list.php">Semua Peminjaman</a>
  <a class="btn" href="<?php echo $base; ?>laporan/peminjaman.php">Laporan</a>
 </p>

 <h3>Peminjaman Terbaru <a class="lihat-semua" href="peminjaman/list.php">Lihat Semua &rarr;</a></h3>
 <div class="table-responsive">
  <table>
   <thead><tr><th>No</th><th>Barang</th><th>Peminjam</th><th>Pemilik</th><th>Status</th></tr></thead>
   <tbody>
   <?php if (!$terbaru): ?>
    <tr><td colspan="5">Belum ada peminjaman.</td></tr>
   <?php else: $no = 1; foreach ($terbaru as $p): ?>
    <tr>
     <td><a href="<?php echo $base; ?>peminjaman/detail.php?id=<?php echo (int)$p['id']; ?>"><?php echo $no++; ?></a></td>
     <td><?php echo e($p['item_teks']); ?></td>
     <td><?php echo e($p['peminjam_nama']); ?></td>
     <td><?php echo e($p['pemilik_nama']); ?></td>
     <td><?php echo badge(status_tampil($p)); ?></td>
    </tr>
   <?php endforeach; endif; ?>
   </tbody>
  </table>
 </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>