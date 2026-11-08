
<?php
$page_title = "Ubah Barang";
$wajib_login = ['oki'];
include __DIR__ . '/../../includes/header.php';

$flash = get_flash();
$b = get_barang((int)($_GET['id'] ?? 0));

if (!$b || $b['org'] !== $user['org']) {
    set_flash('error', 'Barang tidak ditemukan.');
    redirect('oki/barang/saya.php');
}

$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);

$v = fn(string $k, $default) => $old[$k] ?? $default;
?>

<section>
    <h2>Ubah Barang</h2>

    <?php if ($flash): ?>
        <p class="flash flash-<?php echo e($flash['type']); ?>">
            <?php echo e($flash['pesan']); ?>
        </p>
    <?php endif; ?>

    <form method="post" action="proses_edit.php"
          enctype="multipart/form-data">

        <?php echo csrf_field(); ?>

        <input type="hidden" name="id"
               value="<?php echo (int)$b['id']; ?>">

        <label for="nama">Nama Barang</label>
        <input type="text" id="nama" name="nama"
               maxlength="150"
               value="<?php echo e($v('nama', $b['nama'])); ?>"
               required>

        <label for="deskripsi">Deskripsi (opsional)</label>
        <textarea id="deskripsi" name="deskripsi"
                  rows="3" maxlength="500"><?php
            echo e($v('deskripsi', $b['deskripsi']));
        ?></textarea>

        <label for="kategori">Kategori</label>
        <select id="kategori" name="kategori" required>
            <?php foreach (daftar_kategori() as $k): ?>
                <option value="<?php echo (int)$k['id']; ?>"
                    <?php echo (string)$v('kategori', $b['kat_id']) ===
                    (string)$k['id'] ? 'selected' : ''; ?>>
                    <?php echo e($k['nama']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="jumlah">Jumlah Total</label>
        <input type="number" id="jumlah" name="jumlah"
               min="1"
               value="<?php echo e($v('jumlah', $b['total'])); ?>"
               required>

        <label for="maks">Maksimal Durasi Pinjam (hari)</label>
        <input type="number" id="maks" name="maks"
               min="1"
               value="<?php echo e($v('maks', $b['maks'])); ?>"
               required>

        <label for="kondisi">Kondisi</label>
        <select id="kondisi" name="kondisi" required>
            <?php foreach ($KONDISI as $k): ?>
                <option value="<?php echo e($k); ?>"
                    <?php echo $v('kondisi', $b['kondisi']) === $k
                    ? 'selected' : ''; ?>>
                    <?php echo e(ucfirst($k)); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="lokasi">Lokasi Penyimpanan</label>
        <input type="text" id="lokasi" name="lokasi"
               maxlength="150"
               value="<?php echo e($v('lokasi', $b['lokasi'])); ?>">

        <label for="foto">Foto Barang (opsional, maks. 2 MB)</label>

        <?php if (!empty($b['foto'])): ?>
            <p>Foto saat ini:</p>
            <img
                src="<?php echo $base . e($b['foto']); ?>"
                alt="<?php echo e($b['nama']); ?>"
                style="width:200px;height:150px;object-fit:cover;border-radius:8px;"
            >
        <?php endif; ?>

        <input type="file" id="foto" name="foto"
               accept="image/jpeg,image/png,image/webp">

        <div id="preview-container"
             style="display:none;margin-top:10px;">
            <p>Preview Foto Baru:</p>
            <img id="preview-foto" src=""
                 alt="Preview Barang"
                 style="width:200px;height:150px;object-fit:cover;border-radius:8px;">
        </div>

        <button type="submit">Simpan Perubahan</button>
        <a href="saya.php">Batal</a>
    </form>
</section>

<script>
document.getElementById('foto').addEventListener('change', function () {
    const file = this.files[0];
    const preview = document.getElementById('preview-foto');
    const container = document.getElementById('preview-container');

    if (!file) {
        container.style.display = 'none';
        return;
    }

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        alert('Format foto harus JPG, PNG, atau WEBP!');
        this.value = '';
        container.style.display = 'none';
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        alert('Ukuran foto maksimal 2 MB!');
        this.value = '';
        container.style.display = 'none';
        return;
    }

    preview.src = URL.createObjectURL(file);
    container.style.display = 'block';
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
