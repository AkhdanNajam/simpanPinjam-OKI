<?php
// Cek apakah user sudah login atau belum
if (!isset($_SESSION['user_id']) && !isset($_SESSION['login'])) {
    // Redirect ke halaman login jika belum login saat mau transaksi
    header("Location: ../login.php?pesan=harus_login");
    exit;
}
$page_title = "Ajukan Peminjaman";
$wajib_login = ['oki'];
include __DIR__ . '/../includes/header.php';
$flash = get_flash();
$old = $_SESSION['old'] ?? []; unset($_SESSION['old']);

$pre = ['tgl_pinjam' => tgl_valid(get_str('dari')) ? get_str('dari') : '', 'tgl_kembali' => tgl_valid(get_str('sampai')) ? get_str('sampai') : ''];
$b = get_barang((int)($_GET['id'] ?? 0));
if (!$b || $b['status'] !== 'aktif' || $b['org'] === $user['org'] || $b['org_verif'] !== 'terverifikasi') {
    set_flash('error', 'Barang tidak tersedia untuk dipinjam.');
    redirect('barang/list.php');
}
?>
 <section>
 <h2>Ajukan Peminjaman</h2>
 <?php if ($flash): ?>
 <p class="flash flash-<?php echo e($flash['type']); ?>"><?php echo e($flash['pesan']); ?></p>
 <?php endif; ?>
 <p><strong><?php echo e($b['nama']); ?></strong> milik <?php echo e($b['org_nama']); ?>.
 Stok total <?php echo (int)$b['total']; ?>, maksimal <?php echo (int)$b['maks']; ?> hari, kondisi <?php echo e($b['kondisi']); ?>.
 <a href="../barang/kalender.php?id=<?php echo (int)$b['id']; ?>">Lihat kalender ketersediaan</a></p>
 <?php if ($b['deskripsi'] !== ''): ?><p class="catatan"><?php echo e($b['deskripsi']); ?></p><?php endif; ?>
 <form method="post" action="proses_ajukan.php"><?php echo csrf_field(); ?>
 <input type="hidden" name="barang" value="<?php echo (int)$b['id']; ?>">
 <label for="jumlah">Jumlah</label>
 <input type="number" id="jumlah" name="jumlah" min="1" value="<?php echo e($old['jumlah'] ?? '1'); ?>" required>
 <label for="tgl_pinjam">Tanggal Pinjam</label>
 <input type="date" id="tgl_pinjam" name="tgl_pinjam" min="<?php echo date('Y-m-d'); ?>" value="<?php echo e($old['tgl_pinjam'] ?? $pre['tgl_pinjam']); ?>" required>
 <label for="tgl_kembali">Tanggal Kembali</label>
 <input type="date" id="tgl_kembali" name="tgl_kembali" value="<?php echo e($old['tgl_kembali'] ?? $pre['tgl_kembali']); ?>" required>
 <label for="keperluan">Keperluan</label>
 <input type="text" id="keperluan" name="keperluan" value="<?php echo e($old['keperluan'] ?? ''); ?>" required>
 <label for="catatan">Catatan (opsional)</label>
 <input type="text" id="catatan" name="catatan" value="<?php echo e($old['catatan'] ?? ''); ?>">
 <button type="submit">Ajukan</button>
 </form>
 </section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
