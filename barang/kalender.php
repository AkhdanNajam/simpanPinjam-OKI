<?php
$page_title = "Kalender Ketersediaan";
$wajib_login = [];
include __DIR__ . '/../includes/header.php';
$b = get_barang((int)($_GET['id'] ?? 0));
$boleh = $b && ($user['role'] === 'admin' || $b['org'] === $user['org']
    || ($b['status'] === 'aktif' && $b['org_verif'] === 'terverifikasi'));
if (!$boleh) { set_flash('error', 'Barang tidak ditemukan.'); redirect('barang/list.php'); }

$bulan = get_str('bulan');
if (!bulan_valid($bulan)) $bulan = date('Y-m');
$awal = new DateTime($bulan . '-01');
$akhir = (clone $awal)->modify('last day of this month');
$prev = (clone $awal)->modify('-1 month')->format('Y-m');
$next = (clone $awal)->modify('+1 month')->format('Y-m');
$pakai = terpakai_per_hari($b['id'], $awal->format('Y-m-d'), $akhir->format('Y-m-d'));
$jadwal = jadwal_barang($b['id'], $awal->format('Y-m-d'), $akhir->format('Y-m-d'));
$hariIni = date('Y-m-d');
$offset = (int)$awal->format('N') - 1;      // Senin = 0
$jmlHari = (int)$akhir->format('j');
$bisaPinjam = $user['role'] === 'oki' && $b['org'] !== $user['org'] && $b['status'] === 'aktif' && terverifikasi($user);
?>
 <section>
 <h2>Kalender Ketersediaan: <?php echo e($b['nama']); ?></h2>
 <p>Milik <strong><?php echo e($b['org_nama']); ?></strong> &middot; kategori <?php echo e($b['kategori']); ?> &middot; stok total <?php echo (int)$b['total']; ?> &middot; maks. <?php echo (int)$b['maks']; ?> hari &middot; kondisi <?php echo e($b['kondisi']); ?> <?php if ($b['status'] !== 'aktif') echo badge($b['status']); ?></p>
 <?php if ($b['deskripsi'] !== ''): ?><p class="catatan"><?php echo e($b['deskripsi']); ?></p><?php endif; ?>

 <div class="kal-nav">
 <a class="btn" href="?id=<?php echo (int)$b['id']; ?>&amp;bulan=<?php echo e($prev); ?>">&larr;</a>
 <strong><?php echo e(nama_bulan((int)$awal->format('n')) . ' ' . $awal->format('Y')); ?></strong>
 <a class="btn" href="?id=<?php echo (int)$b['id']; ?>&amp;bulan=<?php echo e($next); ?>">&rarr;</a>
 </div>
 <div class="table-responsive">
 <table class="kalender">
 <thead><tr><th>Sen</th><th>Sel</th><th>Rab</th><th>Kam</th><th>Jum</th><th>Sab</th><th>Min</th></tr></thead>
 <tbody>
 <?php
 $sel = 0;
 echo '<tr>';
 for ($i = 0; $i < $offset; $i++) { echo '<td class="kosong-sel"></td>'; $sel++; }
 for ($d = 1; $d <= $jmlHari; $d++) {
     $tgl = $bulan . '-' . sprintf('%02d', $d);
     $terpakai = $pakai[$tgl] ?? 0;
     $sisa = max(0, $b['total'] - $terpakai);
     $kelas = $sisa === 0 ? 'penuh' : ($terpakai > 0 ? 'sebagian' : 'lega');
     if ($tgl < $hariIni) $kelas .= ' lalu';
     if ($tgl === $hariIni) $kelas .= ' hari-ini';
     echo '<td class="' . $kelas . '"><span class="tgl">' . $d . '</span><span class="sisa">' . $sisa . '/' . (int)$b['total'] . '</span></td>';
     $sel++;
     if ($sel % 7 === 0 && $d < $jmlHari) echo '</tr><tr>';
 }
 while ($sel % 7 !== 0) { echo '<td class="kosong-sel"></td>'; $sel++; }
 echo '</tr>';
 ?>
 </tbody>
 </table>
 </div>
 <p class="catatan">Angka pada tiap tanggal = unit tersedia / stok total.
 <span class="legend lega">Tersedia</span> <span class="legend sebagian">Sebagian terpakai</span> <span class="legend penuh">Habis</span></p>

 <h3>Jadwal terpakai bulan ini</h3>
 <div class="table-responsive">
 <table>
 <thead><tr><th>#</th><th>Peminjam</th><th>Jumlah</th><th>Tanggal</th><th>Status</th><th>Keperluan</th></tr></thead>
 <tbody>
 <?php if (!$jadwal): ?>
 <tr><td colspan="6">Belum ada jadwal pada bulan ini.</td></tr>
 <?php else: foreach ($jadwal as $j): ?>
 <tr>
 <td><?php echo (int)$j['id']; ?></td>
 <td><?php echo e($j['peminjam']); ?></td>
 <td><?php echo (int)$j['jumlah']; ?></td>
 <td><?php echo tgl_id($j['tgl_pinjam']); ?> – <?php echo tgl_id($j['tgl_rencana']); ?></td>
 <td><?php echo badge(status_tampil($j)); ?></td>
 <td><?php echo e($j['keperluan']); ?></td>
 </tr>
 <?php endforeach; endif; ?>
 </tbody>
 </table>
 </div>
 <p>
 <?php if ($bisaPinjam): ?><a class="btn" href="../peminjaman/ajukan.php?id=<?php echo (int)$b['id']; ?>">Ajukan Pinjam</a> <?php endif; ?>
 <a href="<?php echo $user['role'] === 'oki' && $b['org'] === $user['org'] ? 'saya.php' : 'list.php'; ?>">&larr; Kembali</a>
 </p>
 </section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
