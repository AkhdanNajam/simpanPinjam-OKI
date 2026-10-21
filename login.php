<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/data.php';

// Sudah login -> langsung ke beranda
if (user_now()) redirect('index.php');

$flash = get_flash();
$old = $_SESSION['old'] ?? []; unset($_SESSION['old']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1">
 <title>Login | SIPINJAM OKI</title>
 <link rel="preconnect" href="https://fonts.googleapis.com">
 <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
 <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap">
 <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="halaman-login">
 <header class="login-brand">
  <img src="assets/img/logo.png" alt="Logo Politeknik Negeri Malang" width="46" height="46">
  <p>Sistem Peminjaman Barang<strong>OKI POLINEMA</strong></p>
 </header>

 <main class="login-main">
  <div class="login-card">
   <h1>Login</h1>

   <?php if ($flash): ?>
   <p class="login-flash login-flash-<?php echo e($flash['type']); ?>" role="alert"><?php echo e($flash['pesan']); ?></p>
   <?php endif; ?>

   <form method="post" action="proses_login.php" autocomplete="on">
    <?php echo csrf_field(); ?>

    <div class="field">
     <label for="email" class="sr-only">Email</label>
     <input type="email" id="email" name="email" placeholder="Email" value="<?php echo e($old['email'] ?? ''); ?>" autocomplete="username" required>
     <svg class="ikon" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4.5 21c.6-4 3.5-6 7.5-6s6.9 2 7.5 6"/></svg>
    </div>

    <div class="field">
     <label for="password" class="sr-only">Password</label>
     <input type="password" id="password" name="password" placeholder="Password" autocomplete="current-password" required>
     <button type="button" class="ikon ikon-tombol" id="toggle-password" aria-label="Tampilkan password" aria-pressed="false">
      <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><path d="M7 10V8a5 5 0 0 1 10 0v2h.5A2.5 2.5 0 0 1 20 12.5v7a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 19.5v-7A2.5 2.5 0 0 1 6.5 10H7Zm2 0h6V8a3 3 0 0 0-6 0v2Zm3 4.5a1.8 1.8 0 1 0 0 3.6 1.8 1.8 0 0 0 0-3.6Z" fill-rule="evenodd"/></svg>
     </button>
    </div>

    <div class="baris-opsi">
     <label class="cek"><input type="checkbox" name="remember" value="1" checked><span>Remember me</span></label>
     <a href="#reset-info">Reset Password</a>
    </div>
    <p class="reset-info" id="reset-info">Lupa password? Hubungi Admin SIPINJAM OKI untuk mengatur ulang password akun Anda.</p>

    <button type="submit" class="btn-login">Login</button>
   </form>
  </div>

  <?php if (demo_aktif()): ?>
  <details class="login-demo">
   <summary>Akun demo</summary>
   <p>Password: <code>password</code><br>
   <code>admin@okipolinema.ac.id</code>, <code>bem@okipolinema.ac.id</code>, <code>hmjti@okipolinema.ac.id</code>, <code>ukmmusik@okipolinema.ac.id</code>, <code>hmjte@okipolinema.ac.id</code>, <code>ukmfoto@okipolinema.ac.id</code>, <code>ukmpa@okipolinema.ac.id</code> (UKM PA belum terverifikasi).</p>
  </details>
  <?php endif; ?>
 </main>

 <script>
  (function () {
   var btn = document.getElementById('toggle-password'), inp = document.getElementById('password');
   if (!btn || !inp) return;
   btn.addEventListener('click', function () {
    var tampil = inp.type === 'password';
    inp.type = tampil ? 'text' : 'password';
    btn.setAttribute('aria-pressed', tampil ? 'true' : 'false');
    btn.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
   });
  })();
 </script>
</body>
</html>