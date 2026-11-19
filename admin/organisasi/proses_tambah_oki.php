<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/data.php';

$user = require_login(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/organisasi/tambah_oki.php');
}

if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    set_flash('error', 'ukuran data terlalu besar.');
    redirect('admin/organisasi/tambah_oki.php');
}

csrf_cek('admin/organisasi/tambah_oki.php');

// Ambil data form OKI & akun login
$nama             = trim($_POST['nama'] ?? '');
$jenis            = trim($_POST['jenis'] ?? '');
$kontak           = trim($_POST['kontak'] ?? '');
$statusVerifikasi = trim($_POST['status_verifikasi'] ?? 'terverifikasi');
$email            = trim($_POST['email'] ?? '');
$password         = $_POST['password'] ?? '';
$statusAkun       = trim($_POST['status_akun'] ?? 'aktif');

$errors = [];

// Validasi data OKI sesuai constraint database
if ($nama === '') $errors[] = 'Nama OKI wajib diisi.';
if (mb_strlen($nama) > 150) $errors[] = 'Nama OKI maksimal 150 karakter.';
if (!in_array($jenis, ['LT', 'HMJ', 'UKM', 'Lainnya'], true)) {
    $errors[] = 'Jenis OKI tidak valid.';
}
if ($kontak === '') $errors[] = 'Kontak wajib diisi.';
if (mb_strlen($kontak) > 50) $errors[] = 'Kontak maksimal 50 karakter.';
if (!in_array($statusVerifikasi, ['menunggu', 'terverifikasi', 'ditolak'], true)) {
    $errors[] = 'Status verifikasi tidak valid.';
}

// Validasi akun login OKI
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Format email login tidak valid.';
}
if (mb_strlen($email) > 150) $errors[] = 'Email maksimal 150 karakter.';
if (mb_strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
if (!in_array($statusAkun, ['aktif', 'nonaktif'], true)) {
    $errors[] = 'Status akun tidak valid.';
}

// Cek duplikasi email pada tabel users
if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $pdo = db();
    $cekEmail = $pdo->prepare("select id_user from users where lower(email) = lower(:email) limit 1");
    $cekEmail->execute([':email' => $email]);
    if ($cekEmail->fetch()) {
        $errors[] = 'Email login sudah terdaftar.';
    }
}

// Jika validasi gagal
if ($errors) {
    set_flash('error', implode(' ', $errors));
    $_SESSION['old'] = compact(
        'nama', 'jenis', 'kontak', 'statusVerifikasi',
        'email', 'statusAkun'
    );
    redirect('admin/organisasi/tambah_oki.php');
}

// Simpan akun login (users) dan data organisasi (organisasi) dalam satu transaksi
try {
    $err = transaksi(function () use (
        $nama, $jenis, $kontak, $statusVerifikasi,
        $email, $password
    ) {
        $pdo = db();

        // 1. Insert akun login role 'organisasi' ke tabel users
        $hashPassword = password_hash($password, PASSWORD_DEFAULT);
        $stUser = $pdo->prepare("
            insert into users (email, password_hash, role)
            values (:email, :password_hash, 'organisasi')
            returning id_user
        ");
        $stUser->execute([
            ':email'         => $email,
            ':password_hash' => $hashPassword,
        ]);

        $idUser = (int)$stUser->fetchColumn();
        if ($idUser <= 0) {
            return 'Gagal mengambil id_user akun baru.';
        }

        // 2. Insert data organisasi OKI yang berelasi ke id_user
        $stOrg = $pdo->prepare("
            insert into organisasi (id_user, nama_organisasi, jenis_oki, kontak, status_verifikasi)
            values (:id_user, :nama_organisasi, :jenis_oki, :kontak, :status_verifikasi)
        ");
        $stOrg->execute([
            ':id_user'           => $idUser,
            ':nama_organisasi'   => $nama,
            ':jenis_oki'         => $jenis,
            ':kontak'            => $kontak,
            ':status_verifikasi' => $statusVerifikasi,
        ]);

        return null;
    });

    if ($err !== null) {
        throw new RuntimeException($err);
    }

    unset($_SESSION['old']);
    set_flash('success', 'Data OKI dan akun login berhasil ditambahkan.');
    redirect('admin/organisasi/list.php');

} catch (Throwable $e) {
    error_log($e->getMessage());

    $_SESSION['old'] = compact(
        'nama', 'jenis', 'kontak', 'statusVerifikasi',
        'email', 'statusAkun'
    );

    set_flash('error', 'Data OKI gagal ditambahkan: ' . $e->getMessage());
    redirect('admin/organisasi/tambah_oki.php');
}