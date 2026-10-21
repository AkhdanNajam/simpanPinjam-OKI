<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/data.php';
$user = require_login(['oki']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('peminjaman/list.php');
csrf_cek('peminjaman/list.php');
wajib_verified($user, 'peminjaman/list.php');

$aksi = $_POST['aksi'] ?? '';
$id = (int)($_POST['id'] ?? 0);
$p = get_peminjaman($id);
$gagal = function (string $pesan) { set_flash('error', $pesan); redirect('peminjaman/list.php'); };
if (!$p) $gagal('Peminjaman tidak ditemukan.');

$syarat = ['setuju' => 'menunggu', 'tolak' => 'menunggu', 'serah' => 'disetujui', 'kembali' => 'dipinjam', 'batal' => 'menunggu'];
if (!isset($syarat[$aksi])) $gagal('Aksi tidak dikenal.');
if ($aksi === 'batal') {
    if ($p['peminjam'] !== $user['org']) $gagal('Bukan pengajuan Anda.');
} elseif ($p['pemilik'] !== $user['org']) {
    $gagal('Bukan barang milik OKI Anda.');
}
if ($p['status'] !== $syarat[$aksi]) $gagal('Status harus ' . $syarat[$aksi] . '.');

$kondisi = $_POST['kondisi'] ?? '';
if (in_array($aksi, ['serah', 'kembali'], true) && !in_array($kondisi, $KONDISI, true)) $gagal('Kondisi tidak valid.');
$alasan = trim($_POST['alasan'] ?? '');
if ($aksi === 'tolak' && $alasan === '') $gagal('Isi alasan penolakan.');
$catatan = trim($_POST['catatan'] ?? '');

// Semua perubahan satu aksi dijalankan dalam satu transaksi; ubah_status() hanya berhasil jika status masih sesuai.
$STATUS_BERUBAH = 'Status peminjaman sudah berubah, muat ulang halaman.';
$err = transaksi(function () use ($aksi, $id, $user, $syarat, $kondisi, $alasan, $catatan, $STATUS_BERUBAH) {
    $dari = $syarat[$aksi];
    if ($aksi === 'setuju') {
        // Cek ulang stok saat persetujuan (pengajuan lain mungkin sudah disetujui lebih dulu).
        $galat = cek_stok_persetujuan($id);
        if ($galat !== null) return $galat;
        $ok = ubah_status($id, $user['id'], $dari, 'disetujui', 'Disetujui pemilik');
    } elseif ($aksi === 'tolak') {
        $ok = ubah_status($id, $user['id'], $dari, 'ditolak', 'Ditolak', $alasan);
    } elseif ($aksi === 'serah') {
        set_kondisi_keluar($id, $kondisi);
        $ok = ubah_status($id, $user['id'], $dari, 'dipinjam', 'Serah terima, kondisi ' . $kondisi);
    } elseif ($aksi === 'kembali') {
        set_pengembalian($id, $kondisi, $catatan);
        $ok = ubah_status($id, $user['id'], $dari, 'selesai', 'Dikembalikan, kondisi ' . $kondisi);
    } else {
        $ok = ubah_status($id, $user['id'], $dari, 'dibatalkan', 'Dibatalkan peminjam');
    }
    return $ok ? null : $STATUS_BERUBAH;
});
if ($err !== null) $gagal($err);
set_flash('success', 'Status peminjaman diperbarui.');
redirect('peminjaman/list.php');
