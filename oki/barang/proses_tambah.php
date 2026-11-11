
<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/data.php';

$user = require_login(['oki']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('oki/barang/tambah.php');
}

if (empty($_POST) && empty($_FILES) &&
    (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    set_flash('error', 'Ukuran data terlalu besar. Gunakan foto maksimal 2 MB.');
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

$fotoFile = $_FILES['foto'] ?? null;
error_log('DEBUG FOTO: ' . print_r([
    'files' => $_FILES,
    'foto_ada' => $fotoFile !== null,
    'foto_error' => $fotoFile['error'] ?? 'tidak ada',
    'foto_size' => $fotoFile['size'] ?? 0
], true));
$isiFoto  = null;
$mimeFoto = null;
$namaFoto = null;

$errors = [];

// Validasi barang
if ($nama === '') $errors[] = 'Nama barang wajib diisi.';
if (mb_strlen($nama) > 150) $errors[] = 'Nama barang maksimal 150 karakter.';
if (mb_strlen($deskripsi) > 500) $errors[] = 'Deskripsi maksimal 500 karakter.';
if (mb_strlen($lokasi) > 150) $errors[] = 'Lokasi maksimal 150 karakter.';
if (!int_positif($kategori) || !kategori_ada((int)$kategori)) {
    $errors[] = 'Pilih kategori yang tersedia.';
}
if (!int_positif($jumlah)) $errors[] = 'Jumlah total minimal 1.';
if (!int_positif($maks)) $errors[] = 'Maksimal durasi pinjam minimal 1 hari.';
if (!in_array($kondisi, $KONDISI, true)) $errors[] = 'Kondisi tidak valid.';
if (empty($user['org'])) $errors[] = 'Akun Anda belum terhubung ke OKI.';

// Validasi foto
if ($fotoFile && $fotoFile['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($fotoFile['error'] === UPLOAD_ERR_INI_SIZE ||
        $fotoFile['error'] === UPLOAD_ERR_FORM_SIZE) {
        $errors[] = 'Ukuran foto maksimal 2 MB.';
    } elseif ($fotoFile['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Gagal mengunggah foto.';
    } elseif (!is_uploaded_file($fotoFile['tmp_name'])) {
        $errors[] = 'File foto tidak valid.';
    } elseif ($fotoFile['size'] > 2 * 1024 * 1024) {
        $errors[] = 'Ukuran foto maksimal 2 MB.';
    } else {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeFoto = $finfo->file($fotoFile['tmp_name']);

        $daftarFormat = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];

        if (!isset($daftarFormat[$mimeFoto])) {
            $errors[] = 'Format foto harus JPG, PNG, atau WEBP.';
        } else {
            $isiFoto = file_get_contents($fotoFile['tmp_name']);

            if ($isiFoto === false) {
                $errors[] = 'Gagal membaca isi foto.';
            } else {
                $namaFoto = bin2hex(random_bytes(16)) . '.' . $daftarFormat[$mimeFoto];
            }
        }
    }
}

// Jika validasi gagal
if ($errors) {
    set_flash('error', implode(' ', $errors));
    $_SESSION['old'] = compact(
        'nama', 'deskripsi', 'kategori', 'jumlah',
        'maks', 'kondisi', 'lokasi'
    );
    redirect('oki/barang/tambah.php');
}

// Simpan barang dan foto dalam satu transaksi
try {
    $err = transaksi(function () use (
        $user, $kategori, $nama, $jumlah, $maks,
        $kondisi, $lokasi, $deskripsi,
        $isiFoto, $mimeFoto, $namaFoto
    ) {
        // Simpan data barang dan ambil ID-nya
        $idBarang = tambah_barang(
            (int)$user['org'],
            (int)$kategori,
            $nama,
            (int)$jumlah,
            (int)$maks,
            $kondisi,
            $lokasi,
            $deskripsi,
            null
        );

        // Simpan foto jika pengguna mengunggah foto
        if ($isiFoto !== null) {
            $urlFoto = 'db://foto_barang/' . $idBarang;

            $pdo = db();

            $st = $pdo->prepare("
                INSERT INTO foto_barang
                    (id_barang, url_foto, is_utama,
                     data_foto, mime_type, nama_file)
                VALUES
                    (:id, :url, TRUE,
                     :data, :mime, :nama)
            ");

            $st->bindValue(':id', $idBarang, PDO::PARAM_INT);
            $st->bindValue(':url', $urlFoto, PDO::PARAM_STR);
            $st->bindValue(':data', $isiFoto, PDO::PARAM_LOB);
            $st->bindValue(':mime', $mimeFoto, PDO::PARAM_STR);
            $st->bindValue(':nama', $namaFoto, PDO::PARAM_STR);

            $st->execute();
        }

        return null;
    });

    if ($err !== null) {
        throw new RuntimeException($err);
    }

    set_flash('success', 'Barang berhasil ditambahkan.');
    redirect('oki/barang/saya.php');

} catch (Throwable $e) {
    error_log($e->getMessage());

    $_SESSION['old'] = compact(
        'nama', 'deskripsi', 'kategori', 'jumlah',
        'maks', 'kondisi', 'lokasi'
    );

    set_flash('error', 'Barang gagal ditambahkan. Periksa struktur database.');
    redirect('oki/barang/tambah.php');
}
