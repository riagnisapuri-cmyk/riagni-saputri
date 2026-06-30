<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

$msg = $err = "";

// Tambah
if (isset($_POST['simpan'])) {
    $nama     = mysqli_real_escape_string($conn, $_POST['nama_bantuan']);
    $jenis    = mysqli_real_escape_string($conn, $_POST['jenis_bantuan']);
    $tgl      = mysqli_real_escape_string($conn, $_POST['tanggal_bantuan']);
    $jumlah   = mysqli_real_escape_string($conn, $_POST['jumlah_bantuan']);

    if (!$nama) { $err = "Nama bantuan wajib diisi!"; }
    else {
        mysqli_query($conn,"INSERT INTO bantuan_sosial (nama_bantuan,jenis_bantuan,tanggal_bantuan,jumlah_bantuan) VALUES('$nama','$jenis','$tgl','$jumlah')");
        $msg = "Program bantuan berhasil ditambahkan.";
    }
}

// Hapus
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn,"DELETE FROM bantuan_sosial WHERE id_bansos='$id'");
    header("Location: data_bantuan.php"); exit;
}

$data = mysqli_query($conn,"SELECT * FROM bantuan_sosial ORDER BY id_bansos DESC");
$total = mysqli_num_rows($data);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Admin — Data Bantuan</title>
<link rel="stylesheet" href="admin_style.css">
</head>
<body>
<?php include '_sidebar.php'; ?>
<div class="main">

  <div class="page-header">
    <div><h1>Data Bantuan Sosial</h1><p>Kelola program bantuan sosial</p></div>
    <div class="breadcrumb"><a href="index.php">Dashboard</a> / Data Bantuan</div>
  </div>

  <?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <!-- Form Tambah -->
  <div class="card">
    <div class="card-title">➕ Tambah Program Bantuan</div>
    <form method="POST">
      <div class="form-grid">
        <div class="field">
          <label>Nama Bantuan</label>
          <input type="text" name="nama_bantuan" placeholder="Contoh: BLT Dana Desa" required>
        </div>
        <div class="field">
          <label>Jenis Bantuan</label>
          <select name="jenis_bantuan">
            <option value="">-- Pilih Jenis --</option>
            <option>Sembako</option>
            <option>Tunai</option>
            <option>Pendidikan</option>
            <option>Kesehatan</option>
            <option>UMKM</option>
            <option>Lainnya</option>
          </select>
        </div>
        <div class="field">
          <label>Tanggal Bantuan</label>
          <input type="date" name="tanggal_bantuan">
        </div>
        <div class="field">
          <label>Jumlah Bantuan (Rp)</label>
          <input type="number" name="jumlah_bantuan" placeholder="Nominal dalam Rupiah">
        </div>
      </div>
      <button type="submit" name="simpan" class="btn btn-navy">Simpan Program</button>
    </form>
  </div>

  <!-- Tabel -->
  <div class="card">
    <div class="card-title">
      🎁 Daftar Program Bantuan
      <span style="margin-left:auto;font-size:13px;color:#6b7280;font-weight:400">
        Total: <b style="color:#1f2937"><?= $total ?></b> program
      </span>
    </div>
    <div class="table-wrap">
      <table>
        <tr>
          <th>#</th><th>Nama Bantuan</th><th>Jenis</th><th>Tanggal</th><th>Jumlah</th><th>Penerima</th><th>Aksi</th>
        </tr>
        <?php $no=1; $data=mysqli_query($conn,"SELECT bs.*, COUNT(pb.id_penerima) as jml_penerima FROM bantuan_sosial bs LEFT JOIN penerima_bantuan pb ON bs.id_bansos=pb.id_bansos GROUP BY bs.id_bansos ORDER BY bs.id_bansos DESC");
        while($d=mysqli_fetch_assoc($data)): ?>
        <tr>
          <td style="color:#999"><?= $no++ ?></td>
          <td><b><?= htmlspecialchars($d['nama_bantuan']) ?></b></td>
          <td><span class="badge badge-navy"><?= $d['jenis_bantuan']?:'-' ?></span></td>
          <td style="font-size:13px"><?= $d['tanggal_bantuan'] ? date('d/m/Y',strtotime($d['tanggal_bantuan'])) : '—' ?></td>
          <td style="color:#166534;font-weight:600">Rp <?= number_format($d['jumlah_bantuan'],0,',','.') ?></td>
          <td><span class="badge badge-pending"><?= $d['jml_penerima'] ?> orang</span></td>
          <td>
            <a href="data_bantuan.php?hapus=<?= $d['id_bansos'] ?>"
               class="btn btn-red btn-sm"
               onclick="return confirm('Hapus program <?= addslashes($d['nama_bantuan']) ?>?')">
              🗑️ Hapus
            </a>
          </td>
        </tr>
        <?php endwhile; ?>
        <?php if($total==0): ?>
        <tr><td colspan="7"><div class="empty-state"><div class="icon">🎁</div><p>Belum ada program bantuan.</p></div></td></tr>
        <?php endif; ?>
      </table>
    </div>
  </div>

</div>
</body>
</html>
