<?php
$page_title = "Dashboard OKI";
$wajib_login = ['oki'];
include __DIR__ . '/../includes/header.php';
$flash = get_flash();
$org = get_org($user['org']);
$terverif = (($org['verif'] ?? '') === 'terverifikasi');
$d = dashboard_oki((int)$user['org']);
$terbaru = peminjaman_terbaru((int)$user['org'], 5);
?>
<section>
 <?php if ($flash): ?>
 <p class="flash flash-<?php echo e($flash['type']); ?>"><?php echo e($flash['pesan']); ?></p>
 <?php endif; ?>
 <h2>Halo, <?php echo e($org['nama'] ?? $user['nama']); ?>!</h2>
 <p>Selamat datang di sistem peminjaman barang antar-OKI Polinema.
 <?php if (!$terverif): ?><br><strong>OKI Anda belum terverifikasi Admin</strong>; Anda baru bisa melengkapi <a href="<?php echo $base; ?>profil.php">profil</a>.<?php endif; ?></p>
 <div class="stat-grid">
  <div class="stat"><strong><?php echo (int)($d['total_barang'] ?? 0); ?></strong><span>Total barang milik OKI Anda</span></div>
  <div class="stat"><strong><?php echo (int)($d['masuk'] ?? 0); ?></strong><span>Pengajuan masuk (perlu diproses)</span></div>
  <div class="stat"><strong><?php echo (int)($d['aktif'] ?? 0); ?></strong><span>Peminjaman aktif</span></div>
  <div class="stat"><strong><?php echo (int)($d['total'] ?? 0); ?></strong><span>Total peminjaman (riwayat)</span></div>
 </div>
 <h3>Akses Cepat</h3>
 <p class="akses-cepat">
  <a class="btn" href="<?php echo $base; ?>katalog/list.php">Cari Barang</a>
  <?php if ($terverif): ?>
  <a class="btn" href="barang/saya.php">Kelola Barang</a>
  <a class="btn" href="barang/tambah.php">Tambah Barang</a>
  <a class="btn" href="peminjaman/list.php">Peminjaman Saya</a>
  <a class="btn" href="laporan/peminjaman.php">Laporan</a>
  <?php else: ?>
  <a class="btn" href="<?php echo $base; ?>profil.php">Lengkapi Profil</a>
  <?php endif; ?>
 </p>
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
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
