<?php
$page_title = "Tambah Barang";
$wajib_login = ['oki'];
include __DIR__ . '/../includes/header.php';
$flash = get_flash();
$old = $_SESSION['old'] ?? []; unset($_SESSION['old']);
?>
 <section>
 <h2>Tambah Barang</h2>
 <?php if ($flash): ?>
 <p class="flash flash-<?php echo e($flash['type']); ?>"><?php echo e($flash['pesan']); ?></p>
 <?php endif; ?>
 <form id="form-tambah" method="post" action="proses_tambah.php"><?php echo csrf_field(); ?>
 <label for="nama">Nama Barang</label>
 <input type="text" id="nama" name="nama" value="<?php echo e($old['nama'] ?? ''); ?>" required>

 <label for="deskripsi">Deskripsi (opsional)</label>
 <textarea id="deskripsi" name="deskripsi" rows="3" maxlength="500"><?php echo e($old['deskripsi'] ?? ''); ?></textarea>

 <label for="kategori">Kategori</label>
 <select id="kategori" name="kategori" required>
 <option value="">-- Pilih kategori --</option>
 <?php foreach (daftar_kategori() as $k): ?>
 <option value="<?php echo (int)$k['id']; ?>" <?php echo (string)($old['kategori'] ?? '') === (string)$k['id'] ? 'selected' : ''; ?>><?php echo e($k['nama']); ?></option>
 <?php endforeach; ?>
 </select>

 <label for="jumlah">Jumlah Total</label>
 <input type="number" id="jumlah" name="jumlah" min="1" value="<?php echo e($old['jumlah'] ?? ''); ?>" required>

 <label for="maks">Maksimal Durasi Pinjam (hari)</label>
 <input type="number" id="maks" name="maks" min="1" value="<?php echo e($old['maks'] ?? '7'); ?>" required>

 <label for="kondisi">Kondisi</label>
 <select id="kondisi" name="kondisi" required>
 <?php foreach ($KONDISI as $k): ?>
 <option value="<?php echo e($k); ?>" <?php echo ($old['kondisi'] ?? 'baik') === $k ? 'selected' : ''; ?>><?php echo e(ucfirst($k)); ?></option>
 <?php endforeach; ?>
 </select>


 <button type="submit">Simpan</button>
 </form>
 </section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
