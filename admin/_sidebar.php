<?php
// admin/_sidebar.php
$cur = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
  <div class="sidebar-logo">
    <h2>SIBANSOS</h2>
    <span>Panel Admin</span>
  </div>

  <div class="user-info">
    <div class="user-avatar"><?= strtoupper(substr($_SESSION['nama'],0,1)) ?></div>
    <div>
      <div class="user-name"><?= htmlspecialchars($_SESSION['nama']) ?></div>
      <div class="user-role">🛡️ Admin</div>
    </div>
  </div>

<nav>
  <div class="nav-section">Menu Utama</div>

  <a href="index.php" class="<?= $cur==='index.php'?'active':'' ?>">
    <span class="nav-icon">📊</span> Dashboard
  </a>

  <a href="jenis_bantuan.php" class="<?= $cur==='jenis_bantuan.php'?'active':'' ?>">
    <span class="nav-icon">📂</span> Jenis Bantuan
  </a>

  <a href="data_bantuan.php" class="<?= $cur==='data_bantuan.php'?'active':'' ?>">
    <span class="nav-icon">🎁</span> Data Bantuan
  </a>

  <a href="data_masyarakat.php" class="<?= $cur==='data_masyarakat.php'?'active':'' ?>">
    <span class="nav-icon">👥</span> Data Masyarakat
  </a>

  <a href="verifikasi_masyarakat.php" class="<?= $cur==='verifikasi_masyarakat.php'?'active':'' ?>">
    <span class="nav-icon">✔️</span> Verifikasi Masyarakat
  </a>

  <div class="nav-section">Pengajuan</div>

  <a href="pengajuan.php" class="<?= $cur==='pengajuan.php'?'active':'' ?>">
    <span class="nav-icon">📋</span> Pengajuan
    <?php
    include_once '../config/koneksi.php';
    $n = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM pengajuan WHERE status='pending'"));
    if($n['c'] > 0): ?>
      <span style="margin-left:auto;background:#dc2626;color:white;border-radius:20px;padding:2px 8px;font-size:11px;font-weight:700">
        <?= $n['c'] ?>
      </span>
    <?php endif; ?>
  </a>

  <div class="nav-section">Lainnya</div>

  <a href="laporan.php" class="<?= $cur==='laporan.php'?'active':'' ?>">
    <span class="nav-icon">📈</span> Laporan
  </a>

  <a href="pengumuman.php" class="<?= $cur==='pengumuman.php'?'active':'' ?>">
    <span class="nav-icon">📢</span> Pengumuman
  </a>
</nav>

  <div class="sidebar-footer">
    <a href="../logout.php" class="btn-logout">
      <span>🚪</span> Logout
    </a>
  </div>
</div>
