<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

$total_masyarakat = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM masyarakat"))['c'];
$total_bantuan    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bantuan_sosial"))['c'];
$total_pengajuan  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM pengajuan"))['c'];
$total_pending    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM pengajuan WHERE status='pending'"))['c'];
$total_diterima   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM pengajuan WHERE status='diterima'"))['c'];
$total_ditolak    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM pengajuan WHERE status='ditolak'"))['c'];

// Pengajuan terbaru
$pengajuan_terbaru = mysqli_query($conn,"
  SELECT p.*, m.nama, bs.nama_bantuan
  FROM pengajuan p
  LEFT JOIN masyarakat m ON p.nik = m.nik
  LEFT JOIN bantuan_sosial bs ON p.id_bansos = bs.id_bansos
  ORDER BY p.tanggal_pengajuan DESC
  LIMIT 8
");

// Pengumuman aktif terbaru
$pengumuman = mysqli_query($conn,"
  SELECT * FROM pengumuman WHERE status='aktif'
  ORDER BY tanggal DESC LIMIT 3
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Admin — Dashboard</title>
<link rel="stylesheet" href="admin_style.css">
</head>
<body>

<?php include '_sidebar.php'; ?>

<div class="main">

  <div class="page-header">
    <div>
      <h1>Dashboard Admin</h1>
      <p>Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?> · <?= date('d F Y') ?></p>
    </div>
    <div class="breadcrumb">Dashboard</div>
  </div>

  <!-- Statistik -->
  <div class="stats-grid">
    <div class="stat-card navy">
      <div class="stat-icon">👥</div>
      <div class="stat-num"><?= $total_masyarakat ?></div>
      <div class="stat-label">Total Masyarakat</div>
    </div>
    <div class="stat-card green">
      <div class="stat-icon">🎁</div>
      <div class="stat-num"><?= $total_bantuan ?></div>
      <div class="stat-label">Program Bantuan</div>
    </div>
    <div class="stat-card yellow">
      <div class="stat-icon">⏳</div>
      <div class="stat-num"><?= $total_pending ?></div>
      <div class="stat-label">Pengajuan Pending</div>
    </div>
    <div class="stat-card green">
      <div class="stat-icon">✅</div>
      <div class="stat-num"><?= $total_diterima ?></div>
      <div class="stat-label">Pengajuan Diterima</div>
    </div>
    <div class="stat-card red">
      <div class="stat-icon">❌</div>
      <div class="stat-num"><?= $total_ditolak ?></div>
      <div class="stat-label">Pengajuan Ditolak</div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px">

    <!-- Pengajuan Terbaru -->
    <div class="card">
      <div class="card-title">
        📋 Pengajuan Terbaru
        <a href="pengajuan.php" class="btn btn-ghost btn-sm" style="margin-left:auto">Lihat Semua →</a>
      </div>
      <div class="table-wrap">
        <table>
          <tr>
            <th>Nama</th>
            <th>Program</th>
            <th>Status</th>
            <th>Tanggal</th>
            <th>Aksi</th>
          </tr>
          <?php $found=false; while($p=mysqli_fetch_assoc($pengajuan_terbaru)): $found=true; ?>
          <tr>
            <td><b><?= htmlspecialchars($p['nama']??'-') ?></b></td>
            <td style="font-size:13px"><?= htmlspecialchars($p['nama_bantuan']??'-') ?></td>
            <td>
              <?php if($p['status']==='diterima'): ?>
                <span class="badge badge-diterima">✅ Diterima</span>
              <?php elseif($p['status']==='ditolak'): ?>
                <span class="badge badge-ditolak">❌ Ditolak</span>
              <?php else: ?>
                <span class="badge badge-pending">⏳ Pending</span>
              <?php endif; ?>
            </td>
            <td style="font-size:12px;color:#999"><?= date('d/m/Y', strtotime($p['tanggal_pengajuan'])) ?></td>
            <td>
              <a href="pengajuan.php?detail=<?= $p['id_pengajuan'] ?>" class="btn btn-navy btn-sm">Detail</a>
            </td>
          </tr>
          <?php endwhile; ?>
          <?php if(!$found): ?>
          <tr><td colspan="5">
            <div class="empty-state" style="padding:30px">
              <p>Belum ada pengajuan.</p>
            </div>
          </td></tr>
          <?php endif; ?>
        </table>
      </div>
    </div>

    <!-- Pengumuman Aktif -->
    <div class="card">
      <div class="card-title">
        📢 Pengumuman Aktif
        <a href="pengumuman.php" class="btn btn-ghost btn-sm" style="margin-left:auto">Kelola →</a>
      </div>
      <?php $found=false; while($u=mysqli_fetch_assoc($pengumuman)): $found=true; ?>
      <div style="padding:14px;border:1px solid #e5e7eb;border-radius:10px;margin-bottom:12px">
        <div style="font-size:14px;font-weight:600;color:#1f2937;margin-bottom:4px">
          <?= htmlspecialchars($u['judul']) ?>
        </div>
        <div style="font-size:12px;color:#6b7280;line-height:1.5">
          <?= nl2br(htmlspecialchars(substr($u['isi'],0,100))) ?>
          <?= strlen($u['isi'])>100 ? '...' : '' ?>
        </div>
        <div style="font-size:11px;color:#9ca3af;margin-top:6px">
          <?= date('d/m/Y', strtotime($u['tanggal'])) ?>
        </div>
      </div>
      <?php endwhile; ?>
      <?php if(!$found): ?>
      <div class="empty-state" style="padding:30px">
        <div class="icon">📢</div>
        <p>Belum ada pengumuman aktif.</p>
      </div>
      <?php endif; ?>
    </div>

  </div>

</div>
</body>
</html>
