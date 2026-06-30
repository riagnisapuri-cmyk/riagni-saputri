<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

$msg = $err = "";

// Tambah
if (isset($_POST['simpan'])) {
    $judul  = mysqli_real_escape_string($conn, $_POST['judul']);
    $isi    = mysqli_real_escape_string($conn, $_POST['isi']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $pembuat = $_SESSION['user_id'];

    if (!$judul || !$isi) { $err = "Judul dan isi wajib diisi!"; }
    else {
        mysqli_query($conn,"INSERT INTO pengumuman (judul,isi,id_pembuat,status) VALUES('$judul','$isi','$pembuat','$status')");
        $msg = "Pengumuman berhasil ditambahkan.";
    }
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id  = (int)$_GET['toggle'];
    $cur = mysqli_fetch_assoc(mysqli_query($conn,"SELECT status FROM pengumuman WHERE id_pengumuman='$id'"));
    $new = $cur['status']==='aktif' ? 'nonaktif' : 'aktif';
    mysqli_query($conn,"UPDATE pengumuman SET status='$new' WHERE id_pengumuman='$id'");
    header("Location: pengumuman.php"); exit;
}

// Hapus
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn,"DELETE FROM pengumuman WHERE id_pengumuman='$id'");
    header("Location: pengumuman.php"); exit;
}

$data  = mysqli_query($conn,"SELECT * FROM pengumuman ORDER BY tanggal DESC");
$total = mysqli_num_rows($data);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Admin — Pengumuman</title>
<link rel="stylesheet" href="admin_style.css">
</head>
<body>
<?php include '_sidebar.php'; ?>
<div class="main">

  <div class="page-header">
    <div><h1>Pengumuman</h1><p>Kelola pengumuman untuk masyarakat</p></div>
    <div class="breadcrumb"><a href="index.php">Dashboard</a> / Pengumuman</div>
  </div>

  <?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:24px">

    <!-- Form Tambah -->
    <div class="card">
      <div class="card-title">📢 Buat Pengumuman</div>
      <form method="POST">
        <div class="field" style="margin-bottom:16px">
          <label>Judul Pengumuman</label>
          <input type="text" name="judul" placeholder="Judul pengumuman" required>
        </div>
        <div class="field" style="margin-bottom:16px">
          <label>Isi Pengumuman</label>
          <textarea name="isi" rows="6" placeholder="Tulis isi pengumuman..." required></textarea>
        </div>
        <div class="field" style="margin-bottom:20px">
          <label>Status</label>
          <select name="status">
            <option value="aktif">🟢 Aktif (langsung tampil)</option>
            <option value="nonaktif">⚫ Nonaktif (simpan draf)</option>
          </select>
        </div>
        <button type="submit" name="simpan" class="btn btn-navy">📢 Publikasikan</button>
      </form>
    </div>

    <!-- Daftar Pengumuman -->
    <div class="card">
      <div class="card-title">
        📋 Daftar Pengumuman
        <span style="margin-left:auto;font-size:13px;color:#6b7280;font-weight:400"><?= $total ?> total</span>
      </div>

      <?php if($total==0): ?>
      <div class="empty-state"><div class="icon">📢</div><p>Belum ada pengumuman.</p></div>
      <?php else: ?>
      <?php while($d=mysqli_fetch_assoc($data)): ?>
      <div style="border:1px solid #e5e7eb;border-radius:12px;padding:16px;margin-bottom:14px;<?= $d['status']==='nonaktif'?'opacity:.6':'' ?>">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px">
          <div style="flex:1">
            <div style="font-size:15px;font-weight:700;color:#1f2937;margin-bottom:4px">
              <?= htmlspecialchars($d['judul']) ?>
            </div>
            <div style="font-size:13px;color:#6b7280;line-height:1.5;margin-bottom:10px">
              <?= nl2br(htmlspecialchars(substr($d['isi'],0,150))) ?>
              <?= strlen($d['isi'])>150?'...':'' ?>
            </div>
            <div style="display:flex;align-items:center;gap:10px">
              <span class="badge <?= $d['status']==='aktif'?'badge-aktif':'badge-nonaktif' ?>">
                <?= $d['status']==='aktif'?'🟢 Aktif':'⚫ Nonaktif' ?>
              </span>
              <span style="font-size:11px;color:#9ca3af"><?= date('d/m/Y H:i',strtotime($d['tanggal'])) ?></span>
            </div>
          </div>
          <div style="display:flex;flex-direction:column;gap:6px">
            <a href="pengumuman.php?toggle=<?= $d['id_pengumuman'] ?>"
               class="btn btn-yellow btn-sm">
              <?= $d['status']==='aktif'?'⚫ Nonaktifkan':'🟢 Aktifkan' ?>
            </a>
            <a href="pengumuman.php?hapus=<?= $d['id_pengumuman'] ?>"
               class="btn btn-red btn-sm"
               onclick="return confirm('Hapus pengumuman ini?')">
              🗑️ Hapus
            </a>
          </div>
        </div>
      </div>
      <?php endwhile; ?>
      <?php endif; ?>
    </div>

  </div>

</div>
</body>
</html>
