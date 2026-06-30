<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

$uid = $_SESSION['user_id'];

// Ambil data user + NIK
$user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM users WHERE id='$uid'"));
$nik  = $user['nik'] ?? '';

// Statistik pengajuan milik user ini
$total_ajuan    = $nik ? mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM pengajuan WHERE nik='$nik'"))['c'] : 0;
$total_diterima = $nik ? mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM pengajuan WHERE nik='$nik' AND status='diterima'"))['c'] : 0;
$total_pending  = $nik ? mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM pengajuan WHERE nik='$nik' AND status='pending'"))['c'] : 0;
$total_ditolak  = $nik ? mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM pengajuan WHERE nik='$nik' AND status='ditolak'"))['c'] : 0;

// Progress dinamis
$progress = 0;
if ($total_diterima > 0)     $progress = 100;
elseif ($total_pending > 0)  $progress = 65;
elseif ($total_ajuan > 0)    $progress = 30;

// Pengajuan terbaru
$pengajuan_terbaru = $nik ? mysqli_query($conn,"
    SELECT p.*, bs.nama_bantuan
    FROM pengajuan p
    LEFT JOIN bantuan_sosial bs ON p.id_bansos=bs.id_bansos
    WHERE p.nik='$nik'
    ORDER BY p.tanggal_pengajuan DESC
    LIMIT 5
") : null;

// Pengumuman aktif
$pengumuman = mysqli_query($conn,"SELECT * FROM pengumuman WHERE status='aktif' ORDER BY tanggal DESC LIMIT 3");

// Foto profil
$foto_src = !empty($user['foto'])
    ? "../uploads/profil/" . htmlspecialchars($user['foto'])
    : null;
$initial  = strtoupper(substr($user['nama'] ?? 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>SIBANSOS — Dashboard</title>
<link rel="stylesheet" href="ms_style.css">
<style>
/* ── TOPBAR FIX ── */
.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 28px;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    margin-bottom: 24px;
}
.topbar h1 { font-size: 22px; font-weight: 700; color: #0f172a; margin: 0 0 4px; }
.topbar p  { font-size: 13px; color: #64748b; margin: 0; }

.topbar-right {
    display: flex;
    align-items: center;
    gap: 16px;
}
.top-icon {
    width: 38px; height: 38px;
    border-radius: 50%;
    background: #f1f5f9;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; cursor: pointer;
    border: 1px solid #e2e8f0;
}
.profile-mini {
    display: flex;
    align-items: center;
    gap: 10px;
}
.profile-mini img,
.profile-mini .avatar-inline {
    width: 38px; height: 38px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e2e8f0;
}
.avatar-inline {
    background: linear-gradient(135deg, #17375e, #2e5c92);
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; font-weight: 700; color: #fff;
}
.profile-mini .name { font-size: 13px; font-weight: 600; color: #0f172a; }
.profile-mini .role { font-size: 11px; color: #94a3b8; }

/* ── PROGRESS BAR ── */
.progress-box { padding: 8px 0 4px; }
.progress-bar {
    background: #e2e8f0;
    border-radius: 99px;
    height: 10px;
    margin-bottom: 8px;
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    border-radius: 99px;
    background: linear-gradient(90deg, #17375e, #3b82f6);
    transition: width .6s ease;
}
.progress-text { font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 14px; }
.timeline {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    font-size: 12px;
}
.timeline span {
    padding: 4px 10px;
    border-radius: 20px;
    background: #f1f5f9;
    color: #94a3b8;
    border: 1px solid #e2e8f0;
}
.timeline span.done   { background: #dcfce7; color: #16a34a; border-color: #bbf7d0; }
.timeline span.active { background: #fef9c3; color: #b45309; border-color: #fde68a; }

/* ── STEPS PENGAJUAN (baru, lebih menarik) ── */
.steps-track {
    display: flex;
    align-items: flex-start;
    gap: 0;
    margin: 20px 0 8px;
    position: relative;
}
.steps-track::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 20px;
    right: 20px;
    height: 3px;
    background: #e2e8f0;
    z-index: 0;
}
.step-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    position: relative;
    z-index: 1;
}
.step-circle {
    width: 40px; height: 40px;
    border-radius: 50%;
    background: #e2e8f0;
    color: #94a3b8;
    font-size: 16px;
    display: flex; align-items: center; justify-content: center;
    border: 3px solid #e2e8f0;
    font-weight: 700;
    transition: .3s;
}
.step-circle.done   { background: #17375e; border-color: #17375e; color: #fff; }
.step-circle.active { background: #fff; border-color: #f59e0b; color: #f59e0b; box-shadow: 0 0 0 4px #fef3c7; }
.step-label { font-size: 11px; color: #94a3b8; text-align: center; font-weight: 500; }
.step-label.done   { color: #17375e; font-weight: 700; }
.step-label.active { color: #b45309; font-weight: 700; }
</style>
</head>
<body>

<?php include '_sidebar.php'; ?>

<div class="main">

  <!-- ══ TOPBAR ══ -->
  <div class="topbar">
    <div>
      <h1>Selamat Datang, <?= htmlspecialchars($user['nama'] ?? $_SESSION['nama']) ?>! 👋</h1>
      <p>Portal Bantuan Sosial Masyarakat &bull; <?= date('d F Y') ?></p>
    </div>
    <div class="topbar-right">
      <div class="top-icon">🔔</div>
      <div class="profile-mini">
        <?php if ($foto_src): ?>
          <img src="<?= $foto_src ?>" alt="Foto Profil">
        <?php else: ?>
          <div class="avatar-inline"><?= $initial ?></div>
        <?php endif; ?>
        <div>
          <div class="name"><?= htmlspecialchars($user['nama'] ?? $_SESSION['nama']) ?></div>
          <div class="role">Masyarakat</div>
        </div>
      </div>
    </div>
  </div>

  <!-- ══ ALERT NIK ══ -->
  <?php if (empty($nik)): ?>
  <div class="alert alert-warning" style="margin:0 28px 20px">
    ⚠️ <span>NIK belum diisi. <a href="profil.php" style="font-weight:700;color:#92400e">Lengkapi profil sekarang</a> agar bisa mengajukan bantuan.</span>
  </div>
  <?php endif; ?>

  <div style="padding:0 28px 28px">

    <!-- ══ STATISTIK ══ -->
    <div class="stats-grid" style="margin-bottom:20px">
      <div class="stat-card blue">
        <div class="stat-icon">📝</div>
        <div class="stat-num"><?= $total_ajuan ?></div>
        <div class="stat-label">Total Pengajuan</div>
      </div>
      <div class="stat-card green">
        <div class="stat-icon">✅</div>
        <div class="stat-num"><?= $total_diterima ?></div>
        <div class="stat-label">Diterima</div>
      </div>
      <div class="stat-card yellow">
        <div class="stat-icon">⏳</div>
        <div class="stat-num"><?= $total_pending ?></div>
        <div class="stat-label">Sedang Diproses</div>
      </div>
      <div class="stat-card red">
        <div class="stat-icon">❌</div>
        <div class="stat-num"><?= $total_ditolak ?></div>
        <div class="stat-label">Ditolak</div>
      </div>
    </div>

    <!-- ══ GRID 2 KOLOM ══ -->
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">

      <!-- KOLOM KIRI -->
      <div style="display:flex;flex-direction:column;gap:20px">

        <!-- Progress Pengajuan — desain baru dengan steps -->
        <div class="card">
          <div class="card-title">📊 Progress Pengajuan</div>

          <div class="progress-box">
            <div class="progress-bar">
              <div class="progress-fill" style="width:<?= $progress ?>%"></div>
            </div>
            <div class="progress-text"><?= $progress ?>% Proses Selesai</div>
          </div>

          <!-- Steps visual baru -->
          <?php
            $s1 = $total_ajuan > 0;
            $s2 = $total_pending > 0 || $total_diterima > 0;
            $s3 = $total_diterima > 0 || $total_pending > 0;
            $s4 = $total_diterima > 0;
            $a3 = $total_pending > 0 && $total_diterima == 0;
            $steps = [
              ['icon'=>'📝','label'=>'Diajukan',   'done'=>$s1,'active'=>false],
              ['icon'=>'🔍','label'=>'Diverifikasi','done'=>$s2,'active'=>false],
              ['icon'=>'⚙️','label'=>'Diproses',   'done'=>$s4,'active'=>$a3],
              ['icon'=>'✅','label'=>'Disalurkan',  'done'=>$s4,'active'=>false],
            ];
          ?>
          <div class="steps-track">
            <?php foreach($steps as $s): ?>
            <div class="step-item">
              <div class="step-circle <?= $s['done']?'done':($s['active']?'active':'') ?>">
                <?= $s['icon'] ?>
              </div>
              <div class="step-label <?= $s['done']?'done':($s['active']?'active':'') ?>">
                <?= $s['label'] ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Pengajuan Terbaru -->
        <div class="card">
          <div class="card-title" style="display:flex;align-items:center">
            📋 Pengajuan Terbaru
            <a href="status.php" class="btn btn-ghost btn-sm" style="margin-left:auto">Lihat Semua →</a>
          </div>
          <?php if (!$nik || !$pengajuan_terbaru || mysqli_num_rows($pengajuan_terbaru) == 0): ?>
          <div class="empty-state" style="padding:30px;text-align:center">
            <div style="font-size:32px">📝</div>
            <p style="color:#94a3b8;margin:8px 0 16px">Belum ada pengajuan.</p>
            <a href="ajukan.php" class="btn btn-primary btn-sm">+ Ajukan Sekarang</a>
          </div>
          <?php else: ?>
          <div class="table-wrap">
            <table>
              <tr><th>Program</th><th>Status</th><th>Tanggal</th></tr>
              <?php while($p = mysqli_fetch_assoc($pengajuan_terbaru)): ?>
              <tr>
                <td><b><?= htmlspecialchars($p['nama_bantuan'] ?? '-') ?></b></td>
                <td>
                  <?php if ($p['status'] === 'diterima'): ?>
                    <span class="badge badge-diterima">✅ Diterima</span>
                  <?php elseif ($p['status'] === 'ditolak'): ?>
                    <span class="badge badge-ditolak">❌ Ditolak</span>
                  <?php else: ?>
                    <span class="badge badge-pending">⏳ Pending</span>
                  <?php endif; ?>
                </td>
                <td style="font-size:12px;color:#64748b"><?= date('d/m/Y', strtotime($p['tanggal_pengajuan'])) ?></td>
              </tr>
              <?php endwhile; ?>
            </table>
          </div>
          <?php endif; ?>
        </div>

        <!-- Aksi Cepat -->
        <div class="card">
          <div class="card-title">⚡ Aksi Cepat</div>
          <div style="display:flex;gap:12px;flex-wrap:wrap">
            <a href="ajukan.php" class="btn btn-primary">📝 Ajukan Bantuan Baru</a>
            <a href="status.php" class="btn btn-ghost">🔍 Cek Status</a>
            <a href="riwayat.php" class="btn btn-ghost">📋 Riwayat</a>
            <a href="profil.php" class="btn btn-ghost">👤 Edit Profil</a>
          </div>
        </div>

      </div>
      <!-- END KOLOM KIRI -->

      <!-- KOLOM KANAN -->
      <div>
        <div class="card">
          <div class="card-title">📢 Pengumuman</div>
          <?php
            $found = false;
            while ($u = mysqli_fetch_assoc($pengumuman)):
              $found = true;
          ?>
          <div class="pengumuman-item">
            <div class="pengumuman-judul"><?= htmlspecialchars($u['judul']) ?></div>
            <div class="pengumuman-isi"><?= nl2br(htmlspecialchars(substr($u['isi'], 0, 120))) ?><?= strlen($u['isi']) > 120 ? '...' : '' ?></div>
            <div class="pengumuman-tgl">📅 <?= date('d/m/Y', strtotime($u['tanggal'])) ?></div>
          </div>
          <?php endwhile; ?>
          <?php if (!$found): ?>
          <div style="padding:24px;text-align:center;color:#94a3b8">
            <div style="font-size:28px">📢</div>
            <p style="margin:8px 0 0;font-size:13px">Belum ada pengumuman.</p>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <!-- END KOLOM KANAN -->

    </div>
    <!-- END GRID -->

  </div>
</div>
</body>
</html>