<?php
$page_title = "Barang Saya";
$wajib_login = ['oki'];
include __DIR__ . '/../../includes/header.php';

$flash = get_flash();
$milik = barang_milik($user['org']);
?>

<section>
    <h2>Barang Saya</h2>

    <?php if ($flash): ?>
        <p class="flash flash-<?php echo e($flash['type']); ?>">
            <?php echo e($flash['pesan']); ?>
        </p>
    <?php endif; ?>

    <p>
        <a class="btn-icon btn-tambah-barang" href="tambah.php" title="Tambah Barang" aria-label="Tambah Barang"><i class="bi bi-plus-lg"></i> Tambah Barang</a>
    </p>

    <div class="table-responsive">
        <table class="tabel-barang">
            <colgroup>
                <col style="width:8%"><col style="width:17%"><col style="width:12%">
                <col style="width:7%"><col style="width:8%"><col style="width:9%">
                <col style="width:9%"><col style="width:12%"><col style="width:18%">
            </colgroup>
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th class="tengah">Jumlah</th>
                    <th class="tengah">Maks. hari</th>
                    <th>Kondisi</th>
                    <th>Status</th>
                    <th>Pemantauan</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($milik)): ?>
                    <tr>
                        <td colspan="9">
                            Belum ada barang. Silakan tambah lewat menu Tambah Barang.
                        </td>
                    </tr>

                <?php else: ?>
                    <?php foreach ($milik as $b): ?>
                        <?php $aktif = $b['status'] === 'aktif'; $lbl = $aktif ? 'Nonaktifkan' : 'Aktifkan'; ?>
                        <tr>
                            <td>
                                <?php if (!empty($b['foto_db'])): ?>

                                    <img
                                        src="foto.php?id=<?php echo (int)$b['id']; ?>"
                                        alt="<?php echo e($b['nama']); ?>"
                                        style="width:70px;height:60px;object-fit:cover;border-radius:6px;"
                                    >

                                <?php elseif (!empty($b['foto'])): ?>

                                    <img
                                        src="<?php echo $base; ?><?php echo e($b['foto']); ?>"
                                        alt="<?php echo e($b['nama']); ?>"
                                        style="width:70px;height:60px;object-fit:cover;border-radius:6px;"
                                    >

                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>

                            <td><?php echo e($b['nama']); ?></td>
                            <td><?php echo e($b['kategori']); ?></td>
                            <td class="tengah"><?php echo (int)$b['total']; ?></td>
                            <td class="tengah"><?php echo (int)$b['maks']; ?></td>
                            <td><?php echo e(ucfirst(str_replace('_', ' ', $b['kondisi']))); ?></td>
                            <td><?php echo badge($b['status']); ?></td>

                            <td>
                                <?php
                                echo $aktif
                                    ? badge(status_pantau($b)) .
                                        ($b['keluar'] > 0
                                            ? ' <small>(' . (int)$b['keluar'] . ' unit keluar)</small>'
                                            : '')
                                    : '-';
                                ?>
                            </td>

                            <td class="kolom-aksi">
                                <div class="aksi-grup">
                                    <a href="edit.php?id=<?php echo (int)$b['id']; ?>" class="btn-icon btn-ubah" title="Ubah" aria-label="Ubah"><i class="bi bi-pencil-square"></i></a>

                                    <form method="post" action="proses_aksi.php" class="inline" data-konfirmasi="<?php echo $lbl; ?> barang ini?">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="aksi" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
                                        <button type="submit" class="btn-icon <?php echo $aktif ? 'btn-nonaktif-barang' : 'btn-verif'; ?>" title="<?php echo $lbl; ?>" aria-label="<?php echo $lbl; ?>">
                                            <i class="bi <?php echo $aktif ? 'bi-slash-circle' : 'bi-check-circle'; ?>"></i>
                                        </button>
                                    </form>

                                    <?php if (!$b['pernah_dipinjam']): ?>
                                        <form method="post" action="proses_aksi.php" class="inline" data-konfirmasi="Hapus barang ini?">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="aksi" value="hapus">
                                            <input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
                                            <button type="submit" class="btn-icon btn-hapus" title="Hapus" aria-label="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>