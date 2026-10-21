<?php
$page_title = "Kategori Barang";
$wajib_login = ['admin'];
include __DIR__ . '/../includes/header.php';
$flash = get_flash();
$old = $_SESSION['old'] ?? []; unset($_SESSION['old']);
$daftar = kategori_dengan_jumlah();
?>
 <section>
 <h2>Kategori Barang</h2>
 <?php if ($flash): ?>
 <p class="flash flash-<?php echo e($flash['type']); ?>"><?php echo e($flash['pesan']); ?></p>
 <?php endif; ?>

 <form method="post" action="proses_aksi.php" class="inline"><?php echo csrf_field(); ?>
 <input type="hidden" name="aksi" value="tambah">
 <input type="text" name="nama" maxlength="100" placeholder="Nama kategori baru" value="<?php echo e($old['nama'] ?? ''); ?>" required>
 <button type="submit">+ Tambah Kategori</button>
 </form>

 <div class="table-responsive">
 <table>
 <thead><tr><th>#</th><th>Nama Kategori</th><th>Jumlah Barang</th><th>Aksi</th></tr></thead>
 <tbody>
 <?php foreach ($daftar as $k): ?>
 <tr>
 <td><?php echo (int)$k['id']; ?></td>
 <td>
 <form method="post" action="proses_aksi.php" class="inline"><?php echo csrf_field(); ?>
 <input type="hidden" name="aksi" value="ubah"><input type="hidden" name="id" value="<?php echo (int)$k['id']; ?>">
 <input type="text" name="nama" maxlength="100" value="<?php echo e($k['nama']); ?>" required>
 <button type="submit">Simpan</button>
 </form>
 </td>
 <td><?php echo (int)$k['jumlah']; ?></td>
 <td>
 <?php if ((int)$k['jumlah'] === 0): ?>
 <form method="post" action="proses_aksi.php" class="inline" data-konfirmasi="Hapus kategori ini?"><?php echo csrf_field(); ?>
 <input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?php echo (int)$k['id']; ?>">
 <button type="submit" class="btn-hapus">Hapus</button>
 </form>
 <?php else: ?><small class="catatan">dipakai barang</small><?php endif; ?>
 </td>
 </tr>
 <?php endforeach; ?>
 </tbody>
 </table>
 </div>
 </section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
