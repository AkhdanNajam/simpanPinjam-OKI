<?php
$KONDISI = ['baik', 'rusak ringan', 'rusak berat'];
$JENIS_OKI = ['BEM', 'HMJ', 'UKM', 'Lainnya'];

// Cegah input pengguna terbaca sebagai HTML (XSS).
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Path relatif ke root proyek (logika yang sama dengan $base di header.php), dipakai untuk redirect.
function url(string $path = ''): string {
    $root = realpath(dirname(__DIR__));
    $dir = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
    $rel = ltrim(str_replace('\\', '/', substr($dir, strlen($root))), '/');
    return ($rel === '' ? '' : str_repeat('../', substr_count($rel, '/') + 1)) . $path;
}
function redirect(string $path): void { header('Location: ' . url($path)); exit; }

// Flash message sekali tampil
function set_flash(string $type, string $pesan): void { $_SESSION['flash'] = ['type' => $type, 'pesan' => $pesan]; }
function get_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }

function tgl_valid(string $s): bool {
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return $d && $d->format('Y-m-d') === $s;
}
function jumlah_hari(string $dari, string $sampai): int {
    return (int)(new DateTime($dari))->diff(new DateTime($sampai))->days + 1;
}
function tgl_id(?string $s): string { return $s ? date('d/m/Y', strtotime($s)) : '-'; }
function int_positif($v): bool { return is_numeric($v) && (int)$v == $v && (int)$v >= 1; }

// Ambil parameter GET sebagai string aman (abaikan jika berupa array).
function get_str(string $k): string { $v = $_GET[$k] ?? ''; return is_string($v) ? trim($v) : ''; }

function nama_bulan(int $n): string {
    $b = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $b[$n] ?? '';
}
function bulan_valid(string $s): bool { return (bool)preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $s); }

// Tombol reset data demo hanya aktif bila APP_ENV bukan "production".
function demo_aktif(): bool { return getenv('APP_ENV') !== 'production'; }

/* ---------- CSRF ---------- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
// Panggil di setiap proses_*.php setelah memastikan method POST.
function csrf_cek(string $kembali = 'index.php'): void {
    $t = $_POST['csrf'] ?? '';
    $s = $_SESSION['csrf'] ?? '';
    if (!is_string($t) || !is_string($s) || $s === '' || !hash_equals($s, $t)) {
        set_flash('error', 'Sesi formulir kedaluwarsa. Silakan ulangi.');
        redirect($kembali);
    }
}

/* ---------- ekspor CSV ---------- */
function csv_sel($v): string {
    $s = (string)$v;
    return ($s !== '' && strpos("=+-@\t\r", $s[0]) !== false) ? "'" . $s : $s;   // cegah formula injection di Excel
}
function kirim_csv(string $namaFile, array $header, array $rows): void {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $namaFile . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $header, ',', '"', '\\');
    foreach ($rows as $r) fputcsv($out, array_map('csv_sel', $r), ',', '"', '\\');
    fclose($out);
    exit;
}
