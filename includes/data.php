<?php
// Seluruh akses data (PostgreSQL via PDO). Halaman tidak menulis SQL langsung; semuanya lewat fungsi di sini.
// Skema: users, organisasi, kategori, barang, peminjaman, detail_peminjaman, log_status, notifikasi.
require_once __DIR__ . '/koneksi.php';

// Potongan SQL: jumlah unit barang yang terpakai (disetujui/dipinjam). Penutup ditambahkan oleh pemanggil.
const HITUNG_STOK = "COALESCE((SELECT SUM(dp.jumlah) FROM detail_peminjaman dp
        JOIN peminjaman p ON p.id_peminjaman = dp.id_peminjaman
        WHERE dp.id_barang = b.id_barang AND p.status IN ('disetujui', 'dipinjam')";

/* ---------- helper query ---------- */
function q(string $sql, array $params = []): PDOStatement {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
function q_all(string $sql, array $params = []): array { return q($sql, $params)->fetchAll(); }
function q_row(string $sql, array $params = []): ?array { $r = q($sql, $params)->fetch(); return $r === false ? null : $r; }
function q_val(string $sql, array $params = []) { $v = q($sql, $params)->fetchColumn(); return $v === false ? null : $v; }

// Jalankan $fn dalam transaksi. $fn mengembalikan null bila sukses, atau string pesan galat (rollback).
function transaksi(callable $fn): ?string {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $err = $fn();
        if ($err !== null) { $pdo->rollBack(); return $err; }
        $pdo->commit();
        return null;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
function ints(?array $r, string ...$kolom): ?array {
    if ($r) foreach ($kolom as $k) if (isset($r[$k])) $r[$k] = (int)$r[$k];
    return $r;
}
function kode_unik(PDOException $e): bool { return $e->getCode() === '23505'; }

/* ---------- autentikasi ---------- */
// Peran dinormalkan: 'admin' tetap admin, selain itu dianggap 'oki'. 'org' = id_organisasi milik akun.
const SQL_USER = "SELECT u.id_user AS id,
        COALESCE(o.nama_organisasi, 'Administrator') AS nama,
        u.email,
        CASE WHEN LOWER(TRIM(u.role::text)) = 'admin' THEN 'admin' ELSE 'oki' END AS role,
        o.id_organisasi AS org,
        TRUE AS aktif";

function user_now() {
    if (!isset($_SESSION['uid'])) return null;
    return ints(q_row(SQL_USER . " FROM users u LEFT JOIN organisasi o ON o.id_user = u.id_user
        WHERE u.id_user = :id", ['id' => $_SESSION['uid']]), 'org');
}
function cari_user_email(string $email): ?array {
    return ints(q_row(SQL_USER . ", u.password_hash AS hash FROM users u LEFT JOIN organisasi o ON o.id_user = u.id_user
        WHERE lower(u.email) = lower(:e)", ['e' => $email]), 'org');
}
function terverifikasi(array $u): bool {
    if (($u['role'] ?? '') === 'admin') return true;
    if (empty($u['org'])) return false;
    return q_val("SELECT status_verifikasi FROM organisasi WHERE id_organisasi = :id", ['id' => $u['org']]) === 'terverifikasi';
}
function require_login(array $roles = []): array {
    $u = user_now();
    if (!$u) { set_flash('error', 'Silakan login terlebih dahulu.'); redirect('login.php'); }
    if ($roles && !in_array($u['role'], array_map('strtolower', $roles), true)) {
        set_flash('error', 'Anda tidak punya akses ke halaman itu.');
        redirect('index.php');
    }
    return $u;
}
function wajib_verified(array $u, string $kembali): void {
    if (($u['role'] ?? '') !== 'admin' && empty($u['org'])) {
        set_flash('error', 'Akun Anda belum terhubung ke organisasi (OKI).');
        redirect($kembali);
    }
    if (!terverifikasi($u)) { set_flash('error', 'OKI Anda belum diverifikasi Admin.'); redirect($kembali); }
}

/* ---------- organisasi ---------- */
function org_nama_ada(string $nama): bool { return (bool)q_val("SELECT 1 FROM organisasi WHERE lower(nama_organisasi) = lower(:n)", ['n' => $nama]); }
function email_ada(string $email): bool { return (bool)q_val("SELECT 1 FROM users WHERE lower(email) = lower(:e)", ['e' => $email]); }
function org_ada(int $id): bool { return (bool)q_val("SELECT 1 FROM organisasi WHERE id_organisasi = :id", ['id' => $id]); }

// Mengembalikan null bila sukses, atau pesan galat.
function daftar_oki(string $nama, string $jenis, string $kontak, string $email, string $hash): ?string {
    try {
        return transaksi(function () use ($nama, $jenis, $kontak, $email, $hash) {
            $uid = (int)q_val("INSERT INTO users (email, password_hash, role) VALUES (:e, :h, 'oki') RETURNING id_user",
                ['e' => $email, 'h' => $hash]);
            q("INSERT INTO organisasi (id_user, nama_organisasi, jenis_oki, kontak, status_verifikasi)
               VALUES (:u, :n, :j, :k, 'menunggu')", ['u' => $uid, 'n' => $nama, 'j' => $jenis, 'k' => $kontak]);
            return null;
        });
    } catch (PDOException $e) {
        if (kode_unik($e)) return 'Nama organisasi atau email sudah terdaftar.';
        throw $e;
    }
}
function statistik_beranda(?int $orgPemilik): array {
    $s = q_row("SELECT
        (SELECT COUNT(*) FROM barang WHERE status = 'aktif') AS aktif,
        (SELECT COUNT(*) FROM organisasi WHERE status_verifikasi = 'terverifikasi') AS oki_ok,
        (SELECT COUNT(*) FROM peminjaman WHERE status IN ('menunggu', 'disetujui', 'dipinjam')) AS jalan");
    $s['masuk'] = $orgPemilik === null ? 0 :
        (int)q_val("SELECT COUNT(*) FROM peminjaman WHERE id_organisasi_pemilik = :o AND status = 'menunggu'", ['o' => $orgPemilik]);
    return array_map('intval', $s);
}
function daftar_org(bool $hanya_aktif = false): array {
    $sql = "SELECT o.id_organisasi AS id, o.id_organisasi AS id_organisasi, o.id_organisasi AS akun_id,
                   o.nama_organisasi AS nama, o.jenis_oki AS jenis, o.kontak,
                   o.status_verifikasi AS verif, o.status_verifikasi AS status,
                   u.email, u.role, 'aktif' AS aktif
            FROM organisasi o LEFT JOIN users u ON o.id_user = u.id_user";
    if ($hanya_aktif) $sql .= " WHERE o.status_verifikasi = 'terverifikasi'";
    return q_all($sql . " ORDER BY o.nama_organisasi ASC");
}
function set_verifikasi(int $org, string $status): void {
    q("UPDATE organisasi SET status_verifikasi = :s WHERE id_organisasi = :id", ['s' => $status, 'id' => $org]);
}
// Skema users belum punya kolom aktif/nonaktif, jadi fungsi ini belum melakukan apa pun.
function toggle_akun_org(int $org): void { }

/* ---------- barang ---------- */
function daftar_kategori(): array {
    return q_all("SELECT id_kategori AS id, nama_kategori AS nama FROM kategori ORDER BY nama_kategori");
}
function kategori_ada($id): bool {
    return (bool)q_val("SELECT 1 FROM kategori WHERE id_kategori = :id", ['id' => (int)$id]);
}

function katalog_barang(string $hari): array {
    $rows = q_all("SELECT b.id_barang AS id, b.id_organisasi AS org, o.nama_organisasi AS org_nama,
            k.nama_kategori AS kategori, b.nama_barang AS nama, b.jumlah AS total, b.kondisi,
            b.maks_hari AS maks, b.foto,
            b.jumlah - " . HITUNG_STOK . " AND p.tgl_pinjam <= :h1 AND p.tgl_kembali_rencana >= :h2), 0) AS tersedia
        FROM barang b
        JOIN organisasi o ON o.id_organisasi = b.id_organisasi
        JOIN kategori k ON k.id_kategori = b.id_kategori
        WHERE b.status = 'aktif' AND o.status_verifikasi = 'terverifikasi'
        ORDER BY b.id_barang", ['h1' => $hari, 'h2' => $hari]);
    return array_map(fn($r) => ints($r, 'id', 'org', 'total', 'maks', 'tersedia'), $rows);
}

function barang_milik(int $org): array {
    $rows = q_all("SELECT b.id_barang AS id, k.nama_kategori AS kategori, b.nama_barang AS nama,
            b.jumlah AS total, b.maks_hari AS maks, b.kondisi, b.status, b.foto,
            EXISTS (SELECT 1 FROM detail_peminjaman dp WHERE dp.id_barang = b.id_barang) AS pernah_dipinjam,
            COALESCE((SELECT SUM(dp.jumlah) FROM detail_peminjaman dp
                      JOIN peminjaman p ON p.id_peminjaman = dp.id_peminjaman
                      WHERE dp.id_barang = b.id_barang AND p.status = 'dipinjam'), 0) AS keluar,
            COALESCE((SELECT SUM(dp.jumlah) FROM detail_peminjaman dp
                      JOIN peminjaman p ON p.id_peminjaman = dp.id_peminjaman
                      WHERE dp.id_barang = b.id_barang AND p.status = 'dipinjam'
                        AND p.tgl_kembali_rencana < CAST(:h AS date)), 0) AS terlambat
        FROM barang b
        JOIN kategori k ON k.id_kategori = b.id_kategori
        WHERE b.id_organisasi = :o
        ORDER BY b.id_barang", ['o' => $org, 'h' => date('Y-m-d')]);
    return array_map(fn($r) => ints($r, 'id', 'total', 'maks', 'keluar', 'terlambat'), $rows);
}

// Status pemantauan barang: tersedia / sedang dipinjam / terlambat.
function status_pantau(array $b): string {
    if ((int)$b['terlambat'] > 0) return 'terlambat';
    if ((int)$b['keluar'] > 0) return 'sedang dipinjam';
    return 'tersedia';
}
function get_barang(int $id): ?array {
    return ints(q_row("SELECT b.id_barang AS id, b.id_organisasi AS org, o.nama_organisasi AS org_nama,
            o.status_verifikasi AS org_verif, k.nama_kategori AS kategori, b.nama_barang AS nama, b.deskripsi,
            b.id_kategori AS kat_id, b.jumlah AS total, b.kondisi, b.lokasi, b.maks_hari AS maks, b.status, b.foto
        FROM barang b
        JOIN organisasi o ON o.id_organisasi = b.id_organisasi
        JOIN kategori k ON k.id_kategori = b.id_kategori
        WHERE b.id_barang = :id", ['id' => $id]), 'id', 'org', 'total', 'maks', 'kat_id');
}
function tambah_barang(int $org, int $kategori, string $nama, int $jumlah, int $maks, string $kondisi,
                       string $lokasi, string $deskripsi = '', ?string $foto = null): int {
    return (int)q_val("INSERT INTO barang (id_organisasi, id_kategori, nama_barang, deskripsi, jumlah, kondisi, lokasi, maks_hari, foto, status)
        VALUES (:o, :k, :n, :d, :j, :kd, :l, :m, :f, 'aktif') RETURNING id_barang",
        ['o' => $org, 'k' => $kategori, 'n' => $nama, 'd' => $deskripsi, 'j' => $jumlah,
         'kd' => $kondisi, 'l' => $lokasi, 'm' => $maks, 'f' => $foto]);
}
function toggle_barang(int $id): void {
    q("UPDATE barang SET status = CASE WHEN status = 'aktif' THEN 'nonaktif' ELSE 'aktif' END WHERE id_barang = :id", ['id' => $id]);
}
function barang_pernah_dipinjam(int $id): bool {
    return (bool)q_val("SELECT 1 FROM detail_peminjaman WHERE id_barang = :id LIMIT 1", ['id' => $id]);
}
function hapus_barang(int $id): void {
    $foto = q_val("SELECT foto FROM barang WHERE id_barang = :id", ['id' => $id]);
    q("DELETE FROM barang WHERE id_barang = :id", ['id' => $id]);
    if ($foto) {
        $file = dirname(__DIR__) . '/' . $foto;
        if (is_file($file)) @unlink($file);
    }
}

/* ---------- pencarian, kalender, edit barang ---------- */
// $f: q, kategori, jenis, dari, sampai, ada. Ketersediaan dihitung untuk rentang dari..sampai (default hari ini).
function cari_barang(array $f = [], bool $hanya_aktif = false): array {
    $dari   = !empty($f['dari']) ? $f['dari'] : date('Y-m-d');
    $sampai = !empty($f['sampai']) ? $f['sampai'] : $dari;
    $p = ['d1' => $dari, 's1' => $sampai];
    $w = [];

    if ($hanya_aktif) $w[] = "b.status = 'aktif' AND o.status_verifikasi = 'terverifikasi'";
    if (!empty($f['q'])) {
        $w[] = "(b.nama_barang ILIKE :q1 OR b.deskripsi ILIKE :q2)";
        $p['q1'] = $p['q2'] = '%' . $f['q'] . '%';
    }
    if (!empty($f['kategori'])) { $w[] = "b.id_kategori = :kat"; $p['kat'] = (int)$f['kategori']; }
    if (!empty($f['jenis']))    { $w[] = "o.jenis_oki = :jenis"; $p['jenis'] = $f['jenis']; }

    $sql = "SELECT b.id_barang AS id, b.id_organisasi AS org, o.nama_organisasi AS org_nama,
                   o.nama_organisasi AS org_name, o.jenis_oki AS jenis, k.nama_kategori AS kategori,
                   b.nama_barang AS nama, b.deskripsi, b.status, b.jumlah AS total, b.kondisi,
                   b.maks_hari AS maks, b.foto,
                   b.jumlah - " . HITUNG_STOK . " AND p.tgl_pinjam <= :s1 AND p.tgl_kembali_rencana >= :d1), 0) AS tersedia
            FROM barang b
            JOIN organisasi o ON o.id_organisasi = b.id_organisasi
            JOIN kategori k ON k.id_kategori = b.id_kategori";
    if ($w) $sql .= " WHERE " . implode(' AND ', $w);
    $sql .= " ORDER BY b.nama_barang, b.id_barang";

    $rows = array_map(fn($r) => ints($r, 'id', 'org', 'total', 'maks', 'tersedia'), q_all($sql, $p));
    if (!empty($f['ada'])) $rows = array_values(array_filter($rows, fn($r) => $r['tersedia'] > 0));
    return $rows;
}
// Jumlah unit terpakai (disetujui/dipinjam) untuk setiap hari pada rentang awal..akhir. Hasil: ['YYYY-MM-DD' => int].
function terpakai_per_hari(int $barang, string $awal, string $akhir): array {
    $n = (int)(new DateTime($awal))->diff(new DateTime($akhir))->days;
    $rows = q_all("SELECT to_char(CAST(:a1 AS date) + g.i, 'YYYY-MM-DD') AS tgl,
            COALESCE((SELECT SUM(dp.jumlah) FROM detail_peminjaman dp JOIN peminjaman p ON p.id_peminjaman = dp.id_peminjaman
                WHERE dp.id_barang = :b AND p.status IN ('disetujui', 'dipinjam')
                  AND p.tgl_pinjam <= CAST(:a2 AS date) + g.i AND p.tgl_kembali_rencana >= CAST(:a3 AS date) + g.i), 0) AS terpakai
        FROM generate_series(0, CAST(:n AS integer)) AS g(i)
        ORDER BY g.i", ['a1' => $awal, 'a2' => $awal, 'a3' => $awal, 'b' => $barang, 'n' => $n]);
    $out = [];
    foreach ($rows as $r) $out[$r['tgl']] = (int)$r['terpakai'];
    return $out;
}
function jadwal_barang(int $barang, string $awal, string $akhir): array {
    return q_all("SELECT p.id_peminjaman AS id, po.nama_organisasi AS peminjam, dp.jumlah, p.tgl_pinjam,
            p.tgl_kembali_rencana AS tgl_rencana, p.status, p.keperluan
        FROM detail_peminjaman dp
        JOIN peminjaman p ON p.id_peminjaman = dp.id_peminjaman
        JOIN organisasi po ON po.id_organisasi = p.id_organisasi_peminjam
        WHERE dp.id_barang = :b AND p.status IN ('disetujui', 'dipinjam')
          AND p.tgl_pinjam <= CAST(:akhir AS date) AND p.tgl_kembali_rencana >= CAST(:awal AS date)
        ORDER BY p.tgl_pinjam, p.id_peminjaman", ['b' => $barang, 'akhir' => $akhir, 'awal' => $awal]);
}
// Pemakaian terbesar pada satu hari mulai hari ini (termasuk barang terlambat yang belum kembali).
function terpakai_maks(int $barang): int {
    $hari = date('Y-m-d');
    $telat = (int)q_val("SELECT COALESCE(SUM(dp.jumlah), 0) FROM detail_peminjaman dp JOIN peminjaman p ON p.id_peminjaman = dp.id_peminjaman
        WHERE dp.id_barang = :b AND p.status = 'dipinjam' AND p.tgl_kembali_rencana < CAST(:h AS date)", ['b' => $barang, 'h' => $hari]);
    $akhir = q_val("SELECT to_char(MAX(p.tgl_kembali_rencana), 'YYYY-MM-DD') FROM detail_peminjaman dp JOIN peminjaman p ON p.id_peminjaman = dp.id_peminjaman
        WHERE dp.id_barang = :b AND p.status IN ('disetujui', 'dipinjam')", ['b' => $barang]);
    $maks = 0;
    if ($akhir && $akhir >= $hari)
        foreach (terpakai_per_hari($barang, $hari, $akhir) as $v) $maks = max($maks, $v);
    return max($maks, $telat);
}
// Mengembalikan null bila sukses, atau pesan galat.

function ubah_barang(
    int $id,
    int $kategori,
    string $nama,
    string $deskripsi,
    int $jumlah,
    int $maks,
    string $kondisi,
    string $lokasi,
    ?string $foto = null
): ?string {
    return transaksi(function () use (
        $id, $kategori, $nama, $deskripsi,
        $jumlah, $maks, $kondisi, $lokasi, $foto
    ) {
        q("SELECT id_barang FROM barang WHERE id_barang = :id FOR UPDATE", [
            'id' => $id
        ]);

        $pakai = terpakai_maks($id);

        if ($jumlah < $pakai) {
            return "Jumlah total tidak boleh kurang dari $pakai (unit yang sedang dipinjam atau sudah dijadwalkan).";
        }

        q("UPDATE barang SET
            id_kategori = :k,
            nama_barang = :n,
            deskripsi = :d,
            jumlah = :j,
            maks_hari = :m,
            kondisi = :kd,
            lokasi = :l,
            foto = COALESCE(:f, foto)
            WHERE id_barang = :id",
            [
                'k' => $kategori,
                'n' => $nama,
                'd' => $deskripsi,
                'j' => $jumlah,
                'm' => $maks,
                'kd' => $kondisi,
                'l' => $lokasi,
                'f' => $foto,
                'id' => $id
            ]
        );

        return null;
    });
}

/* ---------- kategori (admin) ---------- */
function kategori_dengan_jumlah(): array {
    return q_all("SELECT k.id_kategori AS id, k.id_kategori, k.nama_kategori AS nama,
                   (SELECT COUNT(*) FROM barang b WHERE b.id_kategori = k.id_kategori) AS jumlah
            FROM kategori k ORDER BY k.nama_kategori ASC");
}
function simpan_kategori(int $id, string $nama): ?string {
    if (q_val("SELECT 1 FROM kategori WHERE lower(nama_kategori) = lower(:n) AND id_kategori <> :id", ['n' => $nama, 'id' => $id]))
        return 'Nama kategori sudah ada.';
    try {
        if ($id === 0) q("INSERT INTO kategori (nama_kategori) VALUES (:n)", ['n' => $nama]);
        else q("UPDATE kategori SET nama_kategori = :n WHERE id_kategori = :id", ['n' => $nama, 'id' => $id]);
        return null;
    } catch (PDOException $e) {
        if (kode_unik($e)) return 'Nama kategori sudah ada.';
        throw $e;
    }
}
function hapus_kategori(int $id): ?string {
    if (q_val("SELECT 1 FROM barang WHERE id_kategori = :id LIMIT 1", ['id' => $id]))
        return 'Kategori tidak dapat dihapus karena masih digunakan oleh barang.';
    q("DELETE FROM kategori WHERE id_kategori = :id", ['id' => $id]);
    return null;
}

/* ---------- profil & akun ---------- */
function get_org(int $id): ?array {
    return ints(q_row("SELECT id_organisasi AS id, nama_organisasi AS nama, jenis_oki AS jenis, kontak, status_verifikasi AS verif
        FROM organisasi WHERE id_organisasi = :id", ['id' => $id]), 'id');
}
// Skema users tidak punya kolom nama pengurus; yang ditampilkan adalah email akun.
function nama_pengurus($user_id) {
    return q_val("SELECT email FROM users WHERE id_user = :user_id", ['user_id' => $user_id]);
}
function ubah_profil(int $org, int $uid, string $nama, string $jenis, string $kontak, string $pengurus): ?string {
    try {
        q("UPDATE organisasi SET nama_organisasi = :n, jenis_oki = :j, kontak = :k WHERE id_organisasi = :id",
            ['n' => $nama, 'j' => $jenis, 'k' => $kontak, 'id' => $org]);
        return null;
    } catch (PDOException $e) {
        if (kode_unik($e)) return 'Nama organisasi sudah dipakai OKI lain.';
        throw $e;
    }
}
function ganti_password($user_id, $password_lama, $password_baru) {
    $hash = q_val("SELECT password_hash FROM users WHERE id_user = :user_id", ['user_id' => $user_id]);
    if (!$hash || !password_verify($password_lama, $hash)) return false;
    q("UPDATE users SET password_hash = :hash WHERE id_user = :user_id",
        ['hash' => password_hash($password_baru, PASSWORD_DEFAULT), 'user_id' => $user_id]);
    return true;
}
function org_punya_peminjaman(int $id): bool {
    return (bool)q_val("SELECT 1 FROM peminjaman WHERE id_organisasi_peminjam = :a OR id_organisasi_pemilik = :b LIMIT 1", ['a' => $id, 'b' => $id]);
}
function hapus_org(int $id): ?string {
    if (org_punya_peminjaman($id)) return 'OKI sudah punya riwayat peminjaman, tidak bisa dihapus.';
    return transaksi(function () use ($id) {
        $uid = q_val("SELECT id_user FROM organisasi WHERE id_organisasi = :id", ['id' => $id]);
        q("DELETE FROM organisasi WHERE id_organisasi = :id", ['id' => $id]);
        if ($uid) q("DELETE FROM users WHERE id_user = :u AND LOWER(role::text) <> 'admin'", ['u' => $uid]);
        return null;
    });
}

/* ---------- dashboard ---------- */
function dashboard_oki(int $org): array {
    $s = q_row("SELECT
        (SELECT COUNT(*) FROM barang WHERE id_organisasi = :o1) AS total_barang,
        (SELECT COUNT(*) FROM peminjaman WHERE id_organisasi_pemilik = :o2 AND status = 'menunggu') AS masuk,
        (SELECT COUNT(*) FROM peminjaman WHERE (id_organisasi_pemilik = :o3 OR id_organisasi_peminjam = :o4) AND status IN ('disetujui', 'dipinjam')) AS aktif,
        (SELECT COUNT(*) FROM peminjaman WHERE id_organisasi_pemilik = :o5 OR id_organisasi_peminjam = :o6) AS total",
        ['o1' => $org, 'o2' => $org, 'o3' => $org, 'o4' => $org, 'o5' => $org, 'o6' => $org]);
    return array_map('intval', $s);
}
function dashboard_admin(): array {
    $s = q_row("SELECT
        (SELECT COUNT(*) FROM organisasi WHERE status_verifikasi = 'menunggu') AS oki_menunggu,
        (SELECT COUNT(*) FROM organisasi) AS oki_total,
        (SELECT COUNT(*) FROM barang WHERE status = 'aktif') AS barang_aktif,
        (SELECT COUNT(*) FROM peminjaman WHERE status IN ('menunggu', 'disetujui', 'dipinjam')) AS berjalan,
        (SELECT COUNT(*) FROM peminjaman) AS total,
        (SELECT COUNT(*) FROM peminjaman WHERE status = 'dipinjam' AND tgl_kembali_rencana < CAST(:h AS date)) AS terlambat",
        ['h' => date('Y-m-d')]);
    return array_map('intval', $s);
}

/* ---------- query peminjaman (dipakai bersama) ---------- */
const SQL_PINJAM = "SELECT p.*, p.id_peminjaman AS id,
        p.id_organisasi_peminjam AS peminjam, p.id_organisasi_pemilik AS pemilik,
        p.tgl_kembali_rencana AS tgl_rencana, p.tgl_kembali_aktual AS tgl_aktual,
        po.nama_organisasi AS peminjam_nama, po.jenis_oki AS jenis_peminjam, po.jenis_oki AS peminjam_jenis,
        po.kontak AS peminjam_kontak, pk.nama_organisasi AS pemilik_nama,
        (SELECT string_agg(b.nama_barang || ' ×' || dp.jumlah::text, ', ' ORDER BY b.nama_barang)
           FROM detail_peminjaman dp JOIN barang b ON b.id_barang = dp.id_barang
          WHERE dp.id_peminjaman = p.id_peminjaman) AS item_teks,
        (SELECT string_agg(DISTINCT k.nama_kategori, ', ')
           FROM detail_peminjaman dp JOIN barang b ON b.id_barang = dp.id_barang
           JOIN kategori k ON k.id_kategori = b.id_kategori
          WHERE dp.id_peminjaman = p.id_peminjaman) AS kategori_teks
    FROM peminjaman p
    LEFT JOIN organisasi po ON po.id_organisasi = p.id_organisasi_peminjam
    LEFT JOIN organisasi pk ON pk.id_organisasi = p.id_organisasi_pemilik";

// $id_org = 0 untuk semua OKI (admin).
function peminjaman_terbaru(int $id_org = 0, int $limit = 5): array {
    $sql = SQL_PINJAM;
    $params = [];
    if ($id_org > 0) {
        $sql .= " WHERE p.id_organisasi_peminjam = :o1 OR p.id_organisasi_pemilik = :o2";
        $params = ['o1' => $id_org, 'o2' => $id_org];
    }
    return q_all($sql . " ORDER BY p.tgl_pengajuan DESC, p.id_peminjaman DESC LIMIT " . max(1, $limit), $params);
}

/* ---------- laporan ---------- */
// $bulan = YYYY-MM (tanggal pengajuan). Filter kosong/0 berarti semua.
function laporan_peminjaman($bulan, $org_id, $status, $pemilik_id) {
    $sql = SQL_PINJAM . " WHERE 1=1";
    $params = [];
    if (!empty($bulan))                      { $sql .= " AND to_char(p.tgl_pengajuan, 'YYYY-MM') = :bulan"; $params['bulan'] = $bulan; }
    if (!empty($org_id) && $org_id > 0)      { $sql .= " AND p.id_organisasi_peminjam = :org_id"; $params['org_id'] = $org_id; }
    if (!empty($status))                     { $sql .= " AND p.status = :status"; $params['status'] = $status; }
    if (!empty($pemilik_id) && $pemilik_id > 0) { $sql .= " AND p.id_organisasi_pemilik = :pemilik_id"; $params['pemilik_id'] = $pemilik_id; }
    return q_all($sql . " ORDER BY p.tgl_pinjam DESC, p.id_peminjaman DESC", $params);
}
function inventaris($kategori_id = 0, $org_id = 0, $status = '') {
    $sql = "SELECT b.*, b.id_barang AS id, b.nama_barang AS nama, b.jumlah AS total,
                   k.nama_kategori AS kategori, o.nama_organisasi AS organisasi_nama,
                   COALESCE((SELECT SUM(dp.jumlah) FROM detail_peminjaman dp JOIN peminjaman p ON p.id_peminjaman = dp.id_peminjaman
                             WHERE dp.id_barang = b.id_barang AND p.status = 'dipinjam'), 0) AS keluar,
                   COALESCE((SELECT SUM(dp.jumlah) FROM detail_peminjaman dp JOIN peminjaman p ON p.id_peminjaman = dp.id_peminjaman
                             WHERE dp.id_barang = b.id_barang AND p.status = 'dipinjam'
                               AND p.tgl_kembali_rencana < CAST(:h AS date)), 0) AS terlambat
            FROM barang b
            LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
            LEFT JOIN organisasi o ON o.id_organisasi = b.id_organisasi
            WHERE 1=1";
    $params = ['h' => date('Y-m-d')];
    if (!empty($kategori_id) && $kategori_id > 0) { $sql .= " AND b.id_kategori = :kategori_id"; $params['kategori_id'] = $kategori_id; }
    if (!empty($org_id) && $org_id > 0)           { $sql .= " AND b.id_organisasi = :org_id"; $params['org_id'] = $org_id; }
    if (!empty($status))                          { $sql .= " AND b.status = :status"; $params['status'] = $status; }
    $rows = q_all($sql . " ORDER BY b.nama_barang ASC", $params);
    foreach ($rows as &$r) {
        $r = ints($r, 'id', 'total', 'keluar', 'terlambat');
        $r['tersedia'] = max(0, $r['total'] - $r['keluar']);
    }
    return $rows;
}

/* ---------- logika peminjaman ---------- */
// Stok tersedia untuk rentang tanggal; peminjaman berstatus disetujui/dipinjam yang beririsan dihitung terpakai.
function stok_tersedia(int $barang, string $dari, string $sampai, int $kecuali = 0): int {
    $v = q_val("SELECT b.jumlah - " . HITUNG_STOK . " AND p.id_peminjaman <> :kecuali
            AND p.tgl_pinjam <= :sampai AND p.tgl_kembali_rencana >= :dari), 0) FROM barang b WHERE b.id_barang = :id",
        ['kecuali' => $kecuali, 'sampai' => $sampai, 'dari' => $dari, 'id' => $barang]);
    return (int)($v ?? 0);
}
function nama_item(int $pid): string {
    return (string)q_val("SELECT string_agg(b.nama_barang || ' ×' || dp.jumlah::text, ', ' ORDER BY b.nama_barang)
        FROM detail_peminjaman dp JOIN barang b ON b.id_barang = dp.id_barang WHERE dp.id_peminjaman = :p", ['p' => $pid]);
}
// $mode: 'semua' (admin), 'masuk' (OKI sebagai pemilik), 'keluar' (OKI sebagai peminjam)
function daftar_peminjaman(string $mode, int $org_id = 0) {
    if ($mode === 'semua')  return q_all(SQL_PINJAM . " ORDER BY p.tgl_pengajuan DESC, p.id_peminjaman DESC");
    if ($mode === 'masuk')  return q_all(SQL_PINJAM . " WHERE p.id_organisasi_pemilik = :o ORDER BY p.tgl_pengajuan DESC, p.id_peminjaman DESC", ['o' => $org_id]);
    if ($mode === 'keluar') return q_all(SQL_PINJAM . " WHERE p.id_organisasi_peminjam = :o ORDER BY p.tgl_pengajuan DESC, p.id_peminjaman DESC", ['o' => $org_id]);
    return [];
}
function get_peminjaman($id) {
    return q_row(SQL_PINJAM . " WHERE p.id_peminjaman = :id", ['id' => $id]);
}
function item_peminjaman($pid): array {
    return q_all("SELECT b.nama_barang AS nama, dp.jumlah, dp.kondisi_keluar, dp.kondisi_kembali,
                   dp.catatan_kerusakan AS cr, dp.catatan_kerusakan AS catatan
            FROM detail_peminjaman dp JOIN barang b ON b.id_barang = dp.id_barang
            WHERE dp.id_peminjaman = :pid ORDER BY b.nama_barang", ['pid' => $pid]);
}
function riwayat_peminjaman($pid): array {
    return q_all("SELECT to_char(l.waktu_perubahan, 'DD/MM/YYYY HH24:MI') AS waktu,
                   to_char(l.waktu_perubahan, 'DD/MM/YYYY HH24:MI') AS tanggal,
                   COALESCE(u.email, 'Sistem') AS user_nama,
                   l.status_lama AS lama, l.status_baru AS baru, l.catatan AS ket
            FROM log_status l LEFT JOIN users u ON u.id_user = l.id_user
            WHERE l.id_peminjaman = :pid ORDER BY l.waktu_perubahan DESC", ['pid' => $pid]);
}

// Buat pengajuan. Mengembalikan id peminjaman, atau null bila stok habis (dicek ulang dengan kunci baris barang).
function buat_peminjaman(array $user, array $b, int $jumlah, string $dari, string $sampai, string $keperluan, string $catatan): ?int {
    $pid = null;
    transaksi(function () use (&$pid, $user, $b, $jumlah, $dari, $sampai, $keperluan, $catatan) {
        q("SELECT id_barang FROM barang WHERE id_barang = :id FOR UPDATE", ['id' => $b['id']]);
        if ($jumlah > stok_tersedia($b['id'], $dari, $sampai)) return 'stok';
        $pid = (int)q_val("INSERT INTO peminjaman (id_organisasi_peminjam, id_organisasi_pemilik, keperluan, catatan, tgl_pengajuan, tgl_pinjam, tgl_kembali_rencana, status)
            VALUES (:pm, :pk, :kp, :ct, NOW(), :d, :s, 'menunggu') RETURNING id_peminjaman",
            ['pm' => $user['org'], 'pk' => $b['org'], 'kp' => $keperluan, 'ct' => $catatan, 'd' => $dari, 's' => $sampai]);
        q("INSERT INTO detail_peminjaman (id_peminjaman, id_barang, jumlah) VALUES (:p, :b, :j)",
            ['p' => $pid, 'b' => $b['id'], 'j' => $jumlah]);
        q("INSERT INTO log_status (id_peminjaman, id_user, status_lama, status_baru, catatan, waktu_perubahan)
           VALUES (:p, :u, '', 'menunggu', 'Pengajuan dibuat', NOW())", ['p' => $pid, 'u' => $user['id']]);
        $peminjam = q_val("SELECT nama_organisasi FROM organisasi WHERE id_organisasi = :id", ['id' => $user['org']]);
        notif_org((int)$b['org'], $pid, 'Pengajuan baru', "$peminjam mengajukan peminjaman {$b['nama']} ×$jumlah (#$pid)");
        return null;
    });
    return $pid;
}
// Kirim notifikasi ke akun milik organisasi $org. $judul dipakai sebagai awalan pesan.
function notif_org(int $org, int $pid, string $judul, string $pesan): void {
    q("INSERT INTO notifikasi (id_user, id_peminjaman, pesan, is_dibaca, created_at)
       SELECT o.id_user, CAST(:p AS integer), CAST(:m AS text), FALSE, NOW()
       FROM organisasi o WHERE o.id_organisasi = :o AND o.id_user IS NOT NULL",
        ['p' => $pid, 'm' => $judul . ': ' . $pesan, 'o' => $org]);
}
// Ubah status secara atomik (hanya jika status saat ini = $dari). Panggil di dalam transaksi().
function ubah_status(int $pid, int $uid, string $dari, string $baru, string $ket = '', ?string $alasan = null): bool {
    $p = q_row("UPDATE peminjaman SET status = :baru, alasan = COALESCE(:alasan, alasan)
        WHERE id_peminjaman = :id AND status = :dari
        RETURNING id_organisasi_peminjam AS peminjam, id_organisasi_pemilik AS pemilik",
        ['baru' => $baru, 'alasan' => $alasan, 'id' => $pid, 'dari' => $dari]);
    if (!$p) return false;
    q("INSERT INTO log_status (id_peminjaman, id_user, status_lama, status_baru, catatan, waktu_perubahan)
       VALUES (:p, :u, :l, :b, :k, NOW())", ['p' => $pid, 'u' => $uid, 'l' => $dari, 'b' => $baru, 'k' => $ket]);
    $n = nama_item($pid);
    if (in_array($baru, ['disetujui', 'ditolak', 'dipinjam', 'selesai'], true))
        notif_org((int)$p['peminjam'], $pid, 'Status peminjaman', "Peminjaman $n (#$pid): $baru");
    if ($baru === 'dibatalkan')
        notif_org((int)$p['pemilik'], $pid, 'Pengajuan dibatalkan', "Pengajuan $n (#$pid) dibatalkan oleh peminjam");
    return true;
}
// Cek ulang stok saat persetujuan; baris barang dikunci. Panggil di dalam transaksi().
function cek_stok_persetujuan(int $pid): ?string {
    $p = get_peminjaman($pid);
    q("SELECT b.id_barang FROM barang b JOIN detail_peminjaman dp ON dp.id_barang = b.id_barang
       WHERE dp.id_peminjaman = :p ORDER BY b.id_barang FOR UPDATE OF b", ['p' => $pid]);
    foreach (q_all("SELECT dp.id_barang, dp.jumlah, b.nama_barang AS nama FROM detail_peminjaman dp
                    JOIN barang b ON b.id_barang = dp.id_barang WHERE dp.id_peminjaman = :p", ['p' => $pid]) as $i)
        if ((int)$i['jumlah'] > stok_tersedia((int)$i['id_barang'], $p['tgl_pinjam'], $p['tgl_rencana'], $pid))
            return 'Bentrok jadwal: stok ' . $i['nama'] . ' tidak cukup pada tanggal tersebut.';
    return null;
}
function set_kondisi_keluar(int $pid, string $kondisi): void {
    q("UPDATE detail_peminjaman SET kondisi_keluar = :k WHERE id_peminjaman = :p", ['k' => $kondisi, 'p' => $pid]);
}
function set_pengembalian(int $pid, string $kondisi, string $catatan): void {
    q("UPDATE detail_peminjaman SET kondisi_kembali = :k, catatan_kerusakan = :c WHERE id_peminjaman = :p",
        ['k' => $kondisi, 'c' => $catatan, 'p' => $pid]);
    q("UPDATE peminjaman SET tgl_kembali_aktual = :t WHERE id_peminjaman = :p", ['t' => date('Y-m-d'), 'p' => $pid]);
}
// Status tampilan: "dipinjam" yang lewat tanggal rencana dianggap terlambat.
function status_tampil(array $p): string {
    return ($p['status'] === 'dipinjam' && $p['tgl_rencana'] < date('Y-m-d')) ? 'terlambat' : $p['status'];
}
function badge(string $s): string { return '<span class="badge st-' . e(str_replace(' ', '-', $s)) . '">' . e($s) . '</span>'; }

/* ---------- notifikasi ---------- */
function jumlah_notif_baru($id_user) {
    return (int)q_val("SELECT COUNT(*) FROM notifikasi WHERE id_user = :uid AND is_dibaca = FALSE", ['uid' => $id_user]);
}
function daftar_notifikasi($user_id) {
    return q_all("SELECT id_notifikasi AS id, id_notifikasi, id_peminjaman AS pid, pesan AS judul, pesan,
                   is_dibaca AS dibaca, created_at AS waktu
            FROM notifikasi WHERE id_user = :user_id ORDER BY created_at DESC", ['user_id' => $user_id]);
}
function tandai_notif_dibaca($user_id) {
    return q("UPDATE notifikasi SET is_dibaca = TRUE WHERE id_user = :user_id", ['user_id' => $user_id]);
}

/* ---------- reset data demo ---------- */
function reset_data_demo(): void { db()->exec(file_get_contents(dirname(__DIR__) . '/database/seed.sql')); }