<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

// Ambil semua pengumuman aktif
$pengumuman = mysqli_query($conn, "SELECT * FROM pengumuman WHERE status='aktif' ORDER BY tanggal DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>SIBANSOS — Pengumuman</title>
<link rel="stylesheet" href="ms_style.css">
<style>
.topbar { display:flex; align-items:center; justify-content:space-between; padding:20px 28px; background:#fff; border-bottom:1px solid #e2e8f0; margin-bottom:24px; }
.topbar h1 { font-size:22px; font-weight:700; color:#0f172a; margin:0 0 4px; }
.topbar p  { font-size:13px; color:#64748b; margin:0; }
.topbar-right { display:flex; align-items:center; gap:16px; }
.top-icon { width:38px; height:38px; border-radius:50%; background:#f1f5f9; display:flex; align-items:center; justify-content:center; font-size:18px; border:1px solid #e2e8f0; }
.profile-mini { display:flex; align-items:center; gap:10px; }
.profile-mini img, .profile-mini .avatar-inline { width:38px; height:38px; border-radius:50%; object-fit:cover; border:2px solid #e2e8f0; }
.avatar-inline { background:linear-gradient(135deg,#17375e,#2e5c92); display:flex; align-items:center; justify-content:center; font-size:16px; font-weight:700; color:#fff; }
.profile-mini .name { font-size:13px; font-weight:600; color:#0f172a; }
.profile-mini .role { font-size:11px; color:#94a3b8; }

.pengumuman-card {
    background:#fff;
    border-radius:12px;
    border:1px solid #e2e8f0;
    padding:20px 24px;
    margin-bottom:14px;
    border-left:4px solid #17375e;
    transition:.2s;
}
.pengumuman-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.07); transform:translateY(-1px); }
.pengumuman-card .judul { font-size:15px; font-weight:700; color:#0f172a; margin-bottom:6px; }
.pengumuman-card .isi  { font-size:13px; color:#475569; line-height:1.6; margin-bottom:10px; }
.pengumuman-card .meta { font-size:12px; color:#94a3b8; display:flex; gap:16px; }
.badge-aktif { background:#dcfce7; color:#16a34a; padding:2px 8px; border-radius:6px; font-size:11px; font-weight:600; }
</style>
</head>
<body>
<?php include '_sidebar.php'; ?>
<div class="main">

  <?php
  $uid = $_SESSION['user_id'];
  $user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT foto, nama FROM users WHERE id='$uid'"));
  $foto_src = !empty($user['foto']) ? "../uploads/profil/".htmlspecialchars($user['foto']) : null;
  $initial  = strtoupper(substr($user['nama']??'U',0,1));
  ?>

  <div class="topbar">
    <div>
      <h1>📢 Pengumuman</h1>
      <p>Informasi dan pengumuman terbaru dari SIBANSOS</p>
    </div>
    <div class="topbar-right">
      <div class="top-icon">🔔</div>
      <div class="profile-mini">
        <?php if($foto_src): ?><img src="<?= $foto_src ?>" alt="Foto">
        <?php else: ?><div class="avatar-inline"><?= $initial ?></div><?php endif; ?>
        <div>
          <div class="name"><?= htmlspecialchars($user['nama']) ?></div>
          <div class="role">Masyarakat</div>
        </div>
      </div>
    </div>
  </div>

  <div style="padding:0 28px 28px">
    <?php if (mysqli_num_rows($pengumuman) == 0): ?>
    <div class="card" style="text-align:center;padding:48px">
      <div style="font-size:48px;margin-bottom:12px">📢</div>
      <div style="font-size:16px;font-weight:600;color:#0f172a;margin-bottom:6px">Belum ada pengumuman</div>
      <div style="font-size:13px;color:#94a3b8">Pengumuman akan muncul di sini jika ada informasi baru.</div>
    </div>
    <?php else: ?>
      <?php while($u = mysqli_fetch_assoc($pengumuman)): ?>
      <div class="pengumuman-card">
        <div class="judul"><?= htmlspecialchars($u['judul']) ?></div>
        <div class="isi"><?= nl2br(htmlspecialchars($u['isi'])) ?></div>
        <div class="meta">
          <span>📅 <?= date('d F Y', strtotime($u['tanggal'])) ?></span>
          <span class="badge-aktif">● Aktif</span>
        </div>
      </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>

</div>
</body>
</html>