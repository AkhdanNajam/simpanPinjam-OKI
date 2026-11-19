<?php
$page_title = "Tambah OKI";
$wajib_login = ['admin'];
include __DIR__ . '/../../includes/header.php';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<section>
    <h2>Tambah Data &amp; Akun Login OKI</h2>
    <p><a href="list.php">&larr; Kembali ke Verifikasi OKI</a></p>

    <?php if ($flash): ?>
        <div class="catatan" style="margin: 14px 0; padding: 12px 16px; border-radius: 6px; border: 1px solid #cbd5e1; background: <?php echo ($flash['type'] ?? '') === 'success' ? '#ecfdf5' : '#fef2f2'; ?>;">
            <?php echo e($flash['pesan'] ?? ''); ?>
        </div>
    <?php endif; ?>

    <form method="post" action="proses_tambah_oki.php" style="margin-top: 16px;">
        <?php if (function_exists('csrf_field')) echo csrf_field(); ?>

        <fieldset style="border: 1px solid #d1d5db; border-radius: 8px; padding: 18px; margin-bottom: 18px;">
            <legend style="font-weight: 600; padding: 0 8px;">Data Organisasi OKI</legend>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
                <div>
                    <label for="nama" style="display: block; font-weight: 600; margin-bottom: 5px;">Nama OKI</label>
                    <input type="text" id="nama" name="nama" placeholder="Contoh: BEM / HMTI / UKM PP" required style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <div>
                    <label for="jenis" style="display: block; font-weight: 600; margin-bottom: 5px;">Jenis</label>
                    <select id="jenis" name="jenis" required style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <option value="">-- Pilih Jenis --</option>
                        <option value="LT">LT</option>
                        <option value="HMJ">HMJ</option>
                        <option value="UKM">UKM</option>
                    </select>
                </div>

                <div>
                    <label for="kontak" style="display: block; font-weight: 600; margin-bottom: 5px;">Kontak</label>
                    <input type="text" id="kontak" name="kontak" placeholder="Contoh: 081200000001" required style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <div>
                    <label for="status_verifikasi" style="display: block; font-weight: 600; margin-bottom: 5px;">Status Verifikasi</label>
                    <select id="status_verifikasi" name="status_verifikasi" style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <option value="terverifikasi" selected>terverifikasi</option>
                        <option value="menunggu">menunggu</option>
                    </select>
                </div>
            </div>
        </fieldset>

        <fieldset style="border: 1px solid #d1d5db; border-radius: 8px; padding: 18px; margin-bottom: 18px;">
            <legend style="font-weight: 600; padding: 0 8px;">Data Akun Login OKI</legend>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
                <div>
                    <label for="email" style="display: block; font-weight: 600; margin-bottom: 5px;">Email Login</label>
                    <input type="email" id="email" name="email" placeholder="Contoh: bem@okipolinema.ac.id" required style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <div>
                    <label for="password" style="display: block; font-weight: 600; margin-bottom: 5px;">Password Awal</label>
                    <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" minlength="6" required style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <div>
                    <label for="status_akun" style="display: block; font-weight: 600; margin-bottom: 5px;">Status Akun</label>
                    <select id="status_akun" name="status_akun" style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <option value="aktif" selected>aktif</option>
                        <option value="nonaktif">nonaktif</option>
                    </select>
                </div>
            </div>
        </fieldset>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn">Simpan OKI &amp; Akun</button>
            <a href="list.php" class="btn">Batal</a>
        </div>
    </form>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>