<?php
$page_title = "Verifikasi OKI";
$wajib_login = ['admin'];
include __DIR__ . '/../../includes/header.php';
$flash = get_flash();
$admin = true;
$daftar = daftar_org(false);   // semua OKI, termasuk yang belum terverifikasi
function aksi_oki(string $aksi, int $id, string $label, string $icon, string $kelas = '', string $konfirmasi = '', string $extra = ''): void {
    echo '<form method="post" action="proses_aksi.php" class="inline"' . ($konfirmasi !== '' ? ' data-konfirmasi="' . e($konfirmasi) . '"' : '') . '>' . csrf_field()
        . '<input type="hidden" name="aksi" value="' . e($aksi) . '"><input type="hidden" name="id" value="' . $id . '">'
        . $extra
        . '<button type="submit" class="btn-icon ' . e($kelas) . '" title="' . e($label) . '" aria-label="' . e($label) . '"><i class="bi ' . e($icon) . '"></i></button></form> ';
}
?>
<section>
 <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
 <h2 style="margin: 0;">Verifikasi OKI</h2>
 <?php if ($admin): ?>
 <a href="tambah_oki.php" class="btn">+ Tambah OKI</a>
 <?php endif; ?>
 </div>
 <?php if ($flash): ?>
 <p class="flash flash-<?php echo e($flash['type']); ?>"><?php echo e($flash['pesan']); ?></p>
 <?php endif; ?>
 <div class="search-box">
 <label for="search-input">Cari Nama OKI</label>
 <input type="text" id="search-input" placeholder="Ketik nama organisasi...">
 </div>
 <div class="table-responsive">
 <table>
 <thead>
 <tr><th>Nama</th><th>Jenis</th><th>Kontak</th><?php if ($admin): ?><th>Email</th><th>Verifikasi</th><th>Akun</th><th>Aksi</th><?php endif; ?></tr>
 </thead>
 <tbody>
 <?php foreach ($daftar as $o): $a = $o['akun_id'] ? $o : null; ?>
 <tr>
 <td><?php echo e($o['nama']); ?></td>
 <td><?php echo e($o['jenis']); ?></td>
 <td><?php echo e($o['kontak'] ?: '-'); ?></td>
 <?php if ($admin): ?>
 <td><?php echo e($a['email'] ?? '-'); ?></td>
 <td><?php echo badge($o['verif']); ?></td>
 <td><?php echo $a ? badge($a['aktif'] ? 'aktif' : 'nonaktif') : '-'; ?></td>
 <td class="kolom-aksi">
<div class="aksi-grup">
<?php if ($o['verif'] !== 'terverifikasi') aksi_oki('ok', (int)$o['id'], 'Verifikasi', 'bi-check-circle', 'btn-verif');
if ($o['verif'] !== 'ditolak') aksi_oki('no', (int)$o['id'], 'Tolak', 'bi-x-circle', 'btn-tolak');
if ($a) aksi_oki('akun', (int)$o['id'], $a['aktif'] ? 'Nonaktifkan akun' : 'Aktifkan akun', $a['aktif'] ? 'bi-person-slash' : 'bi-person-check', 'btn-nonaktif');
aksi_oki('hapus', (int)$o['id'], 'Hapus', 'bi-trash', 'btn-hapus', 'Hapus OKI ini beserta akun dan barangnya? Hanya bisa jika belum punya riwayat peminjaman.'); ?>
</div>
</td>
 <?php endif; ?>
 </tr>
 <?php endforeach; ?>
 </tbody>
 </table>
 </div>
 </section>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
