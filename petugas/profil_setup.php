<?php
require_once 'auth_guard.php';
include_once '../config/koneksi.php';

$cur = basename($_SERVER['PHP_SELF']);
$uid = $_SESSION['user_id'];

$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id='$uid'"));

// Foto profil
$foto_src = (!empty($user['foto']) && file_exists('../uploads/foto_profil/' . $user['foto']))
    ? '../uploads/foto_profil/' . htmlspecialchars($user['foto'])
    : null;

$inisial = strtoupper(substr($_SESSION['nama'] ?? 'P', 0, 1));

// Badge pending
$pending_n = 0;
$q = mysqli_query($conn, "SELECT COUNT(*) c FROM pengajuan WHERE status='pending'");
if ($q) $pending_n = mysqli_fetch_assoc($q)['c'] ?? 0;
?>
<div class="sidebar">

    <div class="sidebar-logo">
        <h2>SIBANSOS</h2>
        <span>Panel Petugas</span>
    </div>

    <div class="user-info">
        <div class="user-avatar" style="overflow:hidden;padding:0">
            <?php if ($foto_src): ?>
                <img src="<?= $foto_src ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
            <?php else: ?>
                <?= $inisial ?>
            <?php endif; ?>
        </div>
        <div>
            <div class="user-name"><?= htmlspecialchars($_SESSION['nama']) ?></div>
            <div class="user-role">👮 Petugas</div>
        </div>
    </div>

    <nav>

        <a href="index.php" class="<?= $cur === 'index.php' ? 'active' : '' ?>">
            📊 Dashboard
        </a>

        <a href="data_masyarakat.php" class="<?= $cur === 'data_masyarakat.php' ? 'active' : '' ?>">
            👥 Data Masyarakat
        </a>

        <a href="verifikasi.php" class="<?= $cur === 'verifikasi.php' ? 'active' : '' ?>"
           style="display:flex;align-items:center;justify-content:space-between">
            <span>📄 Verifikasi</span>
            <?php if ($pending_n > 0): ?>
                <span style="background:#f59e0b;color:white;border-radius:20px;padding:2px 8px;font-size:11px;font-weight:700">
                    <?= $pending_n ?>
                </span>
            <?php endif; ?>
        </a>

        <a href="survey.php" class="<?= $cur === 'survey.php' ? 'active' : '' ?>">
            🏠 Survey Lapangan
        </a>

        <a href="penyaluran.php" class="<?= $cur === 'penyaluran.php' ? 'active' : '' ?>">
            🎁 Penyaluran Bantuan
        </a>

    </nav>

    <div class="sidebar-footer">

        <a href="profil.php" class="<?= $cur === 'profil.php' ? 'active' : '' ?>"
           style="display:flex;align-items:center;gap:10px;color:rgba(255,255,255,.75);
                  text-decoration:none;padding:11px 12px;border-radius:10px;font-size:14px;
                  font-weight:500;margin-bottom:8px;transition:background .2s;
                  <?= $cur === 'profil.php' ? 'background:rgba(34,197,94,.2);color:#bbf7d0' : '' ?>">
            <?php if ($foto_src): ?>
                <img src="<?= $foto_src ?>" style="width:28px;height:28px;border-radius:50%;object-fit:cover;border:2px solid #22c55e;flex-shrink:0">
            <?php else: ?>
                <span style="font-size:20px">👤</span>
            <?php endif; ?>
            Profil Saya
        </a>

        <a href="../logout.php" class="btn-logout">
            🚪 Logout
        </a>
    </div>

</div>