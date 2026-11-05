<?php
$page_title = "Tambah Barang";
$wajib_login = ['oki'];
include __DIR__ . '/../../includes/header.php';

$flash = get_flash();
$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);
?>

<section>
    <h2>Tambah Barang</h2>

    <?php if ($flash): ?>
        <p class="flash flash-<?php echo e($flash['type']); ?>">
            <?php echo e($flash['pesan']); ?>
        </p>
    <?php endif; ?>

    <form id="form-tambah" method="post" action="proses_tambah.php" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>

        <label for="nama">Nama Barang</label>
        <input type="text" id="nama" name="nama" maxlength="150"
               value="<?php echo e($old['nama'] ?? ''); ?>" required>

        <label for="deskripsi">Deskripsi (opsional)</label>
        <textarea id="deskripsi" name="deskripsi" rows="3" maxlength="500"><?php echo e($old['deskripsi'] ?? ''); ?></textarea>

        <label for="kategori">Kategori</label>
        <select id="kategori" name="kategori" required>
            <option value="">-- Pilih kategori --</option>
            <?php foreach (daftar_kategori() as $k): ?>
                <option value="<?php echo (int)$k['id']; ?>"
                    <?php echo (string)($old['kategori'] ?? '') === (string)$k['id'] ? 'selected' : ''; ?>>
                    <?php echo e($k['nama']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="jumlah">Jumlah Total</label>
        <input type="number" id="jumlah" name="jumlah" min="1"
               value="<?php echo e($old['jumlah'] ?? ''); ?>" required>

        <label for="maks">Maksimal Durasi Pinjam (hari)</label>
        <input type="number" id="maks" name="maks" min="1"
               value="<?php echo e($old['maks'] ?? '7'); ?>" required>

        <label for="kondisi">Kondisi</label>
        <select id="kondisi" name="kondisi" required>
            <?php foreach ($KONDISI as $k): ?>
                <option value="<?php echo e($k); ?>"
                    <?php echo ($old['kondisi'] ?? 'baik') === $k ? 'selected' : ''; ?>>
                    <?php echo e(ucfirst($k)); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="lokasi">Lokasi Penyimpanan</label>
        <input type="text" id="lokasi" name="lokasi" maxlength="150"
               value="<?php echo e($old['lokasi'] ?? ''); ?>">

        <label for="foto">Foto Barang (opsional, maks. 2 MB)</label>
        <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp">

        <div id="preview-container" style="display:none; margin-top:10px;">
            <p>Preview Foto:</p>
            <img id="preview-foto" src="" alt="Preview Barang"
                 style="width:200px; height:150px; object-fit:cover; border-radius:8px;">
        </div>

        <button type="submit">Simpan</button>
    </form>
</section>

<script>
document.getElementById('foto').addEventListener('change', function () {
    const file = this.files[0];
    const preview = document.getElementById('preview-foto');
    const container = document.getElementById('preview-container');

    if (!file) { container.style.display = 'none'; return; }

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        alert('Format foto harus JPG, PNG, atau WEBP!');
        this.value = ''; container.style.display = 'none'; return;
    }
    if (file.size > 2 * 1024 * 1024) {
        alert('Ukuran foto maksimal 2 MB!');
        this.value = ''; container.style.display = 'none'; return;
    }
    preview.src = URL.createObjectURL(file);
    container.style.display = 'block';
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>