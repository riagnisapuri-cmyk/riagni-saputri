<?php
$cur = basename($_SERVER['PHP_SELF']);

$inisial_sidebar = strtoupper(substr($_SESSION['nama'] ?? 'P', 0, 1));
$foto_sidebar    = null;

// Ambil foto — cari PK yang benar dulu
if (isset($conn)) {
    $uid_s = intval($_SESSION['user_id'] ?? 0);

    // Cek kolom apa yang ada di tabel users
    $pk_sidebar = 'id'; // default
    $desc = mysqli_query($conn, "DESCRIBE users");
    if ($desc) {
        $cols_s = [];
        while ($r = mysqli_fetch_assoc($desc)) $cols_s[] = $r['Field'];
        foreach (['id','user_id','id_user'] as $pk) {
            if (in_array($pk, $cols_s)) { $pk_sidebar = $pk; break; }
        }
    }

    $qs = mysqli_query($conn, "SELECT foto FROM users WHERE `$pk_sidebar`=$uid_s LIMIT 1");
    if ($qs) {
        $rs = mysqli_fetch_assoc($qs);
        if (is_array($rs) && !empty($rs['foto'])) {
            $f = '../uploads/foto_profil/' . $rs['foto'];
            if (file_exists($f)) $foto_sidebar = $f;
        }
    }
}

// Badge pending verifikasi
$pending_n = 0;
if (isset($conn)) {
    $qp = mysqli_query($conn, "SELECT COUNT(*) c FROM pengajuan WHERE status='pending'");
    if ($qp) $pending_n = intval((mysqli_fetch_assoc($qp))['c'] ?? 0);
}
?>
<div class="sidebar">

    <div class="sidebar-logo">
        <h2>SIBANSOS</h2>
        <span>Panel Petugas</span>
    </div>

    <div class="user-info">
        <div class="user-avatar" style="overflow:hidden;padding:0;background:#22c55e">
            <?php if ($foto_sidebar): ?>
                <img src="<?= htmlspecialchars($foto_sidebar) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
            <?php else: ?>
                <span style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;font-weight:700;color:white">
                    <?= $inisial_sidebar ?>
                </span>
            <?php endif; ?>
        </div>
        <div>
            <div class="user-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Petugas') ?></div>
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

        <a href="profil.php"
           style="display:flex;align-items:center;gap:10px;
                  color:<?= $cur === 'profil.php' ? '#bbf7d0' : 'rgba(255,255,255,.8)' ?>;
                  text-decoration:none;padding:11px 12px;border-radius:10px;
                  font-size:14px;font-weight:500;margin-bottom:8px;
                  background:<?= $cur === 'profil.php' ? 'rgba(34,197,94,.25)' : 'transparent' ?>">
            <?php if ($foto_sidebar): ?>
                <img src="<?= htmlspecialchars($foto_sidebar) ?>"
                     style="width:28px;height:28px;border-radius:50%;object-fit:cover;border:2px solid #22c55e;flex-shrink:0">
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