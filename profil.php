<?php
$page_title = "Profil";
$wajib_login = [];
include __DIR__ . '/includes/header.php';
$flash = get_flash();
$old = $_SESSION['old'] ?? []; unset($_SESSION['old']);
$oki = $user['role'] === 'oki';
$org = $oki ? get_org($user['org']) : null;
$pengurus = nama_pengurus($user['id']);
?>
 <section>
 <h2>Profil</h2>
 <?php if ($flash): ?>
 <p class="flash flash-<?php echo e($flash['type']); ?>"><?php echo e($flash['pesan']); ?></p>
 <?php endif; ?>

 <?php if ($oki && $org): ?>
 <p>Status verifikasi OKI: <?php echo badge($org['verif']); ?>
 <?php if ($org['verif'] !== 'terverifikasi'): ?><br><small class="catatan">Selama belum terverifikasi, Anda hanya dapat melengkapi profil.</small><?php endif; ?></p>
 <h3>Data OKI</h3>
 <form method="post" action="proses_profil.php"><?php echo csrf_field(); ?>
 <input type="hidden" name="aksi" value="profil">
 <label for="nama">Nama Organisasi</label>
 <input type="text" id="nama" name="nama" maxlength="150" value="<?php echo e($old['nama'] ?? $org['nama']); ?>" required>
 <label for="jenis">Jenis OKI</label>
 <select id="jenis" name="jenis" required>
 <?php foreach ($JENIS_OKI as $j): ?>
 <option value="<?php echo e($j); ?>" <?php echo ($old['jenis'] ?? $org['jenis']) === $j ? 'selected' : ''; ?>><?php echo e($j); ?></option>
 <?php endforeach; ?>
 </select>
 <label for="kontak">Kontak</label>
 <input type="text" id="kontak" name="kontak" maxlength="20" value="<?php echo e($old['kontak'] ?? $org['kontak']); ?>">
 <label for="pengurus">Nama Pengurus (akun)</label>
 <input type="text" id="pengurus" name="pengurus" maxlength="150" value="<?php echo e($old['pengurus'] ?? $pengurus); ?>" required>
 <button type="submit">Simpan Profil</button>
 </form>
 <?php else: ?>
 <p>Akun: <strong><?php echo e($pengurus); ?></strong> (<?php echo e($user['role']); ?>) &middot; <?php echo e($user['email']); ?></p>
 <?php endif; ?>

 <h3>Ganti Password</h3>
 <form method="post" action="proses_profil.php"><?php echo csrf_field(); ?>
 <input type="hidden" name="aksi" value="password">
 <label for="lama">Password lama</label>
 <input type="password" id="lama" name="lama" required>
 <label for="baru">Password baru (min. 6 karakter)</label>
 <input type="password" id="baru" name="baru" minlength="6" required>
 <label for="ulang">Ulangi password baru</label>
 <input type="password" id="ulang" name="ulang" minlength="6" required>
 <button type="submit">Ganti Password</button>
 </form>
 </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
