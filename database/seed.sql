-- =====================================================================
-- SIPINJAM OKI - data awal (template untuk diisi sendiri)
-- Aktif: hanya admin dan BEM. Bagian yang diawali "--" adalah contoh
-- kosongan: hapus tanda "--" di depan barisnya, lalu isi datanya.
-- Password akun bawaan: "password"
-- File ini juga dipakai tombol "Reset data demo" (khusus admin).
-- =====================================================================

BEGIN;

TRUNCATE notifikasi, riwayat_status, peminjaman_item, peminjaman, barang, pengguna, organisasi
    RESTART IDENTITY CASCADE;

-- ---------------------------------------------------------------------
-- 1. ORGANISASI
--    jenis             : 'BEM' | 'HMJ' | 'UKM' | 'Lainnya'
--    status_verifikasi : 'menunggu' | 'terverifikasi' | 'ditolak'
-- ---------------------------------------------------------------------
INSERT INTO organisasi (id, nama, jenis, kontak, status_verifikasi) VALUES
    (1, 'BEM Polinema', 'BEM', '081200000001', 'terverifikasi');
    -- Tambah organisasi lain (ganti titik koma di baris atas menjadi koma):
    -- ,(2, 'NAMA ORGANISASI', 'HMJ', '08xxxxxxxxxx', 'terverifikasi')

-- ---------------------------------------------------------------------
-- 2. PENGGUNA
--    role: 'admin' (org_id NULL) | 'oki' (org_id wajib diisi)
--    password_hash: hash bcrypt. Contoh di bawah = kata "password".
--    Buat hash baru: php -r "echo password_hash('katasandi', PASSWORD_DEFAULT);"
-- ---------------------------------------------------------------------
INSERT INTO pengguna (id, org_id, nama, email, password_hash, role) VALUES
    (1, NULL, 'Admin Web',             'admin@okipolinema.ac.id', '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
    (2, 1,    'Pengurus BEM Polinema', 'bem@okipolinema.ac.id',   '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'oki');
    -- Tambah akun lain (ganti titik koma di baris atas menjadi koma):
    -- ,(3, 2, 'NAMA PENGURUS', 'email@domain.ac.id', '<hash bcrypt>', 'oki')

-- ---------------------------------------------------------------------
-- 3. BARANG (kosong - isi sendiri, atau tambah lewat aplikasi)
--    kategori_id: 1 Elektronik | 2 Perlengkapan | 3 Multimedia & Dokumentasi
--                 4 Furnitur & Dekorasi | 5 Lainnya
--    kondisi: 'baik' | 'rusak ringan' | 'rusak berat'
--    jumlah_total >= 1, maks_hari >= 1, status: 'aktif' | 'nonaktif'
--    org_id = id organisasi pemilik barang
-- ---------------------------------------------------------------------
-- INSERT INTO barang (id, org_id, kategori_id, nama, deskripsi, jumlah_total, kondisi, lokasi, maks_hari, status) VALUES
--     (1, 1, 1, 'NAMA BARANG', 'Deskripsi barang', 1, 'baik', 'Lokasi penyimpanan', 3, 'aktif');
--     ,(2, 1, 2, 'NAMA BARANG', 'Deskripsi barang', 1, 'baik', 'Lokasi penyimpanan', 3, 'aktif')

-- ---------------------------------------------------------------------
-- 4. PEMINJAMAN (kosong - butuh minimal 2 organisasi, peminjam <> pemilik)
--    status: 'menunggu' | 'disetujui' | 'ditolak' | 'dipinjam' | 'selesai' | 'dibatalkan'
--    tgl_rencana >= tgl_pinjam; tgl_aktual diisi hanya jika status 'selesai'
-- ---------------------------------------------------------------------
-- INSERT INTO peminjaman (id, peminjam_org_id, pemilik_org_id, keperluan, tgl_pinjam, tgl_rencana, tgl_aktual, status, alasan) VALUES
--     (1, 2, 1, 'Keperluan kegiatan', '2026-11-01', '2026-11-03', NULL, 'menunggu', '');

-- INSERT INTO peminjaman_item (peminjaman_id, barang_id, jumlah, kondisi_keluar, kondisi_kembali) VALUES
--     (1, 1, 1, NULL, NULL);

-- INSERT INTO riwayat_status (peminjaman_id, pengguna_id, status_lama, status_baru, keterangan) VALUES
--     (1, 3, '', 'menunggu', 'Pengajuan dibuat');

-- INSERT INTO notifikasi (pengguna_id, peminjaman_id, judul, pesan, sudah_baca) VALUES
--     (2, 1, 'Pengajuan baru', 'Isi pesan notifikasi', FALSE);

-- ---------------------------------------------------------------------
-- Sinkronkan sequence (jangan dihapus, agar ID data baru dari aplikasi tidak bentrok)
-- ---------------------------------------------------------------------
SELECT setval(pg_get_serial_sequence('organisasi',      'id'), (SELECT MAX(id) FROM organisasi));
SELECT setval(pg_get_serial_sequence('pengguna',        'id'), (SELECT MAX(id) FROM pengguna));
SELECT setval(pg_get_serial_sequence('barang',          'id'), (SELECT MAX(id) FROM barang));
SELECT setval(pg_get_serial_sequence('peminjaman',      'id'), (SELECT MAX(id) FROM peminjaman));
SELECT setval(pg_get_serial_sequence('peminjaman_item', 'id'), (SELECT MAX(id) FROM peminjaman_item));
SELECT setval(pg_get_serial_sequence('riwayat_status',  'id'), (SELECT MAX(id) FROM riwayat_status));
SELECT setval(pg_get_serial_sequence('notifikasi',      'id'), (SELECT MAX(id) FROM notifikasi));

COMMIT;
