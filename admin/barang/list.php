<?php
$page_title = "Moderasi Barang";
$wajib_login = ['admin'];
include __DIR__ . '/../../includes/header.php';
$flash = get_flash();

$hari = date('Y-m-d');
$dari = get_str('dari');
$sampai = get_str('sampai');
if (!tgl_valid($dari)) $dari = $hari;
if (!tgl_valid($sampai) || $sampai < $dari) $sampai = $dari;
$jenis = get_str('jenis');
if (!in_array($jenis, $JENIS_OKI, true)) $jenis = '';
$f = ['q' => get_str('q'), 'kategori' => (int)get_str('kategori'), 'jenis' => $jenis,
      'dari' => $dari, 'sampai' => $sampai, 'ada' => get_str('ada') === '1'];
$katalog = cari_barang($f, false);   // admin melihat semua barang, termasuk yang nonaktif
?>
<section>
 <h2>Moderasi Barang</h2>
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

  <div class="filter-aksi">
   <button type="submit" class="btn-icon btn-cari" title="Cari" aria-label="Cari"><i class="bi bi-search"></i></button>
   <a href="list.php" class="btn-icon btn-reset" title="Reset" aria-label="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
  </div>
 </form>

 <p class="catatan">Menampilkan <?php echo count($katalog); ?> barang.</p>
 <div class="table-responsive">
 <table class="tabel-moderasi">
  <colgroup>
   <col style="width:24%"><col style="width:13%"><col style="width:12%">
   <col style="width:9%"><col style="width:9%"><col style="width:12%">
   <col style="width:10%"><col style="width:11%">
  </colgroup>
  <thead>
   <tr>
    <th>Nama Barang</th><th>Kategori</th><th>Pemilik</th>
    <th class="tengah">Tersedia</th><th class="tengah">Maks. hari</th>
    <th>Kondisi</th><th>Status</th><th>Aksi</th>
   </tr>
  </thead>
  <tbody>
  <?php if (empty($katalog)): ?>
   <tr><td colspan="8">Tidak ada barang yang cocok.</td></tr>
  <?php else: foreach ($katalog as $b): ?>
   <?php $aktif = $b['status'] === 'aktif'; $lbl = $aktif ? 'Nonaktifkan' : 'Aktifkan'; ?>
   <tr>
    <td>
     <?php echo e($b['nama']); ?>
     <?php if ($b['deskripsi'] !== ''): ?><small class="catatan"><?php echo e($b['deskripsi']); ?></small><?php endif; ?>
    </td>
    <td><?php echo e($b['kategori']); ?></td>
    <td><?php echo e($b['org_nama']); ?></td>
    <td class="tengah"><?php echo (int)$b['tersedia']; ?> / <?php echo (int)$b['total']; ?></td>
    <td class="tengah"><?php echo (int)$b['maks']; ?></td>
    <td><?php echo e(ucfirst(str_replace('_', ' ', $b['kondisi']))); ?></td>
    <td><?php echo badge($b['status']); ?></td>
    <td class="kolom-aksi">
     <div class="aksi-grup">
      <a href="<?php echo $base; ?>katalog/kalender.php?id=<?php echo (int)$b['id']; ?>" class="btn-icon btn-kalender" title="Kalender" aria-label="Kalender"><i class="bi bi-calendar-event"></i></a>
      <form method="post" action="proses_aksi.php" class="inline" data-konfirmasi="<?php echo $lbl; ?> barang ini?"><?php echo csrf_field(); ?>
       <input type="hidden" name="aksi" value="toggle"><input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
       <button type="submit" class="btn-icon <?php echo $aktif ? 'btn-nonaktif-barang' : 'btn-verif'; ?>" title="<?php echo $lbl; ?>" aria-label="<?php echo $lbl; ?>">
        <i class="bi <?php echo $aktif ? 'bi-slash-circle' : 'bi-check-circle'; ?>"></i>
       </button>
      </form>
     </div>
    </td>
   </tr>
  <?php endforeach; endif; ?>
  </tbody>
 </table>
 </div>
</section>
<?php include __DIR__ . '/../../includes/footer.php'; ?>