<?php
$page_title = "Barang Saya";
$wajib_login = ['oki'];
include __DIR__ . '/../includes/header.php';
$flash = get_flash();
$milik = barang_milik($user['org']);
?>
 <section>
 <h2>Barang Saya</h2>
 <?php if ($flash): ?>
 <p class="flash flash-<?php echo e($flash['type']); ?>"><?php echo e($flash['pesan']); ?></p>
 <?php endif; ?>
 <p><a class="btn" href="tambah.php">+ Tambah Barang</a></p>
 <div class="table-responsive">
 <table>
 <thead>
 <tr><th>Nama Barang</th><th>Kategori</th><th>Jumlah</th><th>Maks. hari</th><th>Kondisi</th><th>Status</th><th>Pemantauan</th><th>Aksi</th></tr>
 </thead>
 <tbody>
 <?php if (empty($milik)): ?>
 <tr><td colspan="8">Belum ada barang. Silakan tambah lewat menu "Tambah Barang".</td></tr>
 <?php else: foreach ($milik as $b): ?>
 <tr>
 <td><?php echo e($b['nama']); ?></td>
 <td><?php echo e($b['kategori']); ?></td>
 <td><?php echo (int)$b['total']; ?></td>
 <td><?php echo (int)$b['maks']; ?></td>
 <td><?php echo e($b['kondisi']); ?></td>
 <td><?php echo badge($b['status']); ?></td>
 <td><?php echo $b['status'] === 'aktif' ? badge(status_pantau($b)) . ($b['keluar'] > 0 ? ' <small>(' . (int)$b['keluar'] . ' unit keluar)</small>' : '') : '-'; ?></td>
 <td>
 <a href="edit.php?id=<?php echo (int)$b['id']; ?>">Ubah</a>
 <a href="kalender.php?id=<?php echo (int)$b['id']; ?>">Kalender</a>
 <form method="post" action="proses_aksi.php" class="inline"><?php echo csrf_field(); ?>
 <input type="hidden" name="aksi" value="toggle"><input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
 <button type="submit"><?php echo $b['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'; ?></button>
 </form>
 <?php if (!$b['pernah_dipinjam']): ?>
 <form method="post" action="proses_aksi.php" class="inline" data-konfirmasi="Hapus barang ini?"><?php echo csrf_field(); ?>
 <input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
 <button type="submit" class="btn-hapus">Hapus</button>
 </form>
 <?php endif; ?>
 </td>
 </tr>
 <?php endforeach; endif; ?>
 </tbody>
 </table>
 </div>
 </section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
