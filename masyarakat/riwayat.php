<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

$uid  = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM users WHERE id='$uid'"));
$nik  = $user['nik'] ?? '';

// Bantuan yang sudah diterima (dari penerima_bantuan)
$riwayat = $nik ? mysqli_query($conn,"
    SELECT pb.*, bs.nama_bantuan, bs.jenis_bantuan, bs.jumlah_bantuan, bs.tanggal_bantuan,
           pt.nama_petugas
    FROM penerima_bantuan pb
    LEFT JOIN bantuan_sosial bs ON pb.id_bansos=bs.id_bansos
    LEFT JOIN petugas pt ON pb.id_petugas=pt.id_petugas
    WHERE pb.nik='$nik' AND pb.status_verifikasi='diterima'
    ORDER BY pb.tanggal_verifikasi DESC
") : null;

$total_riwayat = $riwayat ? mysqli_num_rows($riwayat) : 0;

// Total nilai bantuan diterima
$total_nilai = $nik ? mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT SUM(bs.jumlah_bantuan) as total
    FROM penerima_bantuan pb
    LEFT JOIN bantuan_sosial bs ON pb.id_bansos=bs.id_bansos
    WHERE pb.nik='$nik' AND pb.status_verifikasi='diterima'
"))['total'] : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>SIBANSOS — Riwayat Bantuan</title>
<link rel="stylesheet" href="ms_style.css">
</head>
<body>

<?php include '_sidebar.php'; ?>

<div class="main">

  <div class="page-header">
    <h1>📋 Riwayat Bantuan</h1>
    <p>Daftar bantuan sosial yang telah kamu terima</p>
  </div>

  <?php if (empty($nik)): ?>
  <div class="alert alert-warning">
    ⚠️ NIK belum diisi. <a href="profil.php" style="font-weight:700">Lengkapi profil</a> untuk melihat riwayat bantuan.
  </div>
  <?php else: ?>

  <!-- Ringkasan -->
  <div class="stats-grid" style="grid-template-columns:1fr 1fr;max-width:500px;margin-bottom:24px">
    <div class="stat-card green">
      <div class="stat-icon">🎁</div>
      <div class="stat-num"><?= $total_riwayat ?></div>
      <div class="stat-label">Bantuan Diterima</div>
    </div>
    <div class="stat-card blue">
      <div class="stat-icon">💰</div>
      <div class="stat-num" style="font-size:20px">Rp <?= number_format($total_nilai??0,0,',','.') ?></div>
      <div class="stat-label">Total Nilai Bantuan</div>
    </div>
  </div>

  <div class="card">
    <div class="card-title">🎁 Daftar Bantuan Diterima</div>

    <?php if ($total_riwayat == 0): ?>
    <div class="empty-state">
      <div class="icon">📭</div>
      <p>Belum ada bantuan yang diterima.</p>
      <a href="ajukan.php" class="btn btn-primary btn-sm" style="margin-top:14px;display:inline-flex">Ajukan Bantuan</a>
    </div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <tr>
          <th>#</th>
          <th>Program Bantuan</th>
          <th>Jenis</th>
          <th>Nilai</th>
          <th>Petugas</th>
          <th>Tanggal Diterima</th>
        </tr>
        <?php $no=1; while($r=mysqli_fetch_assoc($riwayat)): ?>
        <tr>
          <td style="color:#94a3b8"><?= $no++ ?></td>
          <td>
            <b><?= htmlspecialchars($r['nama_bantuan']??'-') ?></b>
          </td>
          <td>
            <span style="background:#eff6ff;color:#1d4ed8;padding:3px 9px;border-radius:6px;font-size:12px;font-weight:600">
              <?= $r['jenis_bantuan']?:'-' ?>
            </span>
          </td>
          <td style="color:#166534;font-weight:700">
            Rp <?= number_format($r['jumlah_bantuan'],0,',','.') ?>
          </td>
          <td style="font-size:13px;color:#64748b">
            <?= htmlspecialchars($r['nama_petugas']??'-') ?>
          </td>
          <td style="font-size:13px;color:#64748b">
            <?= $r['tanggal_verifikasi'] && $r['tanggal_verifikasi']!=='0000-00-00'
                ? date('d/m/Y',strtotime($r['tanggal_verifikasi']))
                : '—' ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <?php endif; ?>

</div>
</body>
</html>
