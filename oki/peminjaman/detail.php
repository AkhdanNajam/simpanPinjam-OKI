<?php
$page_title = "Detail Peminjaman";
$wajib_login = ['admin', 'oki'];
include __DIR__ . '/../../includes/header.php';
$p = get_peminjaman((int)($_GET['id'] ?? 0));

$id_peminjam = $user['peminjam'] ?? ($user['id_peminjam'] ?? 0);
$org_id = $user['org'] ?? ($user['id_organisasi'] ?? ($_SESSION['org'] ?? 0));

// Definisikan hak akses pemilik barang atau admin
$is_pemilik = ($user['role'] === 'admin' || (int)($p['id_organisasi_pemilik'] ?? 0) === (int)$org_id);
$KOND = $KONDISI ?? ['Baik', 'Kurang Baik', 'Rusak'];
?>
 <section>
 <h2>Peminjaman #<?php echo (int)$p['id']; ?> <?php echo badge(status_tampil($p)); ?></h2>
 <?php
 $langkah = ['menunggu' => 1, 'disetujui' => 2, 'dipinjam' => 3, 'selesai' => 4][$p['status']] ?? 1;
 $label = ['Pengajuan', 'Persetujuan', 'Serah terima', 'Pengembalian'];
 ?>
 <?php if (in_array($p['status'], ['ditolak', 'dibatalkan'], true)): ?>
 <p class="catatan">Pengajuan <?php echo e($p['status']); ?><?php echo $p['alasan'] ? ': ' . e($p['alasan']) : ''; ?>.</p>
 <?php else: ?>
 <ol class="timeline">
 <?php foreach ($label as $i => $l): ?>
 <li class="<?php echo $i < $langkah ? 'selesai' : ''; ?> <?php echo $i === $langkah ? 'berikut' : ''; ?>"><span><?php echo $i + 1; ?></span><?php echo e($l); ?></li>
 <?php endforeach; ?>
 </ol>
 <?php endif; ?>
 <p>
 <strong>Peminjam:</strong> <?php echo e($p['peminjam_nama']); ?><br>
 <strong>Pemilik:</strong> <?php echo e($p['pemilik_nama']); ?><br>
 <strong>Keperluan:</strong> <?php echo e($p['keperluan']); ?><?php if ($p['catatan']): ?> (<?php echo e($p['catatan']); ?>)<?php endif; ?><br>
 <strong>Tanggal:</strong> <?php echo tgl_id($p['tgl_pinjam']); ?> – <?php echo tgl_id($p['tgl_rencana']); ?><br>
 <strong>Dikembalikan:</strong> <?php echo tgl_id($p['tgl_aktual']); ?>
 </p>

 <!-- BAGIAN AKSI BERDASARKAN STATUS PEMINJAMAN -->
 <?php if ($is_pemilik): ?>

     <!-- 1. STATUS: MENUNGGU (Tombol Setuju & Tolak) -->
     <?php if ($p['status'] === 'menunggu'): ?>
     <div class="card no-print" style="margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;">
         <h3>Proses Pengajuan Peminjaman</h3>
         <p>Silakan tinjau permohonan ini dan berikan keputusan.</p>
         
         <div style="display: flex; flex-direction: column; gap: 15px; margin-top: 15px;">
             <!-- Tombol Setujui -->
             <div>
                 <form method="post" action="proses_aksi.php" style="margin: 0;">
                     <?php echo csrf_field(); ?>
                     <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                     <input type="hidden" name="aksi" value="setuju">
                     <button type="submit" class="btn btn-setuju" style="background: #09653a; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; transition: background 0.2s; width: 140px; text-align: center;">Setujui</button>
                 </form>
             </div>

             <!-- Tombol Tolak di atas, diikuti kolom alasan di bawahnya -->
             <div>
                 <form method="post" action="proses_aksi.php" style="display: flex; flex-direction: column; gap: 10px; margin: 0;">
                     <?php echo csrf_field(); ?>
                     <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                     <input type="hidden" name="aksi" value="tolak">
                     
                     <div>
                         <button type="submit" class="btn btn-tolak" style="background: #b91c1c; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; transition: background 0.2s; width: 140px; text-align: center;">Tolak</button>
                     </div>

                     <div>
                         <input type="text" name="alasan" placeholder="Masukkan alasan menolak (wajib diisi)..." required style="padding: 10px; border: 1px solid #ccc; border-radius: 6px; width: 100%; max-width: 500px;">
                     </div>
                 </form>
             </div>
         </div>
     </div>

     <!-- 2. STATUS: DISETUJUI (Tombol Serah Terima) -->
     <?php elseif ($p['status'] === 'disetujui'): ?>
     <div class="card no-print" style="margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;">
         <h3>Serah Terima Barang</h3>
         <p>Pengajuan telah disetujui. Silakan lakukan proses serah terima barang kepada peminjam.</p>
         
         <form method="post" action="proses_aksi.php" style="margin-top: 15px; display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
             <?php echo csrf_field(); ?>
             <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
             <input type="hidden" name="aksi" value="serah">
             
             <div>
                 <label style="display: block; font-size: 13px; margin-bottom: 5px; font-weight: 600;">Kondisi Keluar:</label>
                 <select name="kondisi" style="padding: 9px; border-radius: 6px; border: 1px solid #ccc;">
                     <?php foreach ($KOND as $k): ?>
                         <option value="<?php echo e($k); ?>"><?php echo e($k); ?></option>
                     <?php endforeach; ?>
                 </select>
             </div>

             <div style="align-self: flex-end;">
                 <button type="submit" class="btn btn-serah" style="background: #084cb1; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; transition: background 0.2s;">Proses Serah Terima</button>
             </div>
         </form>
     </div>

     <!-- 3. STATUS: DIPINJAM (Tombol Terima Kembali) -->
     <?php elseif ($p['status'] === 'dipinjam'): ?>
     <div class="card no-print" style="margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;">
         <h3>Pengembalian Barang</h3>
         <p>Barang sedang dalam masa peminjaman. Catat kondisi saat barang dikembalikan.</p>
         
         <form method="post" action="proses_aksi.php" style="margin-top: 15px; display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
             <?php echo csrf_field(); ?>
             <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
             <input type="hidden" name="aksi" value="kembali">
             
             <div>
                 <label style="display: block; font-size: 13px; margin-bottom: 5px; font-weight: 600;">Kondisi Kembali:</label>
                 <select name="kondisi" style="padding: 9px; border-radius: 6px; border: 1px solid #ccc;">
                     <?php foreach ($KOND as $k): ?>
                         <option value="<?php echo e($k); ?>"><?php echo e($k); ?></option>
                     <?php endforeach; ?>
                 </select>
             </div>

             <div>
                 <label style="display: block; font-size: 13px; margin-bottom: 5px; font-weight: 600;">Catatan Kerusakan / Keterangan:</label>
                 <input type="text" name="catatan" placeholder="Catatan (opsional)..." style="padding: 9px; border: 1px solid #ccc; border-radius: 6px; width: 250px;">
             </div>

             <div style="align-self: flex-end;">
                 <button type="submit" class="btn btn-kembali" style="background: #eabb2f; color: #000; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: background 0.2s;">Terima Kembali</button>
             </div>
         </form>
     </div>
     <?php endif; ?>

<?php endif; ?>

 <!-- Efek Hover Tombol -->
 <style>
     .btn-setuju:hover { background: #074e2d !important; }
     .btn-tolak:hover { background: #991b1b !important; }
     .btn-serah:hover { background: #05419b !important; }
     .btn-kembali:hover { background: #ffca2c !important; }
 </style>

 <h3>Barang</h3>
 <div class="table-responsive"><table>
 <thead><tr><th>Barang</th><th>Jumlah</th><th>Kondisi keluar</th><th>Kondisi kembali</th><th>Catatan</th></tr></thead>
 <tbody>
 <?php foreach (item_peminjaman($p['id']) as $i): ?>
 <tr><td><?php echo e($i['nama']); ?></td><td><?php echo (int)$i['jumlah']; ?></td>
 <td><?php echo e($i['kk'] ?? '-'); ?></td><td><?php echo e($i['kb'] ?? '-'); ?></td><td><?php echo e($i['cr'] ?: '-'); ?></td></tr>
 <?php endforeach; ?>
 </tbody></table></div>
 <h3>Riwayat Status</h3>
 <ul class="notif">
 <?php foreach (riwayat_peminjaman($p['id']) as $l): ?>
 <li><small><?php echo e($l['waktu']); ?></small> &mdash; <?php echo e($l['user_nama']); ?>: <?php echo e($l['lama'] ?: 'baru'); ?> → <strong><?php echo e($l['baru']); ?></strong><?php if ($l['ket']): ?> (<?php echo e($l['ket']); ?>)<?php endif; ?></li>
 <?php endforeach; ?>
 </ul>
 <p><a href="list.php">&larr; Kembali</a></p>
 </section>
<?php include __DIR__ . '/../../includes/footer.php'; ?>