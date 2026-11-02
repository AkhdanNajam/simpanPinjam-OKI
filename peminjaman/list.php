<?php
$page_title = "Peminjaman";
$wajib_login = [];
include __DIR__ . '/../includes/header.php';
$flash = get_flash();

$admin = isset($user['role']) && $user['role'] === 'admin';

// Ambil id_user secara aman dari berbagai kemungkinan key sesi
$uid = $user['id_user'] ?? ($user['id'] ?? ($_SESSION['id_user'] ?? ($_SESSION['id'] ?? 0)));

// Cari id_organisasi yang berelasi dengan id_user tersebut di database PostgreSQL
$org_id = 0;
if (!$admin && !empty($uid)) {
    $res = q_all("SELECT id_organisasi FROM organisasi WHERE id_user = :uid", ['uid' => $uid]);
    if (!empty($res)) {
        $org_id = (int)$res[0]['id_organisasi'];
    }
}

$semua = $admin ? daftar_peminjaman('semua') : [];
$masuk = $admin ? [] : daftar_peminjaman('masuk', $org_id);
$keluar = $admin ? [] : daftar_peminjaman('keluar', $org_id);
$KOND = $KONDISI;

function aksi_form(string $aksi, int $id, string $label, string $extra = '', string $kelas = ''): void {
    echo '<form method="post" action="proses_aksi.php" class="inline">' . csrf_field()
        . '<input type="hidden" name="aksi" value="' . e($aksi) . '"><input type="hidden" name="id" value="' . $id . '">'
        . $extra . '<button type="submit" class="' . e($kelas) . '">' . e($label) . '</button></form> ';
}

function pilih_kondisi(array $opsi): string {
    $h = '<select name="kondisi">';
    foreach ($opsi as $k) $h .= '<option value="' . e($k) . '">' . e($k) . '</option>';
    return $h . '</select>';
}

function tabel_pinjam(array $rows, string $mode, array $KOND): void { ?>
 <div class="table-responsive">
 <table>
 <thead><tr><th>#</th>
    <th>Barang</th>
    <th><?php echo $mode === 'masuk' ? 'Peminjam' : ($mode === 'keluar' ? 'Pemilik' : 'Peminjam → Pemilik'); ?></th>
    <th>Tanggal</th>
    <th>Status</th>
    <th>Aksi</th>
</tr></thead>
 <tbody>
 <?php if (!$rows): ?>
 <tr><td colspan="6">Belum ada data.</td></tr>
 <?php else: ?>
 <?php foreach ($rows as $p): ?>
 <?php  $id = (int)($p['id_peminjaman'] ?? $p['id'] ?? 0); ?>
 <tr>
 <td><?php echo $id; ?></td>
 <td><?php echo e($p['item_teks']); ?><br><small><?php echo e($p['keperluan']); ?></small></td>
 <td><?php
 if ($mode === 'masuk') echo e($p['peminjam_nama'] ?? '');
 elseif ($mode === 'keluar') echo e($p['pemilik_nama'] ?? '');
 else echo e($p['peminjam_nama']) . ' → ' . e($p['pemilik_nama']); ?></td>
 <td><?php echo tgl_id($p['tgl_pinjam']); ?> – <?php echo tgl_id($p['tgl_kembali_rencana'] ?? $p['tgl_rencana'] ?? ''); ?></td>
 <td>
    <?php echo badge(status_tampil($p)); ?>
   <?php $alasan_teks = $p['alasan_penolakan'] ?? $p['alasan'] ?? ''; ?>
   <?php if (!empty($alasan_teks)): ?><br><small><?php echo e($alasan_teks); ?></small><?php endif; ?>
 </td>
 <td>

 <a href="detail.php?id=<?php echo $id; ?>">Detail</a>
 <?php if ($mode === 'masuk'): ?>
 <?php if ($p['status'] === 'menunggu'):
 aksi_form('setuju', $id, 'Setujui');
 aksi_form('tolak', $id, 'Tolak', '<input type="text" name="alasan" placeholder="Alasan" required>', 'btn-hapus');
 elseif ($p['status'] === 'disetujui'):
 aksi_form('serah', $id, 'Serah terima', pilih_kondisi($KOND));
 elseif ($p['status'] === 'dipinjam'):
 aksi_form('kembali', $id, 'Terima kembali', pilih_kondisi($KOND) . '<input type="text" name="catatan" placeholder="Catatan kerusakan">');
 endif; ?>
 <?php elseif ($mode === 'keluar' && $p['status'] === 'menunggu'):
 aksi_form('batal', $id, 'Batalkan', '', 'btn-hapus');
 endif; ?>
 </td>
 </tr>
 <?php endforeach; endif; ?>
 </tbody>
 </table>
 </div>
<?php } ?>
 <section>
 <h2>Peminjaman</h2>
 <?php if ($flash): ?>
 <p class="flash flash-<?php echo e($flash['type']); ?>"><?php echo e($flash['pesan']); ?></p>
 <?php endif; ?>
 <?php if ($admin): ?>
 <h3>Semua Peminjaman</h3>
 <?php tabel_pinjam($semua, 'semua', $KOND); ?>
 <?php else: ?>
 <h3>Pengajuan Masuk (barang milik OKI Anda)</h3>
 <?php tabel_pinjam($masuk, 'masuk', $KOND); ?>
 <h3>Pengajuan Saya</h3>
 <?php tabel_pinjam($keluar, 'keluar', $KOND); ?>
 <?php endif; ?>
 </section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
