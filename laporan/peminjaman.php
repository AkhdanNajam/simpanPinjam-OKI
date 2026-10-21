<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/data.php';
$user = require_login();
$admin = $user['role'] === 'admin';
$orgFilter = $admin ? 0 : $user['org'];

// Filter: bulan (YYYY-MM), jenis OKI peminjam, kategori barang.
$bulan = get_str('bulan');
if (!bulan_valid($bulan)) $bulan = date('Y-m');
$jenis = get_str('jenis');
if (!in_array($jenis, $JENIS_OKI, true)) $jenis = '';
$kat = (int)get_str('kategori');
$rows = laporan_peminjaman($bulan, $orgFilter, $jenis, $kat);

// Rekap
$statusHitung = []; $perJenis = []; $perKategori = [];
foreach ($rows as &$r) {
    $r['status_tampil'] = status_tampil($r);
    $statusHitung[$r['status_tampil']] = ($statusHitung[$r['status_tampil']] ?? 0) + 1;
    $perJenis[$r['jenis_peminjam']] = ($perJenis[$r['jenis_peminjam']] ?? 0) + 1;
    $perKategori[$r['kategori_teks']] = ($perKategori[$r['kategori_teks']] ?? 0) + 1;
}
unset($r);
ksort($perJenis); ksort($perKategori);
$namaBulan = nama_bulan((int)substr($bulan, 5, 2)) . ' ' . substr($bulan, 0, 4);

if (get_str('format') === 'csv') {
    $out = [];
    foreach ($rows as $r) $out[] = [$r['id'], $r['tgl_pinjam'], $r['tgl_rencana'], $r['tgl_aktual'] ?? '', $r['peminjam_nama'],
        $r['jenis_peminjam'], $r['pemilik_nama'], $r['item_teks'], $r['kategori_teks'], $r['keperluan'], $r['status_tampil']];
    kirim_csv("laporan-peminjaman-$bulan.csv",
        ['ID', 'Tgl Pinjam', 'Rencana Kembali', 'Kembali Aktual', 'Peminjam', 'Jenis OKI Peminjam', 'Pemilik', 'Barang', 'Kategori', 'Keperluan', 'Status'], $out);
}

$page_title = "Laporan Riwayat Peminjaman";
$wajib_login = [];
include __DIR__ . '/../includes/header.php';
$qs = http_build_query(['bulan' => $bulan, 'jenis' => $jenis, 'kategori' => $kat]);
?>
 <section>
 <h2>Laporan Riwayat Peminjaman Bulanan</h2>
 <p class="no-print tab-laporan"><strong>Riwayat Peminjaman</strong> | <a href="inventaris.php">Ringkasan Inventaris Aktif</a></p>
 <form method="get" action="peminjaman.php" class="filter-bar no-print">
 <div><label for="bulan">Bulan</label><input type="month" id="bulan" name="bulan" value="<?php echo e($bulan); ?>"></div>
 <?php if ($admin): ?>
 <div><label for="jenis">Jenis OKI peminjam</label>
 <select id="jenis" name="jenis"><option value="">Semua</option>
 <?php foreach ($JENIS_OKI as $j): ?><option value="<?php echo e($j); ?>" <?php echo $jenis === $j ? 'selected' : ''; ?>><?php echo e($j); ?></option><?php endforeach; ?>
 </select></div>
 <?php endif; ?>
 <div><label for="kategori">Kategori barang</label>
 <select id="kategori" name="kategori"><option value="0">Semua</option>
 <?php foreach (daftar_kategori() as $k): ?><option value="<?php echo (int)$k['id']; ?>" <?php echo $kat === (int)$k['id'] ? 'selected' : ''; ?>><?php echo e($k['nama']); ?></option><?php endforeach; ?>
 </select></div>
 <div class="filter-aksi"><button type="submit">Tampilkan</button>
 <a class="btn" href="peminjaman.php?<?php echo e($qs); ?>&amp;format=csv">Unduh CSV</a>
 <button type="button" onclick="window.print()">Cetak</button></div>
 </form>

 <p><strong>Periode: <?php echo e($namaBulan); ?></strong><?php if (!$admin): ?> &middot; hanya peminjaman yang melibatkan OKI Anda<?php endif; ?></p>
 <div class="stat-grid">
 <div class="stat"><strong><?php echo count($rows); ?></strong><span>Total peminjaman</span></div>
 <div class="stat"><strong><?php echo (int)($statusHitung['selesai'] ?? 0); ?></strong><span>Selesai</span></div>
 <div class="stat"><strong><?php echo (int)(($statusHitung['dipinjam'] ?? 0) + ($statusHitung['terlambat'] ?? 0)); ?></strong><span>Sedang dipinjam</span></div>
 <div class="stat"><strong><?php echo (int)($statusHitung['terlambat'] ?? 0); ?></strong><span>Terlambat</span></div>
 <div class="stat"><strong><?php echo (int)(($statusHitung['ditolak'] ?? 0) + ($statusHitung['dibatalkan'] ?? 0)); ?></strong><span>Ditolak/dibatalkan</span></div>
 </div>

 <div class="dua-kolom">
 <div>
 <h3>Rekap per jenis OKI peminjam</h3>
 <table><thead><tr><th>Jenis</th><th>Jumlah</th></tr></thead><tbody>
 <?php if (!$perJenis): ?><tr><td colspan="2">-</td></tr><?php endif; ?>
 <?php foreach ($perJenis as $k => $n): ?><tr><td><?php echo e($k); ?></td><td><?php echo (int)$n; ?></td></tr><?php endforeach; ?>
 </tbody></table>
 </div>
 <div>
 <h3>Rekap per kategori barang</h3>
 <table><thead><tr><th>Kategori</th><th>Jumlah</th></tr></thead><tbody>
 <?php if (!$perKategori): ?><tr><td colspan="2">-</td></tr><?php endif; ?>
 <?php foreach ($perKategori as $k => $n): ?><tr><td><?php echo e($k); ?></td><td><?php echo (int)$n; ?></td></tr><?php endforeach; ?>
 </tbody></table>
 </div>
 </div>

 <h3>Rincian</h3>
 <div class="table-responsive">
 <table>
 <thead><tr><th>#</th><th>Tanggal</th><th>Barang</th><th>Peminjam</th><th>Pemilik</th><th>Keperluan</th><th>Kembali</th><th>Status</th></tr></thead>
 <tbody>
 <?php if (!$rows): ?>
 <tr><td colspan="8">Tidak ada peminjaman pada periode ini.</td></tr>
 <?php else: foreach ($rows as $r): ?>
 <tr>
 <td><?php echo (int)$r['id']; ?></td>
 <td><?php echo tgl_id($r['tgl_pinjam']); ?> – <?php echo tgl_id($r['tgl_rencana']); ?></td>
 <td><?php echo e($r['item_teks']); ?><br><small><?php echo e($r['kategori_teks']); ?></small></td>
 <td><?php echo e($r['peminjam_nama']); ?> <small>(<?php echo e($r['jenis_peminjam']); ?>)</small></td>
 <td><?php echo e($r['pemilik_nama']); ?></td>
 <td><?php echo e($r['keperluan']); ?></td>
 <td><?php echo tgl_id($r['tgl_aktual']); ?></td>
 <td><?php echo badge($r['status_tampil']); ?></td>
 </tr>
 <?php endforeach; endif; ?>
 </tbody>
 </table>
 </div>
 <p class="catatan">Dicetak <?php echo date('d/m/Y H:i'); ?> oleh <?php echo e($user['nama']); ?>.</p>
 </section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
