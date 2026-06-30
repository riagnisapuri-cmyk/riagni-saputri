<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

$uid  = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM users WHERE id='$uid'"));
$nik  = $user['nik'] ?? '';

$data = $nik ? mysqli_query($conn,"
    SELECT p.*, bs.nama_bantuan, bs.jenis_bantuan, bs.jumlah_bantuan
    FROM pengajuan p
    LEFT JOIN bantuan_sosial bs ON p.id_bansos=bs.id_bansos
    WHERE p.nik='$nik'
    ORDER BY p.tanggal_pengajuan DESC
") : null;

$total = $data ? mysqli_num_rows($data) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>SIBANSOS — Status Pengajuan</title>
<link rel="stylesheet" href="ms_style.css">
<style>
.timeline { position:relative; padding-left:24px; }
.timeline::before { content:''; position:absolute; left:7px; top:0; bottom:0; width:2px; background:#e2e8f0; }
.tl-item { position:relative; margin-bottom:24px; }
.tl-dot { position:absolute; left:-21px; top:4px; width:14px; height:14px; border-radius:50%; border:2px solid white; }
.tl-dot.pending  { background:#f59e0b; }
.tl-dot.diterima { background:#16a34a; }
.tl-dot.ditolak  { background:#dc2626; }

.pengajuan-card {
  border:1px solid var(--border); border-radius:14px;
  padding:20px; margin-bottom:16px;
  transition:box-shadow .2s;
}
.pengajuan-card:hover { box-shadow:0 4px 16px rgba(0,0,0,0.08); }
.pengajuan-card.diterima { border-left:4px solid #16a34a; }
.pengajuan-card.ditolak  { border-left:4px solid #dc2626; }
.pengajuan-card.pending  { border-left:4px solid #f59e0b; }
</style>
</head>
<body>

<?php include '_sidebar.php'; ?>

<div class="main">

  <div class="page-header">
    <h1>🔍 Status Pengajuan</h1>
    <p>Pantau perkembangan pengajuan bantuan sosial kamu</p>
  </div>

  <?php if (empty($nik)): ?>
  <div class="alert alert-warning">
    ⚠️ NIK belum diisi. <a href="profil.php" style="font-weight:700">Lengkapi profil</a> untuk melihat status pengajuan.
  </div>
  <?php elseif ($total == 0): ?>
  <div class="card">
    <div class="empty-state">
      <div class="icon">📋</div>
      <p>Belum ada pengajuan.</p>
      <a href="ajukan.php" class="btn btn-primary btn-sm" style="margin-top:14px;display:inline-flex">+ Ajukan Sekarang</a>
    </div>
  </div>
  <?php else: ?>

  <p style="font-size:13px;color:#64748b;margin-bottom:16px">
    Total <b style="color:var(--text)"><?= $total ?></b> pengajuan ditemukan
  </p>

  <?php while($p=mysqli_fetch_assoc($data)): ?>
  <div class="pengajuan-card <?= $p['status'] ?>">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px">

      <div style="flex:1">
        <!-- Header -->
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
          <div style="font-size:16px;font-weight:700;color:var(--text)">
            <?= htmlspecialchars($p['nama_bantuan']??'Program Bantuan') ?>
          </div>
          <?php if($p['status']==='diterima'): ?>
            <span class="badge badge-diterima">✅ Diterima</span>
          <?php elseif($p['status']==='ditolak'): ?>
            <span class="badge badge-ditolak">❌ Ditolak</span>
          <?php else: ?>
            <span class="badge badge-pending">⏳ Sedang Diproses</span>
          <?php endif; ?>
        </div>

        <!-- Detail Grid -->
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:12px">
          <div>
            <div style="font-size:11px;color:#94a3b8;text-transform:uppercase;font-weight:600">Jenis</div>
            <div style="font-size:13px;color:var(--text)"><?= $p['jenis_bantuan']?:'-' ?></div>
          </div>
          <div>
            <div style="font-size:11px;color:#94a3b8;text-transform:uppercase;font-weight:600">Nilai Bantuan</div>
            <div style="font-size:13px;color:#166534;font-weight:600">Rp <?= number_format($p['jumlah_bantuan'],0,',','.') ?></div>
          </div>
          <div>
            <div style="font-size:11px;color:#94a3b8;text-transform:uppercase;font-weight:600">Tanggal Ajuan</div>
            <div style="font-size:13px;color:var(--text)"><?= date('d/m/Y',strtotime($p['tanggal_pengajuan'])) ?></div>
          </div>
        </div>

        <!-- Alasan -->
        <div style="background:#f8fafc;border-radius:8px;padding:10px 12px;font-size:13px;color:#475569;margin-bottom:10px">
          <b>Alasan:</b> <?= nl2br(htmlspecialchars($p['alasan']??'—')) ?>
        </div>

        <!-- Catatan Admin (jika ada) -->
        <?php if (!empty($p['catatan_admin'])): ?>
        <div style="background:<?= $p['status']==='diterima'?'#f0fdf4':($p['status']==='ditolak'?'#fef2f2':'#fffbeb') ?>;border-radius:8px;padding:10px 12px;font-size:13px;margin-bottom:10px">
          <b>📌 Catatan Admin:</b> <?= nl2br(htmlspecialchars($p['catatan_admin'])) ?>
        </div>
        <?php endif; ?>

        <!-- Timeline status -->
        <div style="display:flex;gap:6px;align-items:center;font-size:12px;color:#94a3b8">
          <span style="background:#e2e8f0;padding:3px 8px;border-radius:6px">📅 Diajukan: <?= date('d/m/Y H:i',strtotime($p['tanggal_pengajuan'])) ?></span>
          <?php if ($p['tanggal_proses']): ?>
          <span>→</span>
          <span style="background:<?= $p['status']==='diterima'?'#dcfce7':($p['status']==='ditolak'?'#fee2e2':'#fef9c3') ?>;padding:3px 8px;border-radius:6px">
            ⚖️ Diproses: <?= date('d/m/Y',strtotime($p['tanggal_proses'])) ?>
          </span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Dokumen -->
      <?php if (!empty($p['dokumen'])): ?>
      <div style="flex-shrink:0">
        <a href="../uploads/dokumen/<?= htmlspecialchars($p['dokumen']) ?>" target="_blank"
           class="btn btn-ghost btn-sm">
          📄 Dokumen
        </a>
      </div>
      <?php endif; ?>

    </div>
  </div>
  <?php endwhile; ?>

  <div style="margin-top:8px">
    <a href="ajukan.php" class="btn btn-primary">+ Ajukan Bantuan Baru</a>
  </div>

  <?php endif; ?>

</div>
</body>
</html>
