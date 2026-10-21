<?php
$page_title = "Beranda";
$wajib_login = [];   // tamu otomatis diarahkan ke login.php
include __DIR__ . '/includes/header.php';
$flash = get_flash();
$old = $_SESSION['old'] ?? []; unset($_SESSION['old']);
$admin = $user && $user['role'] === 'admin';
$oki = $user && $user['role'] === 'oki';
$org = $oki ? get_org($user['org']) : null;
$d = $admin ? dashboard_admin() : ($oki ? dashboard_oki($user['org']) : null);
$terbaru = $user ? peminjaman_terbaru($oki ? $user['org'] : 0, 5) : [];
?>
 <section>
 <?php if ($flash): ?>
 <p class="flash flash-<?php echo e($flash['type']); ?>"><?php echo e($flash['pesan']); ?></p>
 <?php endif; ?>

 <?php if ($oki): ?>
 <h2>Halo, <?php echo e($org['nama'] ?? $user['nama']); ?>!</h2>
 ...

 <?php elseif ($oki): ?>
 <h2>Halo, <?php echo e($org['nama'] ?? $user['nama']); ?>!</h2>
 <p>Selamat datang di sistem peminjaman barang antar-OKI Polinema.
 <?php if (($org['verif'] ?? '') !== 'terverifikasi'): ?><br><strong>OKI Anda belum terverifikasi Admin</strong>; Anda baru bisa melengkapi <a href="profil.php">profil</a>.<?php endif; ?></p>
 <div class="stat-grid">
 <div class="stat"><strong><?php echo $d['total_barang']; ?></strong><span>Total barang milik OKI Anda</span></div>
 <div class="stat"><strong><?php echo $d['masuk']; ?></strong><span>Pengajuan masuk (perlu diproses)</span></div>
 <div class="stat"><strong><?php echo $d['aktif']; ?></strong><span>Peminjaman aktif</span></div>
 <div class="stat"><strong><?php echo $d['total']; ?></strong><span>Total peminjaman (riwayat)</span></div>
 </div>
 <h3>Akses Cepat</h3>
 <p class="akses-cepat">
 <a class="btn" href="barang/list.php">Cari Barang</a>
 <a class="btn" href="barang/saya.php">Kelola Barang</a>
 <a class="btn" href="barang/tambah.php">Tambah Barang</a>
 <a class="btn" href="peminjaman/list.php">Peminjaman Saya</a>
 <a class="btn" href="laporan/peminjaman.php">Laporan</a>
 </p>

 <?php else: ?>
 <h2>Dashboard Admin</h2>
 <div class="stat-grid">
 <div class="stat"><strong><?php echo $d['oki_menunggu']; ?></strong><span>OKI menunggu verifikasi</span></div>
 <div class="stat"><strong><?php echo $d['oki_total']; ?></strong><span>Total OKI</span></div>
 <div class="stat"><strong><?php echo $d['barang_aktif']; ?></strong><span>Barang aktif</span></div>
 <div class="stat"><strong><?php echo $d['berjalan']; ?></strong><span>Peminjaman berjalan</span></div>
 <div class="stat"><strong><?php echo $d['terlambat']; ?></strong><span>Terlambat dikembalikan</span></div>
 <div class="stat"><strong><?php echo $d['total']; ?></strong><span>Total peminjaman</span></div>
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
 <thead><tr><th>#</th><th>Barang</th><th>Peminjam → Pemilik</th><th>Tanggal</th><th>Status</th></tr></thead>
 <tbody>
 <?php if (!$terbaru): ?>
 <tr><td colspan="5">Belum ada peminjaman.</td></tr>
 <?php else: foreach ($terbaru as $p): ?>
 <tr>
 <td><a href="peminjaman/detail.php?id=<?php echo (int)$p['id']; ?>"><?php echo (int)$p['id']; ?></a></td>
 <td><?php echo e($p['item_teks']); ?></td>
 <td><?php echo e($p['peminjam_nama']); ?> → <?php echo e($p['pemilik_nama']); ?></td>
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