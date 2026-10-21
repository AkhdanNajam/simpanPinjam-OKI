<?php
$page_title = "Daftar OKI";
$wajib_login = [];
include __DIR__ . '/../includes/header.php';
$flash = get_flash();
$admin = $user['role'] === 'admin';
$daftar = daftar_org($admin);
function aksi_oki(string $aksi, int $id, string $label, string $extra = '', string $kelas = '', string $konfirmasi = ''): void {
    echo '<form method="post" action="proses_aksi.php" class="inline"' . ($konfirmasi !== '' ? ' data-konfirmasi="' . e($konfirmasi) . '"' : '') . '>' . csrf_field()
        . '<input type="hidden" name="aksi" value="' . e($aksi) . '"><input type="hidden" name="id" value="' . $id . '">'
        . $extra . '<button type="submit" class="' . e($kelas) . '">' . e($label) . '</button></form> ';
}
?>
 <section>
 <h2>Daftar OKI</h2>
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
 <td>
 <?php if ($o['verif'] !== 'terverifikasi') aksi_oki('ok', (int)$o['id'], 'Verifikasi');
 if ($o['verif'] !== 'ditolak') aksi_oki('no', (int)$o['id'], 'Tolak', '', 'btn-hapus');
 if ($a) aksi_oki('akun', (int)$o['id'], $a['aktif'] ? 'Nonaktifkan akun' : 'Aktifkan akun');
 aksi_oki('hapus', (int)$o['id'], 'Hapus', '', 'btn-hapus', 'Hapus OKI ini beserta akun dan barangnya? Hanya bisa jika belum punya riwayat peminjaman.'); ?>
 </td>
 <?php endif; ?>
 </tr>
 <?php endforeach; ?>
 </tbody>
 </table>
 </div>
 </section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
