<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/data.php';

$user = require_login(['oki']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('oki/barang/tambah.php');
}

// Upload melebihi post_max_size: PHP mengosongkan $_POST dan $_FILES,
// sehingga CSRF gagal. Tangkap lebih awal dengan pesan yang jelas.
if (empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    set_flash('error', 'Ukuran data yang dikirim terlalu besar. Gunakan foto maksimal 2 MB.');
    redirect('oki/barang/tambah.php');
}

csrf_cek('oki/barang/tambah.php');
wajib_verified($user, 'oki/barang/tambah.php');

// Ambil data form
$nama      = trim($_POST['nama'] ?? '');
$deskripsi = trim($_POST['deskripsi'] ?? '');
$kategori  = trim($_POST['kategori'] ?? '');
$jumlah    = $_POST['jumlah'] ?? '';
$maks      = $_POST['maks'] ?? '';
$kondisi   = trim($_POST['kondisi'] ?? '');
$lokasi    = trim($_POST['lokasi'] ?? '');

$fotoFile  = $_FILES['foto'] ?? null;
$fotoPath  = null;
$formatFoto = null;
$errors    = [];

// Validasi data barang
if ($nama === '')                  $errors[] = "Nama barang wajib diisi.";
if (mb_strlen($nama) > 150)        $errors[] = "Nama barang maksimal 150 karakter.";
if (mb_strlen($deskripsi) > 500)   $errors[] = "Deskripsi maksimal 500 karakter.";
if (mb_strlen($lokasi) > 150)      $errors[] = "Lokasi maksimal 150 karakter.";
if (!int_positif($kategori) || !kategori_ada((int)$kategori)) $errors[] = "Pilih kategori yang tersedia.";
if (!int_positif($jumlah))         $errors[] = "Jumlah total minimal 1.";
if (!int_positif($maks))           $errors[] = "Maksimal durasi pinjam minimal 1 hari.";
if (!in_array($kondisi, $KONDISI, true)) $errors[] = "Kondisi tidak valid.";
if (empty($user['org']))           $errors[] = "Akun Anda belum terhubung ke OKI.";

// Validasi foto
if ($fotoFile && $fotoFile['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($fotoFile['error'] === UPLOAD_ERR_INI_SIZE || $fotoFile['error'] === UPLOAD_ERR_FORM_SIZE) {
        $errors[] = "Ukuran foto maksimal 2 MB.";
    } elseif ($fotoFile['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Gagal mengunggah foto.";
    } elseif (!is_uploaded_file($fotoFile['tmp_name'])) {
        $errors[] = "File foto tidak valid.";
    } elseif ($fotoFile['size'] > 2 * 1024 * 1024) {
        $errors[] = "Ukuran foto maksimal 2 MB.";
    } else {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($fotoFile['tmp_name']);
        $daftarFormat = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        if (!isset($daftarFormat[$mime])) {
            $errors[] = "Format foto harus JPG, PNG, atau WEBP.";
        } else {
            $formatFoto = $daftarFormat[$mime];
        }
    }
}

if ($errors) {
    set_flash('error', implode(' ', $errors));
    $_SESSION['old'] = compact('nama', 'deskripsi', 'kategori', 'jumlah', 'maks', 'kondisi', 'lokasi');
    redirect('oki/barang/tambah.php');
}

// Simpan foto
if ($formatFoto !== null) {
    $folder = __DIR__ . '/../../uploads/barang/';

    if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) {
        set_flash('error', 'Folder foto gagal dibuat.');
        redirect('oki/barang/tambah.php');
    }

    $namaFoto = bin2hex(random_bytes(16)) . '.' . $formatFoto;

    if (!move_uploaded_file($fotoFile['tmp_name'], $folder . $namaFoto)) {
        set_flash('error', 'Gagal menyimpan foto barang.');
        redirect('oki/barang/tambah.php');
    }

    $fotoPath = 'uploads/barang/' . $namaFoto;
}

// Simpan ke database
try {
    tambah_barang(
        (int)$user['org'],
        (int)$kategori,
        $nama,
        (int)$jumlah,
        (int)$maks,
        $kondisi,
        $lokasi,
        $deskripsi,
        $fotoPath
    );

    set_flash('success', 'Barang berhasil ditambahkan.');
    redirect('oki/barang/saya.php');

} catch (Throwable $e) {
    if ($fotoPath !== null) {
        $file = __DIR__ . '/../../' . $fotoPath;
        if (is_file($file)) unlink($file);
    }
    error_log($e->getMessage());

    $_SESSION['old'] = compact('nama', 'deskripsi', 'kategori', 'jumlah', 'maks', 'kondisi', 'lokasi');
    set_flash('error', 'Barang gagal ditambahkan. Periksa struktur database.');
    redirect('oki/barang/tambah.php');
}