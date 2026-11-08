<?php
$page_title = "Peminjaman";
$wajib_login = ['admin'];
include __DIR__ . '/../../includes/header.php';
$flash = get_flash();

$semua = daftar_peminjaman('semua');
$KOND = $KONDISI;

function tabel_pinjam(array $rows, string $mode, array $KOND): void { ?>
 <div class="table-responsive">
 <table>
 <thead><tr><th>#</th>
    <th>Barang</th>
    <th><?php echo $mode === 'masuk' ? 'Peminjam' : ($mode === 'keluar' ? 'Pemilik' : 'Peminjam → Pemilik'); ?></th>
    <th>Tanggal</th>
    <th>Status</th>
    <th>Aksi</th>
</tr></thead>
 <tbody>
 <?php if (!$rows): ?>
 <tr><td colspan="6">Belum ada data.</td></tr>
 <?php else: ?>
 <?php foreach ($rows as $p): ?>
 <?php  $id = (int)($p['id_peminjaman'] ?? $p['id'] ?? 0); ?>
 <tr>
 <td><?php echo $id; ?></td>
 <td><?php echo e($p['item_teks']); ?><br><small><?php echo e($p['keperluan']); ?></small></td>
 <td><?php
 if ($mode === 'masuk') echo e($p['peminjam_nama'] ?? '');
 elseif ($mode === 'keluar') echo e($p['pemilik_nama'] ?? '');
 else echo e($p['peminjam_nama']) . ' → ' . e($p['pemilik_nama']); ?></td>
 <td><?php echo tgl_id($p['tgl_pinjam']); ?> – <?php echo tgl_id($p['tgl_kembali_rencana'] ?? $p['tgl_rencana'] ?? ''); ?></td>
 <td>
    <?php echo badge(status_tampil($p)); ?>
   <?php $alasan_teks = $p['alasan_penolakan'] ?? $p['alasan'] ?? ''; ?>
   <?php if (!empty($alasan_teks)): ?><br><small><?php echo e($alasan_teks); ?></small><?php endif; ?>
 </td>
 <td>

 <a href="../../peminjaman/detail.php?id=<?php echo $id; ?>">Detail</a>
 </td>
 </tr>
 <?php endforeach; endif; ?>
 </tbody>
 </table>
 </div>
<?php } ?>
 <section>
 <h2>Peminjaman</h2>
 <?php if ($flash): ?>
 <p class="flash flash-<?php echo e($flash['type']); ?>"><?php echo e($flash['pesan']); ?></p>
 <?php endif; ?>
 <h3>Semua Peminjaman</h3>
 <?php tabel_pinjam($semua, 'semua', $KOND); ?>
 </section>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
