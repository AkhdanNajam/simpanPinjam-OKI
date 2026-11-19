<?php
$page_title = "Katalog Barang";
// $wajib_login sengaja TIDAK didefinisikan: katalog bersifat publik, tamu boleh melihatnya.
include __DIR__ . '/../includes/header.php';
$flash = get_flash();
$admin = $user && $user['role'] === 'admin';
$oki = $user && $user['role'] === 'oki';

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
$colspanKosong = $admin ? 9 : 8;
?>
<section>
 <div class="judul-bar">
  <h2>Katalog Barang</h2>
  <?php if (!empty($user['org']) || !empty($oki)): ?>
   <a href="<?php echo $base; ?>oki/barang/tambah.php" class="btn-icon btn-tambah-barang" title="Tambah Barang" aria-label="Tambah Barang"><i class="bi bi-plus-lg"></i> Tambah Barang</a>
  <?php endif; ?>
 </div>

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

    <div class="filter-aksi">
    <button type="submit" class="btn-icon btn-cari" title="Cari" aria-label="Cari"><i class="bi bi-search"></i></button>
    <a href="list.php" class="btn-icon btn-reset" title="Reset" aria-label="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
  </div>

  <div class="filter-cek"><label><input type="checkbox" name="ada" value="1" <?php echo $f['ada'] ? 'checked' : ''; ?>> Hanya yang tersedia</label></div>
 </form>

 <p class="catatan">Menampilkan <?php echo count($katalog); ?> barang. Ketersediaan dihitung untuk <?php echo e($rentang); ?>.<?php if (!$user): ?> <a href="../login.php">Login</a> untuk mengajukan peminjaman.<?php endif; ?></p>

 <div class="table-responsive">
 <table class="tabel-katalog">
  <colgroup>
   <col style="width:8%">
   <col style="width:<?php echo $admin ? 21 : 23; ?>%">
   <col style="width:13%">
   <col style="width:12%">
   <col style="width:9%">
   <col style="width:8%">
   <col style="width:10%">
   <?php if ($admin): ?><col style="width:9%"><?php endif; ?>
   <col style="width:<?php echo $admin ? 10 : 17; ?>%">
  </colgroup>
  <thead>
   <tr>
    <th>Foto</th>
    <th>Nama Barang</th>
    <th>Kategori</th>
    <th>Pemilik</th>
    <th class="tengah">Tersedia</th>
    <th class="tengah">Maks. hari</th>
    <th>Kondisi</th>
    <?php if ($admin): ?><th>Status</th><?php endif; ?>
    <th>Aksi</th>
   </tr>
  </thead>
  <tbody>
  <?php if (empty($katalog)): ?>
   <tr><td colspan="<?php echo $colspanKosong; ?>">Tidak ada barang yang cocok.</td></tr>
  <?php else: foreach ($katalog as $b): ?>
   <?php
    $aktif = $b['status'] === 'aktif';
    $lbl = $aktif ? 'Nonaktifkan' : 'Aktifkan';
    $srcFoto = $b['foto_url'] ?? '';
    if ($srcFoto !== '' && !preg_match('#^(https?://|\.\./|/)#i', $srcFoto)) {
        $srcFoto = '../' . ltrim($srcFoto, '/');
    }
   ?>
   <tr>
    <td>
     <?php if ($srcFoto !== ''): ?>
      <img
       src="<?php echo e($srcFoto); ?>"
       alt="<?php echo e($b['nama']); ?>"
       style="width:52px;height:52px;object-fit:cover;border-radius:8px;display:block;"
       onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
      >
      <div style="width:52px;height:52px;border-radius:8px;background:#f1f5f9;color:#64748b;display:none;align-items:center;justify-content:center;font-size:11px;">
       No Foto
      </div>
     <?php else: ?>
      <div style="width:52px;height:52px;border-radius:8px;background:#f1f5f9;color:#64748b;display:flex;align-items:center;justify-content:center;font-size:11px;">
       No Foto
      </div>
     <?php endif; ?>
    </td>
    <td>
     <?php echo e($b['nama']); ?>
     <?php if ($b['deskripsi'] !== ''): ?><small class="catatan"><?php echo e($b['deskripsi']); ?></small><?php endif; ?>
    </td>
    <td><?php echo e($b['kategori']); ?></td>
    <td><?php echo e($b['org_nama']); ?></td>
    <td class="tengah"><?php echo (int)$b['tersedia']; ?> / <?php echo (int)$b['total']; ?></td>
    <td class="tengah"><?php echo (int)$b['maks']; ?></td>
    <td><?php echo e(ucfirst(str_replace('_', ' ', $b['kondisi']))); ?></td>
    <?php if ($admin): ?><td><?php echo badge($b['status']); ?></td><?php endif; ?>
    <td class="kolom-aksi">
     <div class="aksi-grup">
      <a href="detail.php?id=<?php echo (int)$b['id']; ?>" class="btn-icon btn-detail" title="Lihat Detail" aria-label="Lihat Detail"><i class="bi-info-circle"></i></a>
      <a href="kalender.php?id=<?php echo (int)$b['id']; ?>" class="btn-icon btn-kalender" title="Kalender" aria-label="Kalender"><i class="bi bi-calendar-event"></i></a>

      <?php if ($oki && $user['org'] !== $b['org'] && $aktif && $b['tersedia'] > 0): ?>
       <a class="btn-icon btn-ajukan" href="../oki/peminjaman/ajukan.php?id=<?php echo (int)$b['id']; ?>&amp;dari=<?php echo e($dari); ?>&amp;sampai=<?php echo e($sampai); ?>" title="Ajukan Pinjam" aria-label="Ajukan Pinjam"><i class="bi bi-box-arrow-in-right"></i></a>
      <?php elseif (!$user && $aktif && $b['tersedia'] > 0): ?>
       <a class="btn-icon btn-ajukan" href="../login.php" title="Login untuk Meminjam" aria-label="Login untuk Meminjam"><i class="bi bi-box-arrow-in-right"></i></a>
      <?php elseif ($admin): ?>
       <form method="post" action="<?php echo $base; ?>admin/barang/proses_aksi.php" class="inline" data-konfirmasi="<?php echo $lbl; ?> barang ini?"><?php echo csrf_field(); ?>
        <input type="hidden" name="aksi" value="toggle"><input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
        <button type="submit" class="btn-icon <?php echo $aktif ? 'btn-nonaktif-barang' : 'btn-verif'; ?>" title="<?php echo $lbl; ?>" aria-label="<?php echo $lbl; ?>">
         <i class="bi <?php echo $aktif ? 'bi-slash-circle' : 'bi-check-circle'; ?>"></i>
        </button>
       </form>
      <?php endif; ?>
     </div>
    </td>
   </tr>
  <?php endforeach; endif; ?>
  </tbody>
 </table>
 </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>