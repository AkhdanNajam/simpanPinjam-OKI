<?php
$page_title = "Katalog Barang";
$wajib_login = [];
include __DIR__ . '/../includes/header.php';
$flash = get_flash();
$admin = $user['role'] === 'admin';

// Filter pencarian (GET): nama/deskripsi, kategori, jenis OKI pemilik, rentang tanggal, hanya yang tersedia.
$hari = date('Y-m-d');
$dari = get_str('dari');
$sampai = get_str('sampai');
if (!tgl_valid($dari)) $dari = $hari;
if (!tgl_valid($sampai) || $sampai < $dari) $sampai = $dari;
$jenis = get_str('jenis');
if (!in_array($jenis, $JENIS_OKI, true)) $jenis = '';
$f = ['q' => get_str('q'), 'kategori' => (int)get_str('kategori'), 'jenis' => $jenis,
      'dari' => $dari, 'sampai' => $sampai, 'ada' => get_str('ada') === '1'];
$katalog = cari_barang($f, $admin);
$rentang = $dari === $sampai ? tgl_id($dari) : tgl_id($dari) . ' – ' . tgl_id($sampai);
?>
 <section>
 <h2>Katalog Barang</h2>
 <?php if ($flash): ?>
 <p class="flash flash-<?php echo e($flash['type']); ?>"><?php echo e($flash['pesan']); ?></p>
 <?php endif; ?>

 <form method="get" action="list.php" class="filter-bar">
 <div><label for="q">Nama / deskripsi</label>
 <input type="text" id="q" name="q" value="<?php echo e($f['q']); ?>" placeholder="mis. proyektor"></div>
 <div><label for="kategori">Kategori</label>
 <select id="kategori" name="kategori">
 <option value="0">Semua</option>
 <?php foreach (daftar_kategori() as $k): ?>
 <option value="<?php echo (int)$k['id']; ?>" <?php echo $f['kategori'] === (int)$k['id'] ? 'selected' : ''; ?>><?php echo e($k['nama']); ?></option>
 <?php endforeach; ?>
 </select></div>
 <div><label for="jenis">Jenis OKI pemilik</label>
 <select id="jenis" name="jenis">
 <option value="">Semua</option>
 <?php foreach ($JENIS_OKI as $j): ?>
 <option value="<?php echo e($j); ?>" <?php echo $jenis === $j ? 'selected' : ''; ?>><?php echo e($j); ?></option>
 <?php endforeach; ?>
 </select></div>
 <div><label for="dari">Cek ketersediaan dari</label>
 <input type="date" id="dari" name="dari" value="<?php echo e($dari); ?>"></div>
 <div><label for="sampai">sampai</label>
 <input type="date" id="sampai" name="sampai" value="<?php echo e($sampai); ?>"></div>
 <div class="filter-cek"><label><input type="checkbox" name="ada" value="1" <?php echo $f['ada'] ? 'checked' : ''; ?>> Hanya yang tersedia</label></div>
 <div class="filter-aksi"><button type="submit">Cari</button> <a href="list.php">Reset</a></div>
 </form>

 <p class="catatan">Menampilkan <?php echo count($katalog); ?> barang. Ketersediaan dihitung untuk <?php echo e($rentang); ?>.</p>
 <div class="table-responsive">
 <table>
 <thead>
 <tr><th>Nama Barang</th><th>Kategori</th><th>Pemilik</th><th>Tersedia</th><th>Maks. hari</th><th>Kondisi</th><?php if ($admin): ?><th>Status</th><?php endif; ?><th>Aksi</th></tr>
 </thead>
 <tbody>
 <?php if (empty($katalog)): ?>
 <tr><td colspan="<?php echo $admin ? 8 : 7; ?>">Tidak ada barang yang cocok.</td></tr>
 <?php else: foreach ($katalog as $b): ?>
 <tr>
 <td><?php echo e($b['nama']); ?><?php if ($b['deskripsi'] !== ''): ?><br><small class="catatan"><?php echo e($b['deskripsi']); ?></small><?php endif; ?></td>
 <td><?php echo e($b['kategori']); ?></td>
 <td><?php echo e($b['org_nama']); ?> <small>(<?php echo e($b['jenis']); ?>)</small></td>
 <td><?php echo (int)$b['tersedia']; ?> / <?php echo (int)$b['total']; ?></td>
 <td><?php echo (int)$b['maks']; ?></td>
 <td><?php echo e($b['kondisi']); ?></td>
 <?php if ($admin): ?><td><?php echo badge($b['status']); ?></td><?php endif; ?>
 <td>
 <a href="kalender.php?id=<?php echo (int)$b['id']; ?>">Kalender</a>
 <?php if ($user['role'] === 'oki' && $user['org'] !== $b['org'] && $b['status'] === 'aktif' && $b['tersedia'] > 0): ?>
 <a class="btn" href="../peminjaman/ajukan.php?id=<?php echo (int)$b['id']; ?>&amp;dari=<?php echo e($dari); ?>&amp;sampai=<?php echo e($sampai); ?>">Ajukan Pinjam</a>
 <?php elseif ($admin): ?>
 <form method="post" action="proses_aksi.php" class="inline" data-konfirmasi="<?php echo $b['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'; ?> barang ini?"><?php echo csrf_field(); ?>
 <input type="hidden" name="aksi" value="toggle"><input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
 <button type="submit" class="<?php echo $b['status'] === 'aktif' ? 'btn-hapus' : ''; ?>"><?php echo $b['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'; ?></button>
 </form>
 <?php endif; ?>
 </td>
 </tr>
 <?php endforeach; endif; ?>
 </tbody>
 </table>
 </div>
 </section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
