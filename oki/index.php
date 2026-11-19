<?php
$page_title = "Dashboard OKI";
$wajib_login = ['oki'];
include __DIR__ . '/../includes/header.php';
$flash = get_flash();
$org = get_org($user['org']);
$terverif = (($org['verif'] ?? '') === 'terverifikasi');
$d = dashboard_oki((int)$user['org']);
$terbaru = peminjaman_terbaru((int)$user['org'], 5);
$tersedia = barang_tersedia((int)$user['org'], 3);
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
  <a class="btn" href="<?php echo $base; ?>laporan/peminjaman.php">Laporan</a>
  <?php else: ?>
  <a class="btn" href="<?php echo $base; ?>profil.php">Lengkapi Profil</a>
  <?php endif; ?>
 </p>

 <div class="section-head">
  <h3>Barang Tersedia</h3>
  <a class="lihat-semua" href="<?php echo $base; ?>katalog/list.php">Lihat Semua &rarr;</a>
 </div>
 <div class="barang-grid">
  <?php if (!$tersedia): ?>
   <p>Belum ada barang yang tersedia.</p>
  <?php else: foreach ($tersedia as $b): ?>
   <div class="barang-card">
    <div class="barang-atas">
     <?php if (!empty($b['foto'])): ?>
      <img class="barang-foto" src="<?php echo $base . e($b['foto']); ?>" alt="<?php echo e($b['nama']); ?>">
     <?php else: ?>
      <div class="barang-foto placeholder">
       <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.2">
        <rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="8.5" cy="8.5" r="1.5"/>
        <path d="M3 17l5-5 4 4 3-3 6 6"/>
       </svg>
      </div>
     <?php endif; ?>
     <div class="barang-info">
      <strong><?php echo e($b['nama']); ?></strong>
      <span>Pemilik : <?php echo e($b['pemilik_nama']); ?></span>
      <span>Tersedia : <?php echo (int)$b['tersedia']; ?></span>
     </div>
    </div>
    <a class="btn-detail" href="<?php echo $base; ?>katalog/detail.php?id=<?php echo (int)$b['id']; ?>">
     <span>Lihat Detail</span><span>&rsaquo;</span>
    </a>
   </div>
  <?php endforeach; endif; ?>
 </div>

 <div class="section-head">
    <h3>Peminjaman Terbaru</h3>
    <a class="lihat-semua" href="peminjaman/list.php">
        Lihat Semua &rarr;
    </a>
 </div>
 <div class="table-responsive">
  <table>
   <thead><tr><th>#</th><th>Barang</th><th>Peminjam &rarr; Pemilik</th><th>Tanggal</th><th>Status</th></tr></thead>
   <tbody>
   <?php if (!$terbaru): ?>
    <tr><td colspan="5">Belum ada peminjaman.</td></tr>
   <?php else: foreach ($terbaru as $p): ?>
    <tr>
     <td><?php echo (int)$p['id']; ?></td>
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