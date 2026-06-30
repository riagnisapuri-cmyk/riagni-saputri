<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

$msg = $err = "";

// Tambah
if (isset($_POST['simpan'])) {
    $nik         = mysqli_real_escape_string($conn, $_POST['nik']);
    $nama        = mysqli_real_escape_string($conn, $_POST['nama']);
    $alamat      = mysqli_real_escape_string($conn, $_POST['alamat']);
    $pekerjaan   = mysqli_real_escape_string($conn, $_POST['pekerjaan']);
    $penghasilan = mysqli_real_escape_string($conn, $_POST['penghasilan']);
    $tanggungan  = mysqli_real_escape_string($conn, $_POST['tanggungan'] ?? 0);
    $kondisi     = mysqli_real_escape_string($conn, $_POST['kondisi_rumah'] ?? '');
    $no_hp       = mysqli_real_escape_string($conn, $_POST['no_hp'] ?? '');

    if (!$nik || !$nama) { $err = "NIK dan Nama wajib diisi!"; }
    elseif (strlen($nik) < 10) { $err = "NIK tidak valid (minimal 10 digit)."; }
    else {
        $cek = mysqli_query($conn,"SELECT nik FROM masyarakat WHERE nik='$nik'");
        if (mysqli_num_rows($cek)>0) { $err = "NIK '$nik' sudah terdaftar."; }
        else {
            mysqli_query($conn,"INSERT INTO masyarakat (nik,nama,alamat,pekerjaan,penghasilan,tanggungan,kondisi_rumah,no_hp) VALUES('$nik','$nama','$alamat','$pekerjaan','$penghasilan','$tanggungan','$kondisi','$no_hp')");
            $msg = "Data masyarakat berhasil ditambahkan.";
        }
    }
}

// Bagian hapus
if (isset($_GET['hapus'])) {
    $nik = $_GET['hapus'];
    $stmt = $conn->prepare("DELETE FROM masyarakat WHERE nik=?");
    $stmt->bind_param("s", $nik);
    $stmt->execute();
    $stmt->close();
    header("Location: data_masyarakat.php"); exit;
}
// Bagian simpan
$stmt = $conn->prepare("INSERT INTO masyarakat (nik,nama,alamat,pekerjaan,penghasilan,tanggungan,kondisi_rumah,no_hp) VALUES (?,?,?,?,?,?,?,?)");
$stmt->bind_param("sssiiiss", $nik,$nama,$alamat,$pekerjaan,$penghasilan,$tanggungan,$kondisi,$no_hp);

// Search & filter
$search = isset($_GET['q']) ? mysqli_real_escape_string($conn,$_GET['q']) : '';
$where  = $search ? "WHERE nik LIKE '%$search%' OR nama LIKE '%$search%' OR alamat LIKE '%$search%'" : '';
$data   = mysqli_query($conn,"SELECT * FROM masyarakat $where ORDER BY nama ASC");
$total  = mysqli_num_rows($data);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Admin — Data Masyarakat</title>
<link rel="stylesheet" href="admin_style.css">
</head>
<body>
<?php include '_sidebar.php'; ?>
<div class="main">

  <div class="page-header">
    <div><h1>Data Masyarakat</h1><p>Kelola data warga penerima bantuan</p></div>
    <div class="breadcrumb"><a href="index.php">Dashboard</a> / Data Masyarakat</div>
  </div>

  <?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <!-- Form Tambah -->
  <div class="card">
    <div class="card-title">➕ Tambah Data Masyarakat</div>
    <form method="POST">
      <div class="form-grid">
        <div class="field">
          <label>NIK</label>
          <input type="text" name="nik" placeholder="16 digit NIK" maxlength="16" required>
        </div>
        <div class="field">
          <label>Nama Lengkap</label>
          <input type="text" name="nama" placeholder="Nama lengkap" required>
        </div>
        <div class="field span-full">
          <label>Alamat</label>
          <textarea name="alamat" placeholder="Alamat lengkap" rows="2"></textarea>
        </div>
        <div class="field">
          <label>Pekerjaan</label>
          <input type="text" name="pekerjaan" placeholder="Pekerjaan">
        </div>
        <div class="field">
          <label>Penghasilan / Bulan (Rp)</label>
          <input type="number" name="penghasilan" placeholder="0">
        </div>
        <div class="field">
          <label>Jumlah Tanggungan</label>
          <input type="number" name="tanggungan" placeholder="0" min="0">
        </div>
        <div class="field">
          <label>Kondisi Rumah</label>
          <select name="kondisi_rumah">
            <option value="">-- Pilih --</option>
            <option>Layak Huni</option>
            <option>Kurang Layak</option>
            <option>Tidak Layak</option>
          </select>
        </div>
        <div class="field">
          <label>No. HP</label>
          <input type="text" name="no_hp" placeholder="08xxxxxxxxxx">
        </div>
      </div>
      <button type="submit" name="simpan" class="btn btn-navy">Simpan Data</button>
    </form>
  </div>

  <!-- Tabel -->
  <div class="card">
    <div class="card-title">👥 Daftar Masyarakat</div>

    <form method="GET" class="filter-bar">
      <input type="text" name="q" placeholder="🔍 Cari NIK, nama, atau alamat..." value="<?= htmlspecialchars($search) ?>" style="flex:1;min-width:220px">
      <button type="submit" class="btn btn-navy btn-sm">Cari</button>
      <a href="data_masyarakat.php" class="btn btn-ghost btn-sm">Reset</a>
    </form>

    <p style="font-size:13px;color:#6b7280;margin-bottom:16px">
      Menampilkan <b style="color:#1f2937"><?= $total ?></b> data
    </p>

    <div class="table-wrap">
      <table>
        <tr><th>#</th><th>NIK</th><th>Nama</th><th>Alamat</th><th>Pekerjaan</th><th>Penghasilan</th><th>Tanggungan</th><th>Kondisi Rumah</th><th>Aksi</th></tr>
        <?php $no=1; $data=mysqli_query($conn,"SELECT * FROM masyarakat $where ORDER BY nama ASC");
        while($d=mysqli_fetch_assoc($data)): ?>
        <tr>
          <td style="color:#999"><?= $no++ ?></td>
          <td style="font-family:monospace;font-size:13px"><?= $d['nik'] ?></td>
          <td><b><?= htmlspecialchars($d['nama']) ?></b>
            <?php if(!empty($d['no_hp'])): ?>
            <div style="font-size:11px;color:#999"><?= $d['no_hp'] ?></div>
            <?php endif; ?>
          </td>
          <td style="font-size:13px;max-width:180px"><?= htmlspecialchars($d['alamat']??'-') ?></td>
          <td><?= htmlspecialchars($d['pekerjaan']??'-') ?></td>
          <td style="color:#166534;font-weight:600">Rp <?= number_format($d['penghasilan'],0,',','.') ?></td>
          <td style="text-align:center"><?= $d['tanggungan']??0 ?> orang</td>
          <td>
            <?php
            $k = $d['kondisi_rumah'] ?? '';
            $kls = $k==='Layak Huni' ? 'badge-diterima' : ($k==='Tidak Layak' ? 'badge-ditolak' : 'badge-pending');
            ?>
            <span class="badge <?= $kls ?>"><?= $k?:'-' ?></span>
          </td>
          <td>
            <a href="data_masyarakat.php?hapus=<?= urlencode($d['nik']) ?>"
               class="btn btn-red btn-sm"
               onclick="return confirm('Hapus data <?= addslashes($d['nama']) ?>?')">
              🗑️ Hapus
            </a>
          </td>
        </tr>
        <?php endwhile; ?>
        <?php if($total==0): ?>
        <tr><td colspan="9"><div class="empty-state"><div class="icon">👥</div><p>Tidak ada data ditemukan.</p></div></td></tr>
        <?php endif; ?>
      </table>
    </div>
  </div>

</div>
</body>
</html>
