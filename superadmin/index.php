<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

// Statistik
$total_masyarakat     = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM masyarakat"))['c'];
$total_superadmin= mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM users WHERE role='superadmin'"))['c'];
$total_admin     = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM users WHERE role='admin'"))['c'];
$total_petugas   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM users WHERE role='petugas'"))['c'];
$total_masyarakat     = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM users WHERE role='masyarakat'"))['c'];
$total_login_hari = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM log_aktivitas WHERE aksi='login' AND DATE(waktu)=CURDATE()"))['c'];

// Statistik Sistem
$total_masyarakat = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM masyarakat"))['c'];

$total_pengajuan = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM pengajuan"))['c'];

$total_diterima = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM pengajuan WHERE status='diterima'"))['c'];

$total_ditolak = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM pengajuan WHERE status='ditolak'"))['c'];

$total_pending = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM pengajuan WHERE status='pending'"))['c'];

$total_bantuan = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM jenis_bantuan"))['c'];

$total_penyaluran = mysqli_fetch_assoc( mysqli_query($conn,"SELECT COUNT(*) c FROM penyaluran_bantuan"))['c'];

// Pengumuman aktif
$pengumuman = mysqli_query($conn,"
SELECT *
FROM pengumuman
WHERE status='aktif'
ORDER BY tanggal DESC
LIMIT 3
");

// 10 aktivitas terbaru
$log_terbaru = mysqli_query($conn,"
  SELECT * FROM log_aktivitas
  ORDER BY waktu DESC
  LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Super Admin — Dashboard</title>
<link rel="stylesheet" href="sa_style.css">
</head>
<body>

<?php include '_sidebar.php'; ?>

<main class="sa-main">

  <div class="sa-topbar">
    <div>
      <h1 class="sa-page-title">Dashboard Super Admin</h1>
      <p class="sa-page-sub">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?> · <?= date('l, d F Y') ?></p>
    </div>
    <div class="sa-breadcrumb">
      <a href="index.php">Dashboard</a>
    </div>
  </div>

  <div class="sa-card">
    <div class="sa-card-title">
        ℹ️ Informasi Sistem
    </div>

    <table class="sa-table">
        <tr>
            <td>Versi PHP</td>
            <td><?= phpversion(); ?></td>
        </tr>

        <tr>
            <td>Server</td>
            <td><?= $_SERVER['SERVER_SOFTWARE']; ?></td>
        </tr>

        <tr>
            <td>Tanggal</td>
            <td><?= date('d F Y H:i'); ?></td>
        </tr>

        <tr>
            <td>Login Hari Ini</td>
            <td><?= $total_login_hari; ?></td>
        </tr>
    </table>
</div>

  <!-- Stat Cards -->
  <div class="sa-stats">
    <div class="sa-stat-card gold">
      <div class="sa-stat-icon">👥</div>
      <div class="sa-stat-num"><?= $total_masyarakat ?></div>
      <div class="sa-stat-label">Total Akun</div>
    </div>
    <div class="sa-stat-card blue">
      <div class="sa-stat-icon">⭐</div>
      <div class="sa-stat-num"><?= $total_superadmin ?></div>
      <div class="sa-stat-label">Super Admin</div>
    </div>
    <div class="sa-stat-card purple">
      <div class="sa-stat-icon">🛡️</div>
      <div class="sa-stat-num"><?= $total_admin ?></div>
      <div class="sa-stat-label">Admin</div>
    </div>
    <div class="sa-stat-card green">
      <div class="sa-stat-icon">📋</div>
      <div class="sa-stat-num"><?= $total_petugas ?></div>
      <div class="sa-stat-label">Petugas</div>
    </div>
    <div class="sa-stat-card red">
      <div class="sa-stat-icon">🔑</div>
      <div class="sa-stat-num"><?= $total_login_hari ?></div>
      <div class="sa-stat-label">Login Hari Ini</div>
    </div>
<div class="sa-stat-card cyan">
    <div class="sa-stat-icon">📂</div>
    <div class="sa-stat-num"><?= $total_bantuan ?></div>
    <div class="sa-stat-label">Jenis Bantuan</div>
</div>

<div class="sa-stat-card orange">
    <div class="sa-stat-icon">📝</div>
    <div class="sa-stat-num"><?= $total_pengajuan ?></div>
    <div class="sa-stat-label">Pengajuan</div>
</div>

<div class="sa-stat-card green">
    <div class="sa-stat-icon">✅</div>
    <div class="sa-stat-num"><?= $total_diterima ?></div>
    <div class="sa-stat-label">Diterima</div>
</div>

<div class="sa-stat-card red">
    <div class="sa-stat-icon">❌</div>
    <div class="sa-stat-num"><?= $total_ditolak ?></div>
    <div class="sa-stat-label">Ditolak</div>
</div>

<div class="sa-stat-card purple">
    <div class="sa-stat-icon">🚚</div>
    <div class="sa-stat-num"><?= $total_penyaluran ?></div>
    <div class="sa-stat-label">Penyaluran</div>
</div>
</div>
<div class="sa-card">
    <div class="sa-card-title">⚡ Menu Cepat</div>

    <div class="quick-menu">

        <a href="tambah_akun.php" class="quick-item">
            👤
            <span>Tambah Akun</span>
        </a>

        <a href="kelola_akun.php" class="quick-item">
            👥
            <span>Kelola Akun</span>
        </a>

        <a href="log_aktivitas.php" class="quick-item">
            📋
            <span>Log Aktivitas</span>
        </a>

        <a href="../admin/index.php" class="quick-item">
            🛠
            <span>Panel Admin</span>
        </a>

    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-top:20px;">

    <!-- Aktivitas -->
    <div class="sa-card">

        <div class="sa-card-title">
            📋 Aktivitas Login Terbaru
        </div>

        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Aksi</th>
                        <th>Waktu</th>
                    </tr>
                </thead>

                <tbody>

                <?php
                mysqli_data_seek($log_terbaru,0);
                while($l=mysqli_fetch_assoc($log_terbaru)):
                ?>

                <tr>
                    <td><?= htmlspecialchars($l['nama']) ?></td>
                    <td><?= ucfirst($l['aksi']) ?></td>
                    <td><?= date('d/m/Y H:i',strtotime($l['waktu'])) ?></td>
                </tr>

                <?php endwhile; ?>

                </tbody>
            </table>
        </div>

    </div>

    <!-- Pengumuman -->
    <div class="sa-card">

        <div class="sa-card-title">
            📢 Pengumuman
        </div>

        <?php while($p=mysqli_fetch_assoc($pengumuman)): ?>

          <div style="padding:16px;margin-bottom:15px;background:#111827; border:1px solid #2d3748;border-radius:12px; ">

                <b><?= htmlspecialchars($p['judul']) ?></b>

        <p style="margin-top:8px;font-size:13px;color:#d1d5db;line-height:1.6">
                    <?= substr(strip_tags($p['isi']),0,100) ?>...

                </p>

                <small>
                    <?= date('d M Y',strtotime($p['tanggal'])) ?>
                    <small style="color:#9ca3af">
                </small>
<b style="font-size:15px;color:#f8fafc;">
  <p style="
margin-top:10px;
color:#cbd5e1;
line-height:1.7;
font-size:13px;
">
<small style="color:#94a3b8;">
            </div>

        <?php endwhile; ?>

    </div>

</div>

</body>
</html>
