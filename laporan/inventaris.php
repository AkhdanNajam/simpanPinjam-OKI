<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/data.php';
$user = require_login();
$admin = $user['role'] === 'admin';
$org_id = $user['org'] ?? ($user['id_organisasi'] ?? ($_SESSION['org'] ?? 0));
$orgFilter = $admin ? 0 : $org_id;

$jenis = get_str('jenis');
if (!in_array($jenis, $JENIS_OKI, true)) $jenis = '';
$kat = (int)get_str('kategori');
$rows = inventaris($orgFilter, $kat, $jenis);

$totalUnit = $keluar = $tersedia = $telat = 0; $perKategori = [];
foreach ($rows as $r) {
    $totalUnit += $r['total']; $keluar += $r['keluar']; $tersedia += $r['tersedia']; $telat += $r['terlambat'];
    $x = $perKategori[$r['kategori']] ?? ['jenis' => 0, 'unit' => 0, 'tersedia' => 0];
    $x['jenis']++; $x['unit'] += $r['total']; $x['tersedia'] += $r['tersedia'];
    $perKategori[$r['kategori']] = $x;
}
ksort($perKategori);

if (get_str('format') === 'csv') {
    $out = [];
    foreach ($rows as $r) $out[] = [$r['org_nama'], $r['jenis'], $r['nama'], $r['kategori'], $r['total'], $r['keluar'], $r['tersedia'], $r['kondisi'], $r['lokasi'], $r['pantau']];
    kirim_csv('laporan-inventaris-' . date('Y-m-d') . '.csv',
        ['OKI', 'Jenis OKI', 'Barang', 'Kategori', 'Jumlah Total', 'Sedang Dipinjam', 'Tersedia Hari Ini', 'Kondisi', 'Lokasi', 'Pemantauan'], $out);
}

$page_title = "Laporan Inventaris Aktif";
$wajib_login = [];
include __DIR__ . '/../includes/header.php';
$qs = http_build_query(['jenis' => $jenis, 'kategori' => $kat]);
?>
 <section>
 <h2>Laporan Ringkasan Inventaris Aktif</h2>
 <p class="no-print tab-laporan"><a href="peminjaman.php">Riwayat Peminjaman</a> | <strong>Ringkasan Inventaris Aktif</strong></p>
 <form method="get" action="inventaris.php" class="filter-bar no-print">
 <?php if ($admin): ?>
 <div><label for="jenis">Jenis OKI pemilik</label>
 <select id="jenis" name="jenis"><option value="">Semua</option>
 <?php foreach ($JENIS_OKI as $j): ?><option value="<?php echo e($j); ?>" <?php echo $jenis === $j ? 'selected' : ''; ?>><?php echo e($j); ?></option><?php endforeach; ?>
 </select></div>
 <?php endif; ?>
 <div><label for="kategori">Kategori</label>
 <select id="kategori" name="kategori"><option value="0">Semua</option>
 <?php foreach (daftar_kategori() as $k): ?><option value="<?php echo (int)$k['id']; ?>" <?php echo $kat === (int)$k['id'] ? 'selected' : ''; ?>><?php echo e($k['nama']); ?></option><?php endforeach; ?>
 </select></div>
 <div class="filter-aksi"><button type="submit">Tampilkan</button>
 <a class="btn" href="inventaris.php?<?php echo e($qs); ?>&amp;format=csv">Unduh CSV</a>
 <button type="button" onclick="window.print()">Cetak</button></div>
 </form>

 <p><strong>Per tanggal <?php echo date('d/m/Y'); ?></strong><?php if (!$admin): ?> &middot; barang aktif milik OKI Anda<?php endif; ?></p>
 <div class="stat-grid">
 <div class="stat"><strong><?php echo count($rows); ?></strong><span>Jenis barang aktif</span></div>
 <div class="stat"><strong><?php echo $totalUnit; ?></strong><span>Total unit</span></div>
 <div class="stat"><strong><?php echo $keluar; ?></strong><span>Sedang dipinjam</span></div>
 <div class="stat"><strong><?php echo $tersedia; ?></strong><span>Tersedia hari ini</span></div>
 <div class="stat"><strong><?php echo $telat; ?></strong><span>Unit terlambat</span></div>
 </div>

 <h3>Rekap per kategori</h3>
 <table><thead><tr><th>Kategori</th><th>Jenis barang</th><th>Total unit</th><th>Tersedia</th></tr></thead><tbody>
 <?php if (!$perKategori): ?><tr><td colspan="4">-</td></tr><?php endif; ?>
 <?php foreach ($perKategori as $k => $x): ?>
 <tr><td><?php echo e($k); ?></td><td><?php echo (int)$x['jenis']; ?></td><td><?php echo (int)$x['unit']; ?></td><td><?php echo (int)$x['tersedia']; ?></td></tr>
 <?php endforeach; ?>
 </tbody></table>

 <h3>Rincian</h3>
 <div class="table-responsive">
 <table>
 <thead><tr><th>Barang</th><th>Kategori</th><?php if ($admin): ?><th>Pemilik</th><?php endif; ?><th>Total</th><th>Dipinjam</th><th>Tersedia</th><th>Kondisi</th><th>Lokasi</th><th>Pemantauan</th></tr></thead>
 <tbody>
 <?php if (!$rows): ?>
 <tr><td colspan="<?php echo $admin ? 9 : 8; ?>">Tidak ada barang aktif.</td></tr>
 <?php else: foreach ($rows as $r): ?>
 <tr>
 <td><?php echo e($r['nama']); ?></td>
 <td><?php echo e($r['kategori']); ?></td>
 <?php if ($admin): ?><td><?php echo e($r['org_nama']); ?> <small>(<?php echo e($r['jenis']); ?>)</small></td><?php endif; ?>
 <td><?php echo (int)$r['total']; ?></td>
 <td><?php echo (int)$r['keluar']; ?></td>
 <td><?php echo (int)$r['tersedia']; ?></td>
 <td><?php echo e($r['kondisi']); ?></td>
 <td><?php echo e($r['lokasi'] ?: '-'); ?></td>
 <td><?php echo badge($r['pantau']); ?></td>
 </tr>
 <?php endforeach; endif; ?>
 </tbody>
 </table>
 </div>
 <p class="catatan">Dicetak <?php echo date('d/m/Y H:i'); ?> oleh <?php echo e($user['nama']); ?>.</p>
 </section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
