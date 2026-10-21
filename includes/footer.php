    </main>
    <footer>
        <p>&copy; 2026 SIPINJAM OKI Polinema &mdash; PBL Kelompok 4 SIB 2C</p>
        <?php if (!empty($user) && $user['role'] === 'admin' && demo_aktif()): ?>
        <form method="post" action="<?php echo $base; ?>reset.php" class="inline" data-konfirmasi="Reset semua data ke data demo awal?"><?php echo csrf_field(); ?><button type="submit">Reset data demo</button></form>
        <?php endif; ?>
    </footer>
    <script src="<?php echo $base; ?>assets/js/app.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/app.js'); ?>"></script>
</body>
</html>
