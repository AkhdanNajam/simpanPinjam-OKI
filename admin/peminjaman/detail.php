<?php
$page_title = "Detail Peminjaman";
$wajib_login = ['admin'];
include __DIR__ . '/../../includes/header.php';

// Ambil data peminjaman berdasarkan ID.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    set_flash('error', 'ID peminjaman tidak valid.');
    redirect('admin/peminjaman/list.php');
}

$p = get_peminjaman($id);
if (!$p) {
    set_flash('error', 'Data peminjaman tidak ditemukan.');
    redirect('admin/peminjaman/list.php');
}

$barang = item_peminjaman($id);
$riwayat = riwayat_peminjaman($id);
$status = (string)($p['status'] ?? '');
$statusTampil = status_tampil($p);

$labelStatus = [
    'menunggu' => 'Pengajuan',
    'disetujui' => 'Persetujuan',
    'dipinjam' => 'Serah terima',
    'selesai' => 'Pengembalian',
];

$urutanStatus = ['menunggu', 'disetujui', 'dipinjam', 'selesai'];
$posisiStatus = array_search($status, $urutanStatus, true);
?>

<section class="detail-peminjaman-admin">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
        <h2>Detail Peminjaman #<?php echo (int)$p['id']; ?></h2>
        <a href="list.php" class="btn">&larr; Kembali ke Daftar</a>
    </div>

    <div class="card" style="margin:20px 0; padding:20px;">
        <h3>Informasi Peminjaman</h3>
        <p>
            <strong>Status:</strong> <?php echo badge($statusTampil); ?><br>
            <strong>Tanggal Pengajuan:</strong>
            <?php echo tgl_id($p['tgl_pengajuan'] ?? null); ?><br>
            <strong>Tanggal Pinjam:</strong>
            <?php echo tgl_id($p['tgl_pinjam'] ?? null); ?><br>
            <strong>Rencana Pengembalian:</strong>
            <?php echo tgl_id($p['tgl_rencana'] ?? null); ?><br>
            <strong>Tanggal Pengembalian Aktual:</strong>
            <?php echo tgl_id($p['tgl_aktual'] ?? null); ?>
        </p>

        <?php if (!empty($p['alasan'])): ?>
            <p><strong>Alasan Penolakan/Keterangan:</strong><br>
                <?php echo nl2br(e($p['alasan'])); ?>
            </p>
        <?php endif; ?>
    </div>

    <?php if (in_array($status, ['ditolak', 'dibatalkan'], true)): ?>
        <div class="catatan" style="margin:20px 0;">
            Pengajuan ini berstatus <strong><?php echo e($status); ?></strong>.
            <?php if (!empty($p['alasan'])): ?>
                Keterangan: <?php echo e($p['alasan']); ?>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card" style="margin:20px 0; padding:20px;">
            <h3>Progres Peminjaman</h3>
            <ol class="timeline">
                <?php foreach ($urutanStatus as $index => $kode): ?>
                    <?php
                    $selesai = $posisiStatus !== false && $index <= $posisiStatus;
                    $berikut = $posisiStatus !== false && $index === $posisiStatus;
                    ?>
                    <li class="<?php echo $selesai ? 'selesai' : ''; ?> <?php echo $berikut ? 'berikut' : ''; ?>">
                        <span><?php echo $index + 1; ?></span>
                        <?php echo e($labelStatus[$kode]); ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    <?php endif; ?>

    <div class="card" style="margin:20px 0; padding:20px;">
        <h3>Informasi Organisasi</h3>
        <p>
            <strong>Organisasi Peminjam:</strong>
            <?php echo e($p['peminjam_nama'] ?? '-'); ?><br>
            <strong>Organisasi Pemilik:</strong>
            <?php echo e($p['pemilik_nama'] ?? '-'); ?>
        </p>

        <h3>Keperluan Peminjaman</h3>
        <p><?php echo nl2br(e($p['keperluan'] ?? '-')); ?></p>

        <h3>Catatan Tambahan</h3>
        <p><?php echo !empty($p['catatan']) ? nl2br(e($p['catatan'])) : '-'; ?></p>
    </div>

    <div class="card" style="margin:20px 0; padding:20px;">
        <h3>Daftar Barang yang Dipinjam</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Barang</th>
                        <th>Jumlah</th>
                        <th>Kondisi Keluar</th>
                        <th>Kondisi Kembali</th>
                        <th>Catatan Kerusakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$barang): ?>
                        <tr><td colspan="6">Tidak ada detail barang.</td></tr>
                    <?php else: ?>
                        <?php foreach ($barang as $index => $item): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo e($item['nama'] ?? '-'); ?></td>
                                <td><?php echo (int)($item['jumlah'] ?? 0); ?></td>
                                <td><?php echo e($item['kondisi_keluar'] ?? '-'); ?></td>
                                <td><?php echo e($item['kondisi_kembali'] ?? '-'); ?></td>
                                <td><?php echo e($item['cr'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin:20px 0; padding:20px;">
        <h3>Riwayat Status</h3>
        <?php if (!$riwayat): ?>
            <p>Belum ada riwayat perubahan status.</p>
        <?php else: ?>
            <ul class="notif">
                <?php foreach ($riwayat as $log): ?>
                    <li>
                        <small><?php echo e($log['waktu'] ?? '-'); ?></small>
                        &mdash; <?php echo e($log['user_nama'] ?? 'Sistem'); ?>:
                        <?php echo e($log['lama'] ?: 'baru'); ?>
                        &rarr; <strong><?php echo e($log['baru'] ?? '-'); ?></strong>
                        <?php if (!empty($log['ket'])): ?>
                            (<?php echo e($log['ket']); ?>)
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <p><a href="list.php" class="btn">&larr; Kembali ke Daftar Peminjaman</a></p>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
