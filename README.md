# SIPINJAM OKI Polinema (PHP + PostgreSQL)

Peminjaman barang antar-OKI Polinema dengan PHP biasa: halaman `.php`, `includes/header.php` + `footer.php`, form `method="post"` ke `proses_*.php`, validasi server, flash message. Data sekarang disimpan di **PostgreSQL** lewat PDO (sebelumnya di `$_SESSION`).

## Persiapan database
1. Pastikan PostgreSQL berjalan dan ekstensi PHP `pdo_pgsql` aktif (di `php.ini`: `extension=pdo_pgsql` dan `extension=pgsql`, lalu restart Apache/Laragon).
2. Buat database dan isi:
   ```bash
   createdb -U postgres sipinjam_oki
   psql -U postgres -d sipinjam_oki -f database/schema.sql
   psql -U postgres -d sipinjam_oki -f database/seed.sql
   ```
3. Atur koneksi di `includes/koneksi.php` (default: host `localhost`, port `5432`, db `sipinjam_oki`, user `postgres`, password kosong) atau lewat environment variable `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.

## Menjalankan di Laragon
1. Salin folder `sipinjam-oki` ke `D:\laragon\laragon\www\`, lalu **Start All**.
2. Buka http://localhost/sipinjam-oki/ (atau http://sipinjam-oki.test).

Alternatif: `cd` ke folder proyek lalu `php -S localhost:8000`.

## Akun demo (password: `password`)
`admin@okipolinema.ac.id`, `bem@`, `hmjti@`, `ukmmusik@`, `hmjte@`, `ukmfoto@`, `ukmpa@` (akhiran `okipolinema.ac.id`; UKM PA belum terverifikasi).
Tombol "Reset data demo" (khusus admin, di footer) memuat ulang `database/seed.sql`.

## Fitur
**OKI sebagai peminjam**
- Pencarian katalog: nama/deskripsi, kategori, jenis OKI pemilik, rentang tanggal, dan opsi "hanya yang tersedia" (`barang/list.php`).
- Kalender ketersediaan per barang, stok tersisa per hari dan jadwal terpakai (`barang/kalender.php`).
- Pengajuan peminjaman: validasi tanggal, durasi maksimal, stok dan bentrok jadwal, tidak boleh meminjam barang sendiri; pembatalan selama masih menunggu.
- Pemantauan status dengan timeline, riwayat status, dan notifikasi.

**OKI sebagai pengelola barang**
- Tambah, ubah (nama, deskripsi, kategori, jumlah, kondisi, lokasi, maks. durasi), nonaktifkan, dan hapus barang. Jumlah total tidak boleh di bawah unit yang sedang dipinjam/dijadwalkan.
- Setujui/tolak (dengan alasan), serah terima dan pengembalian dengan pencatatan kondisi.
- Pemantauan barang (tersedia / sedang dipinjam / terlambat) di "Barang Saya".
- Profil OKI (nama, jenis, kontak, nama pengurus) dan ganti password; OKI yang belum terverifikasi hanya bisa melengkapi profil.

**Admin**
- Verifikasi/tolak OKI, nonaktifkan akun, hapus OKI (jika belum punya riwayat peminjaman).
- Kelola kategori barang (tambah, ubah, hapus jika tidak dipakai) di `kategori/list.php`.
- Moderasi barang (nonaktifkan/aktifkan) dan pemantauan seluruh peminjaman.

**Dashboard & laporan**
- Dashboard berbeda untuk OKI dan Admin: ringkasan angka, peminjaman terbaru, akses cepat.
- Laporan Riwayat Peminjaman Bulanan (`laporan/peminjaman.php`) dengan rekap per jenis OKI dan per kategori.
- Laporan Ringkasan Inventaris Aktif (`laporan/inventaris.php`) dengan pemantauan hari ini.
- Kedua laporan bisa difilter, diunduh sebagai CSV, dan dicetak. Admin melihat semua OKI; OKI hanya melihat data miliknya.

**Keamanan**
- Token CSRF pada semua form POST, logout lewat POST dan sesi dihancurkan, password di-hash, prepared statement.
- Tombol "Reset data demo" nonaktif bila environment variable `APP_ENV=production`.

## Struktur database
`organisasi`, `pengguna`, `kategori`, `barang` (termasuk `deskripsi`), `peminjaman`, `peminjaman_item`, `riwayat_status`, `notifikasi` (lihat `database/schema.sql`). Aturan penting dijaga di database: CHECK untuk status/kondisi/jenis, FK antar tabel, email dan nama organisasi unik (tidak peka huruf besar-kecil), barang yang pernah dipinjam tidak bisa dihapus.

## Struktur kode
`includes/koneksi.php` (PDO), `includes/data.php` (satu-satunya tempat query SQL), `includes/config.php`, `header.php`, `footer.php`; halaman di `barang/`, `peminjaman/`, `oki/`, serta `index.php` (informasi beranda), `login.php`, `register.php`, `notifikasi.php`, `reset.php`. Form login dan pendaftaran dibuka pada halaman terpisah.
Semua query memakai prepared statement. Perubahan status peminjaman berjalan dalam transaksi, dengan penguncian baris barang saat pengajuan dan persetujuan agar stok tidak terlampaui oleh dua permintaan bersamaan.

## Catatan
- Database lama (dibuat sebelum versi ini): jalankan `psql -U postgres -d sipinjam_oki -f database/migrasi_deskripsi.sql` atau ulangi `schema.sql` + `seed.sql`.
- Belum ada upload foto barang (entitas Foto barang di proposal ditunda).
- Untuk deploy: set `DB_*` dan `APP_ENV=production` lewat environment variable.
