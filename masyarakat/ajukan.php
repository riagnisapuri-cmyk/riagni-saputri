<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

$uid  = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM users WHERE id='$uid'"));
$nik  = $user['nik'] ?? '';

$msg = $err = "";

if (isset($_POST['ajukan'])) {
    if (empty($nik)) {
        $err = "NIK belum diisi. Silakan lengkapi profil terlebih dahulu.";
    } else {
        $id_bansos = (int)$_POST['id_bansos'];
        $alasan    = mysqli_real_escape_string($conn, $_POST['alasan']);

        // Cek sudah pernah mengajukan program ini dan masih pending/diterima
        $cek = mysqli_query($conn,"SELECT id_pengajuan FROM pengajuan WHERE nik='$nik' AND id_bansos='$id_bansos' AND status IN ('pending','diterima')");
        if (mysqli_num_rows($cek) > 0) {
            $err = "Kamu sudah pernah mengajukan program ini dan masih diproses/diterima.";
        } else {
            // Upload dokumen
            $folder = '../uploads/dokumen/';
if (!is_dir($folder)) {
    mkdir($folder, 0777, true);
}

$allowed = ['pdf','jpg','jpeg','png'];
$max = 5 * 1024 * 1024;

function uploadDokumen($nama, $prefix, $nik, $folder, $allowed, $max, &$err)
{
            if (empty($_FILES[$nama]['name'])) {
                $err = "File $prefix wajib diupload.";
                return '';
            }

            $ext = strtolower(pathinfo($_FILES[$nama]['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                $err = "Format $prefix harus PDF/JPG/PNG.";
                return '';
            }

            if ($_FILES[$nama]['size'] > $max) {
                $err = "Ukuran $prefix maksimal 5MB.";
                return '';
            }

            $fileBaru = $prefix . "_" . $nik . "_" . time() . "." . $ext;

            move_uploaded_file($_FILES[$nama]['tmp_name'], $folder . $fileBaru);

            return $fileBaru;
        }

        $ktp  = uploadDokumen('ktp','KTP',$nik,$folder,$allowed,$max,$err);
        $kk   = uploadDokumen('kk','KK',$nik,$folder,$allowed,$max,$err);
        $sktm = uploadDokumen('sktm','SKTM',$nik,$folder,$allowed,$max,$err);

            if (!$err) {
                if (!$id_bansos) { $err = "Pilih program bantuan terlebih dahulu."; }
                elseif (!$alasan) { $err = "Alasan pengajuan wajib diisi."; }
                else {
        $ktp  = mysqli_real_escape_string($conn, $ktp);
$kk   = mysqli_real_escape_string($conn, $kk);
$sktm = mysqli_real_escape_string($conn, $sktm);

$sql = "INSERT INTO pengajuan
(nik, id_bansos, alasan, ktp, kk, sktm, status)
VALUES
('$nik', '$id_bansos', '$alasan', '$ktp', '$kk', '$sktm', 'pending')";

if (mysqli_query($conn, $sql)) {
    $msg = "Pengajuan berhasil dikirim! Silakan pantau status di menu Status Pengajuan.";
} else {
    $err = "Gagal menyimpan data: " . mysqli_error($conn);
            }
$list_bansos = mysqli_query($conn,"SELECT * FROM bantuan_sosial ORDER BY nama_bantuan");
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>SIBANSOS — Ajukan Bantuan</title>
<link rel="stylesheet" href="ms_style.css">
<style>
.program-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:14px; margin-bottom:20px; }
.program-card input[type="radio"] { display:none; }
.program-card label {
  display:block; padding:16px; border:2px solid var(--border);
  border-radius:12px; cursor:pointer; transition:all .2s;
}
.program-card label:hover { border-color:#3b82f6; background:#eff6ff; }
.program-card input:checked + label { border-color:#17375e; background:#eff6ff; }
.prog-nama  { font-size:14px; font-weight:700; color:var(--text); margin-bottom:4px; }
.prog-jenis { font-size:12px; color:#3b82f6; font-weight:600; margin-bottom:4px; }
.prog-nilai { font-size:13px; color:#166534; font-weight:600; }

.upload-area {
  border: 2px dashed var(--border); border-radius:12px; padding:28px;
  text-align:center; cursor:pointer; transition:border-color .2s;
}
.upload-area:hover { border-color:#3b82f6; }
.upload-area input { display:none; }
</style>
</head>
<body>

<?php include '_sidebar.php'; ?>

<div class="main">

  <div class="page-header">
    <h1>📝 Ajukan Bantuan</h1>
    <p>Isi formulir pengajuan bantuan sosial di bawah ini</p>
  </div>

  <?php if (empty($nik)): ?>
  <div class="alert alert-warning">
    ⚠️ NIK belum diisi. <a href="profil.php" style="font-weight:700">Lengkapi profil sekarang</a> sebelum mengajukan bantuan.
  </div>
  <?php endif; ?>

  <?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div class="card">
    <div class="card-title">📋 Formulir Pengajuan Bantuan</div>

    <form method="POST" enctype="multipart/form-data">

      <!-- Pilih Program -->
      <div class="field" style="margin-bottom:20px">
        <label style="margin-bottom:12px;display:block">Pilih Program Bantuan</label>
        <div class="program-grid">
          <?php
          $list_bansos = mysqli_query($conn,"SELECT * FROM bantuan_sosial ORDER BY nama_bantuan");
          $ada = false;
          while($b=mysqli_fetch_assoc($list_bansos)): $ada=true; ?>
          <div class="program-card">
            <input type="radio" name="id_bansos" id="bs_<?= $b['id_bansos'] ?>" value="<?= $b['id_bansos'] ?>" required>
            <label for="bs_<?= $b['id_bansos'] ?>">
              <div class="prog-nama"><?= htmlspecialchars($b['nama_bantuan']) ?></div>
              <div class="prog-jenis">🏷️ <?= $b['jenis_bantuan']?:'-' ?></div>
              <div class="prog-nilai">💰 Rp <?= number_format($b['jumlah_bantuan'],0,',','.') ?></div>
            </label>
          </div>
          <?php endwhile; ?>
          <?php if(!$ada): ?>
          <p style="color:#64748b;font-size:13px">Belum ada program bantuan tersedia.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Alasan -->
      <div class="field" style="margin-bottom:20px">
        <label>Alasan / Keperluan Pengajuan</label>
        <textarea name="alasan" rows="4"
          placeholder="Jelaskan alasan kamu membutuhkan bantuan ini..."
          required><?= isset($_POST['alasan'])?htmlspecialchars($_POST['alasan']):'' ?></textarea>
      </div>

          <!-- Upload Dokumen Persyaratan -->

      <div class="field" style="margin-bottom:20px">
          <label>Upload KTP</label>
          <input type="file"
                name="ktp"
                accept=".pdf,.jpg,.jpeg,.png"
                required>
      </div>

      <div class="field" style="margin-bottom:20px">
          <label>Upload Kartu Keluarga (KK)</label>
          <input type="file"
                name="kk"
                accept=".pdf,.jpg,.jpeg,.png"
                required>
      </div>

      <div class="field" style="margin-bottom:20px">
          <label>Upload Surat Keterangan Tidak Mampu (SKTM)</label>
          <input type="file"
                name="sktm"
                accept=".pdf,.jpg,.jpeg,.png"
                required>
      </div>

      <!-- Info Pemohon -->
      <div style="background:#f8fafc;border:1px solid var(--border);border-radius:12px;padding:16px;margin-bottom:20px">
        <p style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">Data Pemohon (dari Profil)</p>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;font-size:13px">
          <div><span style="color:#94a3b8">Nama:</span><br><b><?= htmlspecialchars($_SESSION['nama']) ?></b></div>
          <div><span style="color:#94a3b8">NIK:</span><br><b><?= $nik ?: '<span style="color:#dc2626">Belum diisi</span>' ?></b></div>
          <div><span style="color:#94a3b8">Alamat:</span><br><b><?= htmlspecialchars($user['alamat']??'—') ?></b></div>
        </div>
      </div>

      <div style="display:flex;gap:12px">
        <button type="submit" name="ajukan" class="btn btn-primary" <?= empty($nik)?'disabled style="opacity:.5;cursor:not-allowed"':'' ?>>
          📤 Kirim Pengajuan
        </button>
        <a href="index.php" class="btn btn-ghost">Batal</a>
      </div>

    </form>
  </div>

</div>

<script>
function showFileName(input) {
  const label = document.getElementById('file-label');
  if (input.files && input.files[0]) {
    label.textContent = '✅ ' + input.files[0].name;
    label.style.color = '#166534';
  }
}
</script>

</body>
</html>
