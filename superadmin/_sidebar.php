<?php
// superadmin/_sidebar.php — include di setiap halaman superadmin
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="sa-sidebar">
  <div class="sa-logo">
    <div class="sa-logo-icon">⭐</div>
    <div>
      <div class="sa-logo-name">SIBANSOS</div>
      <div class="sa-logo-role">Super Admin</div>
    </div>
  </div>

  <div class="sa-user-card">
    <div class="sa-user-avatar"><?= strtoupper(substr($_SESSION['nama'], 0, 1)) ?></div>
    <div>
      <div class="sa-user-name"><?= htmlspecialchars($_SESSION['nama']) ?></div>
      <div class="sa-user-badge">⭐ Super Admin</div>
    </div>
  </div>

  <nav class="sa-nav">
    <a href="index.php"      class="<?= $current==='index.php'       ? 'active':'' ?>">
      <span class="nav-icon">📊</span> Dashboard
    </a>
    <a href="kelola_akun.php" class="<?= $current==='kelola_akun.php' ? 'active':'' ?>">
      <span class="nav-icon">👥</span> Kelola Akun
    </a>
    <a href="tambah_akun.php" class="<?= $current==='tambah_akun.php' ? 'active':'' ?>">
      <span class="nav-icon">➕</span> Tambah Akun
    </a>
    <a href="log_aktivitas.php" class="<?= $current==='log_aktivitas.php' ? 'active':'' ?>">
      <span class="nav-icon">📋</span> Log Aktivitas
    </a>
    <div class="nav-divider"></div>
    <a href="../index.php">
      <span class="nav-icon">🏠</span> Kembali ke Sistem
    </a>
  </nav>

  <a href="../logout.php" class="sa-logout">
    <span>🚪</span> Logout
  </a>
</aside>
