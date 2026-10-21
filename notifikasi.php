<?php
$page_title = "Notifikasi";
$wajib_login = [];
include __DIR__ . '/includes/header.php';
$mine = daftar_notifikasi($user['id']);
?>
 <section>
 <h2>Notifikasi</h2>
 <?php if (!$mine): ?>
 <p>Belum ada notifikasi.</p>
 <?php else: ?>
 <ul class="notif">
 <?php foreach ($mine as $n): ?>
 <li class="<?php echo $n['baca'] ? '' : 'baru'; ?>">
 <strong><?php echo e($n['judul']); ?></strong> <small><?php echo e($n['waktu']); ?></small><br>
 <?php echo e($n['pesan']); ?>
 <a href="peminjaman/detail.php?id=<?php echo (int)$n['pid']; ?>">Lihat</a>
 </li>
 <?php endforeach; ?>
 </ul>
 <?php endif; ?>
 </section>
<?php
// Tandai terbaca setelah ditampilkan (yang "baru" tetap terlihat pada kunjungan ini).
tandai_notif_dibaca($user['id']);
include __DIR__ . '/includes/footer.php';
