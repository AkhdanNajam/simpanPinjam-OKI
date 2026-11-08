
<?php
session_start();

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/data.php';

$user = require_login(['oki']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('oki/barang/saya.php');
}

if (empty($_POST) && empty($_FILES) &&
    (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    set_flash('error', 'Ukuran data terlalu besar. Gunakan foto maksimal 2 MB.');
    redirect('oki/barang/saya.php');
}

csrf_cek('oki/barang/saya.php');
wajib_verified($user, 'oki/barang/saya.php');

$id = (int)($_POST['id'] ?? 0);
$b = get_barang($id);

if (!$b || $b['org'] !== $user['org']) {
    set_flash('error', 'Barang tidak ditemukan.');
    redirect('oki/barang/saya.php');
}

$balik = 'oki/barang/edit.php?id=' . $id;

$nama = trim($_POST['nama'] ?? '');
$deskripsi = trim($_POST['deskripsi'] ?? '');
$kategori = trim($_POST['kategori'] ?? '');
$jumlah = $_POST['jumlah'] ?? '';
$maks = $_POST['maks'] ?? '';
$kondisi = trim($_POST['kondisi'] ?? '');
$lokasi = trim($_POST['lokasi'] ?? '');

$fotoFile = $_FILES['foto'] ?? null;
$fotoPath = null;
$formatFoto = null;
$errors = [];

if ($nama === '') $errors[] = "Nama barang wajib diisi.";
if (mb_strlen($nama) > 150) $errors[] = "Nama barang maksimal 150 karakter.";
if (mb_strlen($deskripsi) > 500) $errors[] = "Deskripsi maksimal 500 karakter.";
if (mb_strlen($lokasi) > 150) $errors[] = "Lokasi maksimal 150 karakter.";

if (!int_positif($kategori) || !kategori_ada((int)$kategori)) {
    $errors[] = "Pilih kategori yang tersedia.";
}

if (!int_positif($jumlah)) {
    $errors[] = "Jumlah total minimal 1.";
}

if (!int_positif($maks)) {
    $errors[] = "Maksimal durasi pinjam minimal 1 hari.";
}

if (!in_array($kondisi, $KONDISI, true)) {
    $errors[] = "Kondisi tidak valid.";
}

// Validasi foto baru
if ($fotoFile && $fotoFile['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($fotoFile['error'] === UPLOAD_ERR_INI_SIZE ||
        $fotoFile['error'] === UPLOAD_ERR_FORM_SIZE) {
        $errors[] = "Ukuran foto maksimal 2 MB.";
    } elseif ($fotoFile['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Gagal mengunggah foto.";
    } elseif (!is_uploaded_file($fotoFile['tmp_name'])) {
        $errors[] = "File foto tidak valid.";
    } elseif ($fotoFile['size'] > 2 * 1024 * 1024) {
        $errors[] = "Ukuran foto maksimal 2 MB.";
    } else {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($fotoFile['tmp_name']);

        $daftarFormat = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        if (!isset($daftarFormat[$mime])) {
            $errors[] = "Format foto harus JPG, PNG, atau WEBP.";
        } else {
            $formatFoto = $daftarFormat[$mime];
        }
    }
}

// Jika ada kesalahan, kembali ke halaman edit
if ($errors) {
    set_flash('error', implode(' ', $errors));

    $_SESSION['old'] = compact(
        'nama', 'deskripsi', 'kategori',
        'jumlah', 'maks', 'kondisi', 'lokasi'
    );

    redirect($balik);
}

// Simpan file foto baru jika dipilih
if ($formatFoto !== null) {
    $folder = __DIR__ . '/../../uploads/barang/';

    if (!is_dir($folder) &&
        !mkdir($folder, 0755, true) &&
        !is_dir($folder)) {
        set_flash('error', 'Folder foto gagal dibuat.');
        redirect($balik);
    }

    $namaFoto = bin2hex(random_bytes(16)) . '.' . $formatFoto;

    if (!move_uploaded_file($fotoFile['tmp_name'], $folder . $namaFoto)) {
        set_flash('error', 'Gagal menyimpan foto barang.');
        redirect($balik);
    }

    $fotoPath = 'uploads/barang/' . $namaFoto;
}

// Simpan perubahan ke database
try {
    $galat = ubah_barang(
        $id,
        (int)$kategori,
        $nama,
        $deskripsi,
        (int)$jumlah,
        (int)$maks,
        $kondisi,
        $lokasi,
        $fotoPath
    );

    if ($galat !== null) {
        throw new RuntimeException($galat);
    }

    // Hapus foto lama setelah database berhasil diperbarui
    if ($fotoPath !== null && !empty($b['foto'])) {
        $fotoLama = __DIR__ . '/../../' . $b['foto'];

        if (is_file($fotoLama)) {
            unlink($fotoLama);
        }
    }

    set_flash('success', 'Data barang berhasil diperbarui.');
    redirect('oki/barang/saya.php');

} catch (Throwable $e) {
    // Hapus foto baru jika penyimpanan database gagal
    if ($fotoPath !== null) {
        $fotoBaru = __DIR__ . '/../../' . $fotoPath;

        if (is_file($fotoBaru)) {
            unlink($fotoBaru);
        }
    }

    error_log($e->getMessage());

    set_flash('error', $e->getMessage());
    redirect($balik);
}
