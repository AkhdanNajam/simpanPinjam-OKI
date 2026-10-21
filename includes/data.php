<?php
// Seluruh akses data (PostgreSQL via PDO). Halaman tidak menulis SQL langsung; semuanya lewat fungsi di sini.
require_once __DIR__ . '/koneksi.php';

const HITUNG_STOK = "COALESCE((SELECT SUM(pi.jumlah) FROM peminjaman_item pi
        JOIN peminjaman p ON p.id = pi.peminjaman_id
        WHERE pi.barang_id = b.id AND p.status IN ('disetujui', 'dipinjam')";

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
function user_now(): ?array {
    if (!isset($_SESSION['uid'])) return null;
    $u = q_row("SELECT id, COALESCE(org_id, 0) AS org, nama, email, role, aktif FROM pengguna WHERE id = :id",
        ['id' => (int)$_SESSION['uid']]);
    $u = ints($u, 'id', 'org');
    return ($u && $u['aktif']) ? $u : null;
}
function cari_user_email(string $email): ?array {
    return ints(q_row("SELECT id, nama, password_hash AS hash, aktif FROM pengguna WHERE lower(email) = lower(:e)", ['e' => $email]), 'id');
}
function terverifikasi(array $u): bool {
    if ($u['role'] === 'admin') return true;
    return q_val("SELECT status_verifikasi FROM organisasi WHERE id = :id", ['id' => $u['org']]) === 'terverifikasi';
}
function require_login(array $roles = []): array {
    $u = user_now();
    if (!$u) { set_flash('error', 'Silakan login terlebih dahulu.'); redirect('login.php'); }
    if ($roles && !in_array($u['role'], $roles, true)) { set_flash('error', 'Anda tidak punya akses ke halaman itu.'); redirect('index.php'); }
    return $u;
}
function wajib_verified(array $u, string $kembali): void {
    if (!terverifikasi($u)) { set_flash('error', 'OKI Anda belum diverifikasi Admin.'); redirect($kembali); }
}

/* ---------- organisasi ---------- */
function org_nama_ada(string $nama): bool { return (bool)q_val("SELECT 1 FROM organisasi WHERE lower(nama) = lower(:n)", ['n' => $nama]); }
function email_ada(string $email): bool { return (bool)q_val("SELECT 1 FROM pengguna WHERE lower(email) = lower(:e)", ['e' => $email]); }
function org_ada(int $id): bool { return (bool)q_val("SELECT 1 FROM organisasi WHERE id = :id", ['id' => $id]); }

// Mengembalikan null bila sukses, atau pesan galat (mis. nama/email dobel karena balapan).
function daftar_oki(string $nama, string $jenis, string $kontak, string $email, string $hash): ?string {
    try {
        return transaksi(function () use ($nama, $jenis, $kontak, $email, $hash) {
            $oid = (int)q_val("INSERT INTO organisasi (nama, jenis, kontak) VALUES (:n, :j, :k) RETURNING id",
                ['n' => $nama, 'j' => $jenis, 'k' => $kontak]);
            q("INSERT INTO pengguna (org_id, nama, email, password_hash, role) VALUES (:o, :nm, :e, :h, 'oki')",
                ['o' => $oid, 'nm' => "Pengurus $nama", 'e' => $email, 'h' => $hash]);
            return null;
        });
    } catch (PDOException $e) {
        if (kode_unik($e)) return 'Nama organisasi atau email sudah terdaftar.';
        throw $e;
    }
}
// Beranda & daftar OKI
function statistik_beranda(?int $orgPemilik): array {
    $s = q_row("SELECT
        (SELECT COUNT(*) FROM barang WHERE status = 'aktif') AS aktif,
        (SELECT COUNT(*) FROM organisasi WHERE status_verifikasi = 'terverifikasi') AS oki_ok,
        (SELECT COUNT(*) FROM peminjaman WHERE status IN ('menunggu', 'disetujui', 'dipinjam')) AS jalan");
    $s['masuk'] = $orgPemilik === null ? 0 :
        (int)q_val("SELECT COUNT(*) FROM peminjaman WHERE pemilik_org_id = :o AND status = 'menunggu'", ['o' => $orgPemilik]);
    return array_map('intval', $s);
}
function daftar_org(bool $admin): array {
    $sql = "SELECT o.id, o.nama, o.jenis, o.kontak, o.status_verifikasi AS verif, a.id AS akun_id, a.email, a.aktif
            FROM organisasi o
            LEFT JOIN LATERAL (SELECT id, email, aktif FROM pengguna WHERE org_id = o.id ORDER BY id LIMIT 1) a ON TRUE"
         . ($admin ? '' : " WHERE o.status_verifikasi = 'terverifikasi'") . " ORDER BY o.id";
    return q_all($sql);
}
function set_verifikasi(int $org, string $status): void {
    q("UPDATE organisasi SET status_verifikasi = :s WHERE id = :id", ['s' => $status, 'id' => $org]);
}
function toggle_akun_org(int $org): void { q("UPDATE pengguna SET aktif = NOT aktif WHERE org_id = :id", ['id' => $org]); }

/* ---------- barang ---------- */
function daftar_kategori(): array { return q_all("SELECT id, nama FROM kategori ORDER BY id"); }
function kategori_ada(int $id): bool { return (bool)q_val("SELECT 1 FROM kategori WHERE id = :id", ['id' => $id]); }

function katalog_barang(string $hari): array {
    $rows = q_all("SELECT b.id, b.org_id AS org, o.nama AS org_nama, k.nama AS kategori, b.nama,
            b.jumlah_total AS total, b.kondisi, b.maks_hari AS maks,
            b.jumlah_total - " . HITUNG_STOK . " AND p.tgl_pinjam <= :h1 AND p.tgl_rencana >= :h2), 0) AS tersedia
        FROM barang b
        JOIN organisasi o ON o.id = b.org_id
        JOIN kategori k ON k.id = b.kategori_id
        WHERE b.status = 'aktif' AND o.status_verifikasi = 'terverifikasi'
        ORDER BY b.id", ['h1' => $hari, 'h2' => $hari]);
    return array_map(fn($r) => ints($r, 'id', 'org', 'total', 'maks', 'tersedia'), $rows);
}
function barang_milik(int $org): array {
    $rows = q_all("SELECT b.id, k.nama AS kategori, b.nama, b.jumlah_total AS total, b.maks_hari AS maks, b.kondisi, b.status,
            EXISTS (SELECT 1 FROM peminjaman_item pi WHERE pi.barang_id = b.id) AS pernah_dipinjam,
            COALESCE((SELECT SUM(pi.jumlah) FROM peminjaman_item pi JOIN peminjaman p ON p.id = pi.peminjaman_id
                WHERE pi.barang_id = b.id AND p.status = 'dipinjam'), 0) AS keluar,
            COALESCE((SELECT SUM(pi.jumlah) FROM peminjaman_item pi JOIN peminjaman p ON p.id = pi.peminjaman_id
                WHERE pi.barang_id = b.id AND p.status = 'dipinjam' AND p.tgl_rencana < CAST(:h AS date)), 0) AS terlambat
        FROM barang b JOIN kategori k ON k.id = b.kategori_id
        WHERE b.org_id = :o ORDER BY b.id", ['o' => $org, 'h' => date('Y-m-d')]);
    return array_map(fn($r) => ints($r, 'id', 'total', 'maks', 'keluar', 'terlambat'), $rows);
}
// Status pemantauan barang: tersedia / sedang dipinjam / terlambat (dihitung dari data peminjaman berjalan).
function status_pantau(array $b): string {
    if ((int)$b['terlambat'] > 0) return 'terlambat';
    if ((int)$b['keluar'] > 0) return 'sedang dipinjam';
    return 'tersedia';
}
function get_barang(int $id): ?array {
    return ints(q_row("SELECT b.id, b.org_id AS org, o.nama AS org_nama, o.status_verifikasi AS org_verif, k.nama AS kategori,
            b.nama, b.deskripsi, b.kategori_id AS kat_id, b.jumlah_total AS total, b.kondisi, b.lokasi, b.maks_hari AS maks, b.status
        FROM barang b JOIN organisasi o ON o.id = b.org_id JOIN kategori k ON k.id = b.kategori_id
        WHERE b.id = :id", ['id' => $id]), 'id', 'org', 'total', 'maks', 'kat_id');
}
function tambah_barang(int $org, int $kategori, string $nama, int $jumlah, int $maks, string $kondisi, string $lokasi, string $deskripsi = ''): int {
    return (int)q_val("INSERT INTO barang (org_id, kategori_id, nama, deskripsi, jumlah_total, kondisi, lokasi, maks_hari)
        VALUES (:o, :k, :n, :d, :j, :kd, :l, :m) RETURNING id",
        ['o' => $org, 'k' => $kategori, 'n' => $nama, 'd' => $deskripsi, 'j' => $jumlah, 'kd' => $kondisi, 'l' => $lokasi, 'm' => $maks]);
}
function toggle_barang(int $id): void {
    q("UPDATE barang SET status = CASE WHEN status = 'aktif' THEN 'nonaktif' ELSE 'aktif' END WHERE id = :id", ['id' => $id]);
}
function barang_pernah_dipinjam(int $id): bool {
    return (bool)q_val("SELECT 1 FROM peminjaman_item WHERE barang_id = :id LIMIT 1", ['id' => $id]);
}
function hapus_barang(int $id): void { q("DELETE FROM barang WHERE id = :id", ['id' => $id]); }

/* ---------- pencarian, kalender, edit barang ---------- */
// $f: q, kategori, jenis, dari, sampai, ada. Ketersediaan dihitung untuk rentang dari..sampai.
function cari_barang(array $f, bool $semuaStatus = false): array {
    $w = ["o.status_verifikasi = 'terverifikasi'"];
    if (!$semuaStatus) $w[] = "b.status = 'aktif'";
    $p = ['dari' => $f['dari'], 'sampai' => $f['sampai']];
    if ($f['q'] !== '') {
        $w[] = "(b.nama ILIKE :q1 OR b.deskripsi ILIKE :q2)";
        $like = '%' . addcslashes($f['q'], '%_\\') . '%';
        $p['q1'] = $like; $p['q2'] = $like;
    }
    if ($f['kategori'] > 0) { $w[] = "b.kategori_id = :kat"; $p['kat'] = $f['kategori']; }
    if ($f['jenis'] !== '') { $w[] = "o.jenis = :jenis"; $p['jenis'] = $f['jenis']; }
    $rows = q_all("SELECT b.id, b.org_id AS org, o.nama AS org_nama, o.jenis, k.nama AS kategori, b.nama, b.deskripsi, b.status,
            b.jumlah_total AS total, b.kondisi, b.maks_hari AS maks,
            b.jumlah_total - " . HITUNG_STOK . " AND p.tgl_pinjam <= :sampai AND p.tgl_rencana >= :dari), 0) AS tersedia
        FROM barang b
        JOIN organisasi o ON o.id = b.org_id
        JOIN kategori k ON k.id = b.kategori_id
        WHERE " . implode(' AND ', $w) . "
        ORDER BY b.nama, b.id", $p);
    $rows = array_map(fn($r) => ints($r, 'id', 'org', 'total', 'maks', 'tersedia'), $rows);
    if (!empty($f['ada'])) $rows = array_values(array_filter($rows, fn($r) => $r['tersedia'] > 0));
    return $rows;
}
// Jumlah unit terpakai (disetujui/dipinjam) untuk setiap hari pada rentang awal..akhir. Hasil: ['YYYY-MM-DD' => int].
function terpakai_per_hari(int $barang, string $awal, string $akhir): array {
    $n = (int)(new DateTime($awal))->diff(new DateTime($akhir))->days;
    $rows = q_all("SELECT to_char(CAST(:a1 AS date) + g.i, 'YYYY-MM-DD') AS tgl,
            COALESCE((SELECT SUM(pi.jumlah) FROM peminjaman_item pi JOIN peminjaman p ON p.id = pi.peminjaman_id
                WHERE pi.barang_id = :b AND p.status IN ('disetujui', 'dipinjam')
                  AND p.tgl_pinjam <= CAST(:a2 AS date) + g.i AND p.tgl_rencana >= CAST(:a3 AS date) + g.i), 0) AS terpakai
        FROM generate_series(0, CAST(:n AS integer)) AS g(i)
        ORDER BY g.i", ['a1' => $awal, 'a2' => $awal, 'a3' => $awal, 'b' => $barang, 'n' => $n]);
    $out = [];
    foreach ($rows as $r) $out[$r['tgl']] = (int)$r['terpakai'];
    return $out;
}
function jadwal_barang(int $barang, string $awal, string $akhir): array {
    return q_all("SELECT p.id, po.nama AS peminjam, pi.jumlah, p.tgl_pinjam, p.tgl_rencana, p.status, p.keperluan
        FROM peminjaman_item pi
        JOIN peminjaman p ON p.id = pi.peminjaman_id
        JOIN organisasi po ON po.id = p.peminjam_org_id
        WHERE pi.barang_id = :b AND p.status IN ('disetujui', 'dipinjam')
          AND p.tgl_pinjam <= CAST(:akhir AS date) AND p.tgl_rencana >= CAST(:awal AS date)
        ORDER BY p.tgl_pinjam, p.id", ['b' => $barang, 'akhir' => $akhir, 'awal' => $awal]);
}
// Pemakaian terbesar pada satu hari mulai hari ini (termasuk barang terlambat yang belum kembali).
function terpakai_maks(int $barang): int {
    $hari = date('Y-m-d');
    $telat = (int)q_val("SELECT COALESCE(SUM(pi.jumlah), 0) FROM peminjaman_item pi JOIN peminjaman p ON p.id = pi.peminjaman_id
        WHERE pi.barang_id = :b AND p.status = 'dipinjam' AND p.tgl_rencana < CAST(:h AS date)", ['b' => $barang, 'h' => $hari]);
    $akhir = q_val("SELECT to_char(MAX(p.tgl_rencana), 'YYYY-MM-DD') FROM peminjaman_item pi JOIN peminjaman p ON p.id = pi.peminjaman_id
        WHERE pi.barang_id = :b AND p.status IN ('disetujui', 'dipinjam')", ['b' => $barang]);
    $maks = 0;
    if ($akhir && $akhir >= $hari)
        foreach (terpakai_per_hari($barang, $hari, $akhir) as $v) $maks = max($maks, $v);
    return max($maks, $telat);
}
// Mengembalikan null bila sukses, atau pesan galat.
function ubah_barang(int $id, int $kategori, string $nama, string $deskripsi, int $jumlah, int $maks, string $kondisi, string $lokasi): ?string {
    return transaksi(function () use ($id, $kategori, $nama, $deskripsi, $jumlah, $maks, $kondisi, $lokasi) {
        q("SELECT id FROM barang WHERE id = :id FOR UPDATE", ['id' => $id]);
        $pakai = terpakai_maks($id);
        if ($jumlah < $pakai) return "Jumlah total tidak boleh kurang dari $pakai (unit yang sedang dipinjam atau sudah dijadwalkan).";
        q("UPDATE barang SET kategori_id = :k, nama = :n, deskripsi = :d, jumlah_total = :j, maks_hari = :m, kondisi = :kd, lokasi = :l WHERE id = :id",
            ['k' => $kategori, 'n' => $nama, 'd' => $deskripsi, 'j' => $jumlah, 'm' => $maks, 'kd' => $kondisi, 'l' => $lokasi, 'id' => $id]);
        return null;
    });
}

/* ---------- kategori (admin) ---------- */
function kategori_dengan_jumlah(): array {
    return q_all("SELECT k.id, k.nama, (SELECT COUNT(*) FROM barang b WHERE b.kategori_id = k.id) AS jumlah FROM kategori k ORDER BY k.id");
}
function simpan_kategori(int $id, string $nama): ?string {
    if (q_val("SELECT 1 FROM kategori WHERE lower(nama) = lower(:n) AND id <> :id", ['n' => $nama, 'id' => $id])) return 'Nama kategori sudah ada.';
    try {
        if ($id === 0) q("INSERT INTO kategori (nama) VALUES (:n)", ['n' => $nama]);
        else q("UPDATE kategori SET nama = :n WHERE id = :id", ['n' => $nama, 'id' => $id]);
        return null;
    } catch (PDOException $e) {
        if (kode_unik($e)) return 'Nama kategori sudah ada.';
        throw $e;
    }
}
function hapus_kategori(int $id): ?string {
    if (q_val("SELECT 1 FROM barang WHERE kategori_id = :id LIMIT 1", ['id' => $id])) return 'Kategori masih dipakai barang, tidak bisa dihapus.';
    q("DELETE FROM kategori WHERE id = :id", ['id' => $id]);
    return null;
}

/* ---------- profil & akun ---------- */
function get_org(int $id): ?array {
    return ints(q_row("SELECT id, nama, jenis, kontak, status_verifikasi AS verif FROM organisasi WHERE id = :id", ['id' => $id]), 'id');
}
function nama_pengurus(int $uid): string { return (string)q_val("SELECT nama FROM pengguna WHERE id = :id", ['id' => $uid]); }
function ubah_profil(int $org, int $uid, string $nama, string $jenis, string $kontak, string $pengurus): ?string {
    try {
        return transaksi(function () use ($org, $uid, $nama, $jenis, $kontak, $pengurus) {
            q("UPDATE organisasi SET nama = :n, jenis = :j, kontak = :k WHERE id = :id", ['n' => $nama, 'j' => $jenis, 'k' => $kontak, 'id' => $org]);
            q("UPDATE pengguna SET nama = :n WHERE id = :id", ['n' => $pengurus, 'id' => $uid]);
            return null;
        });
    } catch (PDOException $e) {
        if (kode_unik($e)) return 'Nama organisasi sudah dipakai OKI lain.';
        throw $e;
    }
}
function ganti_password(int $uid, string $lama, string $baru): ?string {
    $h = q_val("SELECT password_hash FROM pengguna WHERE id = :id", ['id' => $uid]);
    if (!$h || !password_verify($lama, (string)$h)) return 'Password lama salah.';
    q("UPDATE pengguna SET password_hash = :h WHERE id = :id", ['h' => password_hash($baru, PASSWORD_DEFAULT), 'id' => $uid]);
    return null;
}
function org_punya_peminjaman(int $id): bool {
    return (bool)q_val("SELECT 1 FROM peminjaman WHERE peminjam_org_id = :a OR pemilik_org_id = :b LIMIT 1", ['a' => $id, 'b' => $id]);
}
function hapus_org(int $id): ?string {
    if (org_punya_peminjaman($id)) return 'OKI sudah punya riwayat peminjaman, nonaktifkan akunnya saja.';
    q("DELETE FROM organisasi WHERE id = :id", ['id' => $id]);
    return null;
}

/* ---------- dashboard ---------- */
function dashboard_oki(int $org): array {
    $s = q_row("SELECT
        (SELECT COUNT(*) FROM barang WHERE org_id = :o1) AS total_barang,
        (SELECT COUNT(*) FROM peminjaman WHERE pemilik_org_id = :o2 AND status = 'menunggu') AS masuk,
        (SELECT COUNT(*) FROM peminjaman WHERE (pemilik_org_id = :o3 OR peminjam_org_id = :o4) AND status IN ('disetujui', 'dipinjam')) AS aktif,
        (SELECT COUNT(*) FROM peminjaman WHERE pemilik_org_id = :o5 OR peminjam_org_id = :o6) AS total",
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
        (SELECT COUNT(*) FROM peminjaman WHERE status = 'dipinjam' AND tgl_rencana < CAST(:h AS date)) AS terlambat",
        ['h' => date('Y-m-d')]);
    return array_map('intval', $s);
}
// $org = 0 untuk semua OKI (admin).
function peminjaman_terbaru(int $org = 0, int $limit = 5): array {
    $where = $org > 0 ? ' WHERE p.peminjam_org_id = :o1 OR p.pemilik_org_id = :o2' : '';
    $params = $org > 0 ? ['o1' => $org, 'o2' => $org] : [];
    $rows = q_all(SQL_PINJAM . $where . ' ORDER BY p.id DESC LIMIT ' . (int)$limit, $params);
    return array_map(fn($r) => ints($r, 'id', 'peminjam', 'pemilik'), $rows);
}

/* ---------- laporan ---------- */
// Riwayat peminjaman yang tanggal pinjamnya jatuh pada bulan $bulan (YYYY-MM). $org = 0 untuk semua OKI.
function laporan_peminjaman(string $bulan, int $org, string $jenis, int $kategori): array {
    $awal = $bulan . '-01';
    $akhir = (new DateTime($awal))->modify('+1 month')->format('Y-m-d');
    $w = ["p.tgl_pinjam >= CAST(:awal AS date)", "p.tgl_pinjam < CAST(:akhir AS date)"];
    $p = ['awal' => $awal, 'akhir' => $akhir];
    if ($org > 0) { $w[] = "(p.peminjam_org_id = :o1 OR p.pemilik_org_id = :o2)"; $p['o1'] = $org; $p['o2'] = $org; }
    if ($jenis !== '') { $w[] = "po.jenis = :jenis"; $p['jenis'] = $jenis; }
    if ($kategori > 0) {
        $w[] = "EXISTS (SELECT 1 FROM peminjaman_item pi2 JOIN barang b2 ON b2.id = pi2.barang_id
                        WHERE pi2.peminjaman_id = p.id AND b2.kategori_id = :kat)";
        $p['kat'] = $kategori;
    }
    return q_all("SELECT p.id, po.nama AS peminjam_nama, po.jenis AS jenis_peminjam, wo.nama AS pemilik_nama, p.keperluan,
            p.tgl_pinjam, p.tgl_rencana, p.tgl_aktual, p.status,
            (SELECT string_agg(b.nama || ' ×' || pi.jumlah::text, ', ' ORDER BY pi.id)
               FROM peminjaman_item pi JOIN barang b ON b.id = pi.barang_id WHERE pi.peminjaman_id = p.id) AS item_teks,
            (SELECT string_agg(DISTINCT k.nama, ', ')
               FROM peminjaman_item pi JOIN barang b ON b.id = pi.barang_id JOIN kategori k ON k.id = b.kategori_id
              WHERE pi.peminjaman_id = p.id) AS kategori_teks
        FROM peminjaman p
        JOIN organisasi po ON po.id = p.peminjam_org_id
        JOIN organisasi wo ON wo.id = p.pemilik_org_id
        WHERE " . implode(' AND ', $w) . "
        ORDER BY p.tgl_pinjam, p.id", $p);
}
// Ringkasan inventaris aktif beserta pemantauan hari ini. $org = 0 untuk semua OKI.
function inventaris(int $org, int $kategori, string $jenis): array {
    $w = ["b.status = 'aktif'"];
    $hari = date('Y-m-d');
    $p = ['h1' => $hari, 'dari' => $hari, 'sampai' => $hari];
    if ($org > 0) { $w[] = "b.org_id = :org"; $p['org'] = $org; }
    if ($kategori > 0) { $w[] = "b.kategori_id = :kat"; $p['kat'] = $kategori; }
    if ($jenis !== '') { $w[] = "o.jenis = :jenis"; $p['jenis'] = $jenis; }
    $rows = q_all("SELECT b.id, b.nama, k.nama AS kategori, o.nama AS org_nama, o.jenis, b.jumlah_total AS total, b.kondisi, b.lokasi,
            COALESCE((SELECT SUM(pi.jumlah) FROM peminjaman_item pi JOIN peminjaman p ON p.id = pi.peminjaman_id
                WHERE pi.barang_id = b.id AND p.status = 'dipinjam'), 0) AS keluar,
            COALESCE((SELECT SUM(pi.jumlah) FROM peminjaman_item pi JOIN peminjaman p ON p.id = pi.peminjaman_id
                WHERE pi.barang_id = b.id AND p.status = 'dipinjam' AND p.tgl_rencana < CAST(:h1 AS date)), 0) AS terlambat,
            " . HITUNG_STOK . " AND p.tgl_pinjam <= :sampai AND p.tgl_rencana >= :dari), 0) AS dipakai
        FROM barang b
        JOIN organisasi o ON o.id = b.org_id
        JOIN kategori k ON k.id = b.kategori_id
        WHERE " . implode(' AND ', $w) . "
        ORDER BY o.nama, b.nama", $p);
    $rows = array_map(fn($r) => ints($r, 'id', 'total', 'keluar', 'terlambat', 'dipakai'), $rows);
    foreach ($rows as &$r) {
        $r['tersedia'] = max(0, $r['total'] - $r['dipakai'] - $r['terlambat']);
        $r['pantau'] = status_pantau($r);
    }
    unset($r);
    return $rows;
}

/* ---------- logika peminjaman ---------- */
// Stok tersedia untuk rentang tanggal; peminjaman berstatus disetujui/dipinjam yang beririsan dihitung terpakai.
function stok_tersedia(int $barang, string $dari, string $sampai, int $kecuali = 0): int {
    $v = q_val("SELECT b.jumlah_total - " . HITUNG_STOK . " AND p.id <> :kecuali
            AND p.tgl_pinjam <= :sampai AND p.tgl_rencana >= :dari), 0) FROM barang b WHERE b.id = :id",
        ['kecuali' => $kecuali, 'sampai' => $sampai, 'dari' => $dari, 'id' => $barang]);
    return (int)($v ?? 0);
}
function nama_item(int $pid): string {
    return (string)q_val("SELECT string_agg(b.nama || ' ×' || pi.jumlah::text, ', ' ORDER BY pi.id)
        FROM peminjaman_item pi JOIN barang b ON b.id = pi.barang_id WHERE pi.peminjaman_id = :p", ['p' => $pid]);
}
const SQL_PINJAM = "SELECT p.id, p.peminjam_org_id AS peminjam, p.pemilik_org_id AS pemilik,
        po.nama AS peminjam_nama, wo.nama AS pemilik_nama, p.keperluan, p.catatan,
        p.tgl_pinjam, p.tgl_rencana, p.tgl_aktual, p.status, p.alasan,
        (SELECT string_agg(b.nama || ' ×' || pi.jumlah::text, ', ' ORDER BY pi.id)
           FROM peminjaman_item pi JOIN barang b ON b.id = pi.barang_id WHERE pi.peminjaman_id = p.id) AS item_teks
    FROM peminjaman p
    JOIN organisasi po ON po.id = p.peminjam_org_id
    JOIN organisasi wo ON wo.id = p.pemilik_org_id";

// $peran: 'semua' (admin), 'masuk' (OKI sebagai pemilik), 'keluar' (OKI sebagai peminjam)
function daftar_peminjaman(string $peran, int $org = 0): array {
    $where = ['semua' => '', 'masuk' => ' WHERE p.pemilik_org_id = :o', 'keluar' => ' WHERE p.peminjam_org_id = :o'][$peran];
    $rows = q_all(SQL_PINJAM . $where . ' ORDER BY p.id DESC', $peran === 'semua' ? [] : ['o' => $org]);
    return array_map(fn($r) => ints($r, 'id', 'peminjam', 'pemilik'), $rows);
}
function get_peminjaman(int $id): ?array {
    return ints(q_row(SQL_PINJAM . ' WHERE p.id = :id', ['id' => $id]), 'id', 'peminjam', 'pemilik');
}
function item_peminjaman(int $pid): array {
    return q_all("SELECT b.nama, pi.jumlah, pi.kondisi_keluar AS kk, pi.kondisi_kembali AS kb, pi.catatan_kembali AS cr
        FROM peminjaman_item pi JOIN barang b ON b.id = pi.barang_id WHERE pi.peminjaman_id = :p ORDER BY pi.id", ['p' => $pid]);
}
function riwayat_peminjaman(int $pid): array {
    return q_all("SELECT to_char(r.waktu, 'DD/MM/YYYY HH24:MI') AS waktu, u.nama AS user_nama,
            r.status_lama AS lama, r.status_baru AS baru, r.keterangan AS ket
        FROM riwayat_status r JOIN pengguna u ON u.id = r.pengguna_id
        WHERE r.peminjaman_id = :p ORDER BY r.id", ['p' => $pid]);
}

// Buat pengajuan. Mengembalikan id peminjaman, atau null bila stok habis (dicek ulang dengan kunci baris barang).
function buat_peminjaman(array $user, array $b, int $jumlah, string $dari, string $sampai, string $keperluan, string $catatan): ?int {
    $pid = null;
    transaksi(function () use (&$pid, $user, $b, $jumlah, $dari, $sampai, $keperluan, $catatan) {
        q("SELECT id FROM barang WHERE id = :id FOR UPDATE", ['id' => $b['id']]);
        if ($jumlah > stok_tersedia($b['id'], $dari, $sampai)) return 'stok';
        $pid = (int)q_val("INSERT INTO peminjaman (peminjam_org_id, pemilik_org_id, keperluan, catatan, tgl_pinjam, tgl_rencana)
            VALUES (:pm, :pk, :kp, :ct, :d, :s) RETURNING id",
            ['pm' => $user['org'], 'pk' => $b['org'], 'kp' => $keperluan, 'ct' => $catatan, 'd' => $dari, 's' => $sampai]);
        q("INSERT INTO peminjaman_item (peminjaman_id, barang_id, jumlah) VALUES (:p, :b, :j)", ['p' => $pid, 'b' => $b['id'], 'j' => $jumlah]);
        q("INSERT INTO riwayat_status (peminjaman_id, pengguna_id, status_lama, status_baru, keterangan)
           VALUES (:p, :u, '', 'menunggu', 'Pengajuan dibuat')", ['p' => $pid, 'u' => $user['id']]);
        $peminjam = q_val("SELECT nama FROM organisasi WHERE id = :id", ['id' => $user['org']]);
        notif_org($b['org'], $pid, 'Pengajuan baru', "$peminjam mengajukan peminjaman {$b['nama']} ×$jumlah (#$pid)");
        return null;
    });
    return $pid;
}
function notif_org(int $org, int $pid, string $judul, string $pesan): void {
    q("INSERT INTO notifikasi (pengguna_id, peminjaman_id, judul, pesan)
       SELECT id, CAST(:p AS integer), CAST(:j AS varchar), CAST(:m AS varchar) FROM pengguna WHERE org_id = :o", ['p' => $pid, 'j' => $judul, 'm' => $pesan, 'o' => $org]);
}
// Ubah status secara atomik (hanya jika status saat ini = $dari). Panggil di dalam transaksi().
function ubah_status(int $pid, int $uid, string $dari, string $baru, string $ket = '', ?string $alasan = null): bool {
    $p = q_row("UPDATE peminjaman SET status = :baru, alasan = COALESCE(:alasan, alasan)
        WHERE id = :id AND status = :dari RETURNING peminjam_org_id AS peminjam, pemilik_org_id AS pemilik",
        ['baru' => $baru, 'alasan' => $alasan, 'id' => $pid, 'dari' => $dari]);
    if (!$p) return false;
    q("INSERT INTO riwayat_status (peminjaman_id, pengguna_id, status_lama, status_baru, keterangan)
       VALUES (:p, :u, :l, :b, :k)", ['p' => $pid, 'u' => $uid, 'l' => $dari, 'b' => $baru, 'k' => $ket]);
    $n = nama_item($pid);
    if (in_array($baru, ['disetujui', 'ditolak', 'dipinjam', 'selesai'], true))
        notif_org((int)$p['peminjam'], $pid, 'Status peminjaman', "Peminjaman $n (#$pid): $baru");
    if ($baru === 'dibatalkan')
        notif_org((int)$p['pemilik'], $pid, 'Pengajuan dibatalkan', "Pengajuan $n (#$pid) dibatalkan oleh peminjam");
    return true;
}
// Cek ulang stok saat persetujuan; baris barang dikunci agar dua persetujuan tidak saling menimpa. Panggil di dalam transaksi().
function cek_stok_persetujuan(int $pid): ?string {
    $p = get_peminjaman($pid);
    q("SELECT b.id FROM barang b JOIN peminjaman_item pi ON pi.barang_id = b.id WHERE pi.peminjaman_id = :p ORDER BY b.id FOR UPDATE OF b", ['p' => $pid]);
    foreach (q_all("SELECT pi.barang_id, pi.jumlah, b.nama FROM peminjaman_item pi JOIN barang b ON b.id = pi.barang_id WHERE pi.peminjaman_id = :p", ['p' => $pid]) as $i)
        if ((int)$i['jumlah'] > stok_tersedia((int)$i['barang_id'], $p['tgl_pinjam'], $p['tgl_rencana'], $pid))
            return 'Bentrok jadwal: stok ' . $i['nama'] . ' tidak cukup pada tanggal tersebut.';
    return null;
}
function set_kondisi_keluar(int $pid, string $kondisi): void {
    q("UPDATE peminjaman_item SET kondisi_keluar = :k WHERE peminjaman_id = :p", ['k' => $kondisi, 'p' => $pid]);
}
function set_pengembalian(int $pid, string $kondisi, string $catatan): void {
    q("UPDATE peminjaman_item SET kondisi_kembali = :k, catatan_kembali = :c WHERE peminjaman_id = :p", ['k' => $kondisi, 'c' => $catatan, 'p' => $pid]);
    q("UPDATE peminjaman SET tgl_aktual = :t WHERE id = :p", ['t' => date('Y-m-d'), 'p' => $pid]);
}
// Status tampilan: "dipinjam" yang lewat tanggal rencana dianggap terlambat.
function status_tampil(array $p): string {
    return ($p['status'] === 'dipinjam' && $p['tgl_rencana'] < date('Y-m-d')) ? 'terlambat' : $p['status'];
}
function badge(string $s): string { return '<span class="badge st-' . e(str_replace(' ', '-', $s)) . '">' . e($s) . '</span>'; }

/* ---------- notifikasi ---------- */
function jumlah_notif_baru(int $uid): int {
    return (int)q_val("SELECT COUNT(*) FROM notifikasi WHERE pengguna_id = :u AND NOT sudah_baca", ['u' => $uid]);
}
function daftar_notifikasi(int $uid): array {
    return q_all("SELECT id, peminjaman_id AS pid, judul, pesan, sudah_baca AS baca, to_char(waktu, 'DD/MM/YYYY HH24:MI') AS waktu
        FROM notifikasi WHERE pengguna_id = :u ORDER BY id DESC", ['u' => $uid]);
}
function tandai_notif_dibaca(int $uid): void { q("UPDATE notifikasi SET sudah_baca = TRUE WHERE pengguna_id = :u AND NOT sudah_baca", ['u' => $uid]); }

/* ---------- reset data demo ---------- */
function reset_data_demo(): void { db()->exec(file_get_contents(dirname(__DIR__) . '/database/seed.sql')); }
