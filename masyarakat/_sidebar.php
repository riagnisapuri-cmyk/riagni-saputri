<?php
// masyarakat/_sidebar.php
$cur = basename($_SERVER['PHP_SELF']);

include_once '../config/koneksi.php';
$_sid_uid  = $_SESSION['user_id'];
$_sid_user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nik, foto FROM users WHERE id='$_sid_uid'"));
$_sid_foto = !empty($_sid_user['foto'])
    ? "../uploads/profil/" . htmlspecialchars($_sid_user['foto'])
    : null;
$_sid_initial = strtoupper(substr($_SESSION['nama'] ?? 'U', 0, 1));

$_sid_badge = 0;
if (!empty($_sid_user['nik'])) {
    $nik_esc = mysqli_real_escape_string($conn, $_sid_user['nik']);
    $nb = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) c FROM pengajuan WHERE nik='$nik_esc' AND status='pending'"));
    $_sid_badge = (int)($nb['c'] ?? 0);
}
?>
<div class="sidebar">

  <div class="sidebar-header">
    <div class="sidebar-logo">SI<span>BANSOS</span></div>
    <div class="sidebar-tagline">Portal Masyarakat</div>
  </div>

  <div class="user-card">
    <?php if ($_sid_foto): ?>
      <img src="<?= $_sid_foto ?>" alt="Foto" style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,.25);flex-shrink:0;">
    <?php else: ?>
      <div class="user-avatar"><?= $_sid_initial ?></div>
    <?php endif; ?>
    <div>
      <div class="user-name"><?= htmlspecialchars($_SESSION['nama']) ?></div>
      <div class="user-role">👤 Masyarakat</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-label">Menu</div>
    <a href="index.php" class="<?= $cur==='index.php' ? 'active' : '' ?>"><span class="nav-icon">🏠</span> Dashboard</a>
    <a href="ajukan.php" class="<?= $cur==='ajukan.php' ? 'active' : '' ?>"><span class="nav-icon">📝</span> Ajukan Bantuan</a>
    <a href="status.php" class="<?= $cur==='status.php' ? 'active' : '' ?>">
      <span class="nav-icon">🔍</span> Status Pengajuan
      <?php if ($_sid_badge > 0): ?><span style="margin-left:auto;background:#f59e0b;color:#fff;border-radius:20px;padding:2px 8px;font-size:11px;font-weight:700"><?= $_sid_badge ?></span><?php endif; ?>
    </a>
    <a href="riwayat.php" class="<?= $cur==='riwayat.php' ? 'active' : '' ?>"><span class="nav-icon">📋</span> Riwayat Bantuan</a>
    <a href="pengumuman.php" class="<?= $cur==='pengumuman.php' ? 'active' : '' ?>"><span class="nav-icon">📢</span> Pengumuman</a>
    <a href="faq.php" class="<?= $cur==='faq.php' ? 'active' : '' ?>"><span class="nav-icon">❓</span> FAQ</a>
    <div class="nav-label">Akun</div>
    <a href="profil.php" class="<?= $cur==='profil.php' ? 'active' : '' ?>"><span class="nav-icon">👤</span> Profil Saya</a>
  </nav>

  <div class="sidebar-footer">
    <a href="../logout.php" class="btn-logout"><span>🚪</span> Logout</a>
  </div>

</div>
<style>
.sidebar { display:flex; flex-direction:column; height:100vh; overflow:hidden; }
.sidebar-header { flex-shrink:0; }
.user-card { flex-shrink:0; }
.sidebar-nav { flex:1; overflow-y:auto; overflow-x:hidden; padding-bottom:8px; }
.sidebar-nav::-webkit-scrollbar { width:4px; }
.sidebar-nav::-webkit-scrollbar-track { background:transparent; }
.sidebar-nav::-webkit-scrollbar-thumb { background:rgba(255,255,255,.15); border-radius:99px; }
.sidebar-footer { flex-shrink:0; padding:12px 16px; border-top:1px solid rgba(255,255,255,.08); }
</style>