<?php
$page_title = "Detail Peminjaman";
$wajib_login = [];
include __DIR__ . '/../includes/header.php';
$p = get_peminjaman((int)($_GET['id'] ?? 0));

$id_peminjam = $user['peminjam'] ?? ($user['id_peminjam'] ?? 0);
$org_id = $user['org'] ?? ($user['id_organisasi'] ?? ($_SESSION['org'] ?? 0));

?>
 <section>
 <h2>Peminjaman #<?php echo (int)$p['id']; ?> <?php echo badge(status_tampil($p)); ?></h2>
 <?php
 $langkah = ['menunggu' => 1, 'disetujui' => 2, 'dipinjam' => 3, 'selesai' => 4][$p['status']] ?? 1;
 $label = ['Pengajuan', 'Persetujuan', 'Serah terima', 'Pengembalian'];
 ?>
 <?php if (in_array($p['status'], ['ditolak', 'dibatalkan'], true)): ?>
 <p class="catatan">Pengajuan <?php echo e($p['status']); ?><?php echo $p['alasan'] ? ': ' . e($p['alasan']) : ''; ?>.</p>
 <?php else: ?>
 <ol class="timeline">
 <?php foreach ($label as $i => $l): ?>
 <li class="<?php echo $i < $langkah ? 'selesai' : ''; ?> <?php echo $i === $langkah ? 'berikut' : ''; ?>"><span><?php echo $i + 1; ?></span><?php echo e($l); ?></li>
 <?php endforeach; ?>
 </ol>
 <?php endif; ?>
 <p>
 <strong>Peminjam:</strong> <?php echo e($p['peminjam_nama']); ?><br>
 <strong>Pemilik:</strong> <?php echo e($p['pemilik_nama']); ?><br>
 <strong>Keperluan:</strong> <?php echo e($p['keperluan']); ?><?php if ($p['catatan']): ?> (<?php echo e($p['catatan']); ?>)<?php endif; ?><br>
 <strong>Tanggal:</strong> <?php echo tgl_id($p['tgl_pinjam']); ?> – <?php echo tgl_id($p['tgl_rencana']); ?><br>
 <strong>Dikembalikan:</strong> <?php echo tgl_id($p['tgl_aktual']); ?>
 </p>
 <h3>Barang</h3>
 <div class="table-responsive"><table>
 <thead><tr><th>Barang</th><th>Jumlah</th><th>Kondisi keluar</th><th>Kondisi kembali</th><th>Catatan</th></tr></thead>
 <tbody>
 <?php foreach (item_peminjaman($p['id']) as $i): ?>
 <tr><td><?php echo e($i['nama']); ?></td><td><?php echo (int)$i['jumlah']; ?></td>
 <td><?php echo e($i['kk'] ?? '-'); ?></td><td><?php echo e($i['kb'] ?? '-'); ?></td><td><?php echo e($i['cr'] ?: '-'); ?></td></tr>
 <?php endforeach; ?>
 </tbody></table></div>
 <h3>Riwayat Status</h3>
 <ul class="notif">
 <?php foreach (riwayat_peminjaman($p['id']) as $l): ?>
 <li><small><?php echo e($l['waktu']); ?></small> &mdash; <?php echo e($l['user_nama']); ?>: <?php echo e($l['lama'] ?: 'baru'); ?> → <strong><?php echo e($l['baru']); ?></strong><?php if ($l['ket']): ?> (<?php echo e($l['ket']); ?>)<?php endif; ?></li>
 <?php endforeach; ?>
 </ul>
 <p><a href="list.php">&larr; Kembali</a></p>
 </section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
