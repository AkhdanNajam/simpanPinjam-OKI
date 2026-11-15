
<?php
$page_title = "Detail Barang";
include __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$b = get_barang($id);

$boleh = $b && (
    ($user && $user['role'] === 'admin') ||
    ($user && $user['role'] === 'oki' && $b['org'] === $user['org']) ||
    ($b['status'] === 'aktif' && $b['org_verif'] === 'terverifikasi')
);

if (!$boleh) {
    set_flash('error', 'Barang tidak ditemukan.');
    redirect('katalog/list.php');
}

$bisaPinjam = $user &&
    $user['role'] === 'oki' &&
    $b['org'] !== $user['org'] &&
    $b['status'] === 'aktif' &&
    $b['org_verif'] === 'terverifikasi';
?>

<section class="detail-page">

    <div class="detail-heading">
        <a href="list.php" class="detail-back">&larr;</a>
        <h2>Detail Barang</h2>
    </div>

    <div class="detail-card">

        <div class="detail-photo">
            <?php if (!empty($b['foto']) &&
                strpos($b['foto'], 'db://') !== 0): ?>

                <img
                    src="<?php echo $base . e($b['foto']); ?>"
                    alt="<?php echo e($b['nama']); ?>"
                >

            <?php else: ?>
                <div class="detail-photo-empty">
                    <span>&#128247;</span>
                    <small>Foto barang</small>
                </div>
            <?php endif; ?>
        </div>

        <div class="detail-info">

            <div class="detail-field">
                <label>Nama Barang</label>
                <div><?php echo e($b['nama']); ?></div>
            </div>

            <div class="detail-field">
                <label>Kategori</label>
                <div><?php echo e($b['kategori']); ?></div>
            </div>

            <div class="detail-field">
                <label>Jumlah Unit</label>
                <div><?php echo (int)$b['total']; ?> Unit</div>
            </div>

            <div class="detail-field">
                <label>Kondisi</label>
                <div><?php echo e(ucfirst($b['kondisi'])); ?></div>
            </div>

            <div class="detail-field">
                <label>Deskripsi</label>
                <div>
                    <?php echo $b['deskripsi'] !== ''
                        ? nl2br(e($b['deskripsi']))
                        : 'Tidak ada deskripsi.'; ?>
                </div>
            </div>

            <div class="detail-field">
                <label>Lokasi Penyimpanan</label>
                <div>
                    <?php echo e($b['lokasi'] ?: '-'); ?>
                </div>
            </div>

            <div class="detail-field">
                <label>Pemilik</label>
                <div><?php echo e($b['org_nama']); ?></div>
            </div>

            <div class="detail-field">
                <label>Maksimal Peminjaman</label>
                <div><?php echo (int)$b['maks']; ?> Hari</div>
            </div>

            <div class="detail-action">
                <?php if ($bisaPinjam): ?>

                    <a class="btn"
                       href="../oki/peminjaman/ajukan.php?id=<?php echo (int)$b['id']; ?>">
                        Ajukan Peminjaman
                    </a>

                <?php elseif (!$user): ?>

                    <a class="btn" href="../login.php">
                        Login untuk Meminjam
                    </a>

                <?php endif; ?>
            </div>

        </div>
    </div>

</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
