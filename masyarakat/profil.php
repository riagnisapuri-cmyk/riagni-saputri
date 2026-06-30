<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

$uid = $_SESSION['user_id'];
$msg = $err = "";

// Ambil data user
$user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM users WHERE id='$uid'"));

// Update profil
if (isset($_POST['update'])) {
    $nama         = mysqli_real_escape_string($conn, trim($_POST['nama']));
    $nik          = mysqli_real_escape_string($conn, trim($_POST['nik']));
    $no_hp        = mysqli_real_escape_string($conn, trim($_POST['no_hp']));
    $alamat       = mysqli_real_escape_string($conn, trim($_POST['alamat']));
    $pekerjaan    = mysqli_real_escape_string($conn, trim($_POST['pekerjaan']));
    $penghasilan  = mysqli_real_escape_string($conn, trim($_POST['penghasilan']));
    $tanggungan   = (int)$_POST['tanggungan'];
    $status_rumah = mysqli_real_escape_string($conn, trim($_POST['status_rumah']));
    $foto         = $user['foto'];

    // Upload foto
    if (!empty($_FILES['foto']['name'])) {
        $ext   = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $allow = ['jpg','jpeg','png','webp'];
        if (!in_array(strtolower($ext), $allow)) {
            $err = "Format foto tidak didukung (jpg/jpeg/png/webp).";
        } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
            $err = "Ukuran foto maksimal 2MB.";
        } else {
            $namaFoto = time() . "_" . $uid . "." . $ext;
            $uploadDir = "../uploads/profil/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            move_uploaded_file($_FILES['foto']['tmp_name'], $uploadDir . $namaFoto);
            if ($user['foto'] && file_exists($uploadDir . $user['foto'])) {
                unlink($uploadDir . $user['foto']);
            }
            $foto = $namaFoto;
        }
    }

    if (!$err) {
        if (!$nama) {
            $err = "Nama wajib diisi!";
        } elseif ($nik && !preg_match('/^[0-9]{16}$/', $nik)) {
            $err = "NIK harus 16 digit angka.";
        } else {
            if ($nik) {
                $cek_nik = mysqli_query($conn,"SELECT id FROM users WHERE nik='$nik' AND id!='$uid'");
                if (mysqli_num_rows($cek_nik) > 0) {
                    $err = "NIK '$nik' sudah digunakan akun lain.";
                }
            }
            if (!$err) {
                mysqli_query($conn,"UPDATE users SET
                    nama='$nama', nik='$nik', no_hp='$no_hp', alamat='$alamat',
                    pekerjaan='$pekerjaan', penghasilan='$penghasilan',
                    tanggungan='$tanggungan', status_rumah='$status_rumah',
                    foto='$foto'
                    WHERE id='$uid'");

                switch ($penghasilan) {
                    case "<500.000":           $penghasilan_db = 500000;  break;
                    case "500.000-1.000.000":  $penghasilan_db = 1000000; break;
                    case "1.000.000-2.000.000":$penghasilan_db = 2000000; break;
                    case ">2.000.000":         $penghasilan_db = 2500000; break;
                    default:                   $penghasilan_db = 0;
                }

                mysqli_query($conn,"INSERT INTO masyarakat
                    (nik,nama,alamat,pekerjaan,penghasilan,tanggungan,no_hp,status_rumah)
                    VALUES ('$nik','$nama','$alamat','$pekerjaan',$penghasilan_db,'$tanggungan','$no_hp','$status_rumah')
                    ON DUPLICATE KEY UPDATE
                    nama='$nama', alamat='$alamat', pekerjaan='$pekerjaan',
                    penghasilan=$penghasilan_db, tanggungan='$tanggungan',
                    no_hp='$no_hp', status_rumah='$status_rumah'");

                $_SESSION['nama'] = $nama;
                $_SESSION['foto'] = $foto;
                $msg = "Profil berhasil diperbarui.";
            }
        }
    }
    // Refresh data setelah update
    $user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM users WHERE id='$uid'"));
}

// ── Ganti Password ── FIX: pakai password_verify + password_hash
if (isset($_POST['ganti_pw'])) {
    $pw_lama    = $_POST['pw_lama'];
    $pw_baru    = trim($_POST['pw_baru']);
    $pw_konfirm = trim($_POST['pw_konfirm']);

    // Dukung password lama (plain text lama) DAN password baru (bcrypt)
    $pw_cocok = password_verify($pw_lama, $user['password'])
             || ($pw_lama === $user['password']);

    if (!$pw_cocok) {
        $err = "Password lama tidak sesuai.";
    } elseif (strlen($pw_baru) < 6) {
        $err = "Password baru minimal 6 karakter.";
    } elseif ($pw_baru !== $pw_konfirm) {
        $err = "Konfirmasi password tidak cocok.";
    } else {
        // Simpan dengan bcrypt
        $hash_baru = password_hash($pw_baru, PASSWORD_DEFAULT);
        $hash_esc  = mysqli_real_escape_string($conn, $hash_baru);
        mysqli_query($conn,"UPDATE users SET password='$hash_esc' WHERE id='$uid'");
        $msg  = "Password berhasil diganti.";
        $user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM users WHERE id='$uid'"));
    }
}

// Foto untuk topbar & form
$foto_src = !empty($user['foto'])
    ? "../uploads/profil/" . htmlspecialchars($user['foto'])
    : null;
$initial = strtoupper(substr($user['nama'] ?? 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>SIBANSOS — Profil</title>
<link rel="stylesheet" href="ms_style.css">
<style>
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
.topbar-right { display: flex; align-items: center; gap: 16px; }
.top-icon {
    width: 38px; height: 38px;
    border-radius: 50%;
    background: #f1f5f9;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; cursor: pointer;
    border: 1px solid #e2e8f0;
}
.profile-mini { display: flex; align-items: center; gap: 10px; }
.profile-mini img,
.profile-mini .avatar-inline {
    width: 38px; height: 38px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e2e8f0;
}
.avatar-inline {
    background: linear-gradient(135deg,#17375e,#2e5c92);
    display: flex; align-items: center; justify-content: center;
    font-size:16px; font-weight:700; color:#fff;
}
.profile-mini .name { font-size:13px; font-weight:600; color:#0f172a; }
.profile-mini .role { font-size:11px; color:#94a3b8; }

..foto-wrap {
    position: relative;
    width: 100px; height: 100px;
    margin: 0 auto 16px;
    cursor: pointer;
    overflow: hidden; 
}

.avatar-big {
    background: linear-gradient(135deg,#17375e,#2e5c92);
    display: flex; align-items: center; justify-content: center;
    font-size: 36px; font-weight: 700; color: #fff;
    width: 100px;  
    height: 100px; 
    border-radius: 50%;
    line-height: 1; 
}
.foto-overlay {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: rgba(0,0,0,.45);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: .3s;
    color: #fff;
    font-size: 11px;
    font-weight: 600;
    gap: 4px;
    z-index: 2; /* ← tambah ini */
}

.foto-wrap {
    position: relative;
    width: 100px; height: 100px;
    margin: 0 auto 16px;
    cursor: pointer;
    overflow: hidden;
    border-radius: 50%; /* ← tambah ini */
}
.foto-wrap:hover .foto-overlay { opacity: 1; }
.foto-wrap:hover img,
.foto-wrap:hover .avatar-big   { filter: brightness(.7); }
#inputFoto { display: none; }
.foto-preview-badge {
    display: none; margin: 8px auto 0;
    background: #eff6ff; color: #1d4ed8;
    border: 1px solid #bfdbfe; border-radius: 20px;
    padding: 3px 12px; font-size: 11px; font-weight: 600;
    text-align: center; width: fit-content;
}

.info-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 11px 0; border-bottom: 1px solid #f1f5f9;
    gap: 12px;
}
.info-row:last-child { border-bottom: none; }
.info-label { font-size: 13px; color: #64748b; flex-shrink: 0; }
.info-value { font-size: 13px; font-weight: 600; color: #0f172a; text-align: right; word-break: break-all; }
.info-value.empty { color: #dc2626; font-weight: 400; }

.pw-wrap { position: relative; }
.pw-wrap input { padding-right: 44px !important; }
.toggle-pw {
    position: absolute; right: 12px; top: 50%;
    transform: translateY(-50%);
    background: none; border: none; cursor: pointer;
    color: #94a3b8; font-size: 16px; padding: 0;
}
</style>
</head>
<body>

<?php include '_sidebar.php'; ?>

<div class="main">

  <div class="topbar">
    <div>
      <h1>👤 Profil Saya</h1>
      <p>Kelola informasi akun dan data diri kamu</p>
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
          <div class="name"><?= htmlspecialchars($user['nama']) ?></div>
          <div class="role">Masyarakat</div>
        </div>
      </div>
    </div>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-success" style="margin:0 28px 16px">✅ <?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>
  <?php if ($err): ?>
  <div class="alert alert-error" style="margin:0 28px 16px">⚠️ <?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <div style="padding:0 28px 28px;display:grid;grid-template-columns:1fr 1fr;gap:20px">

    <!-- KIRI: Edit Data Diri -->
    <div class="card">
      <div class="card-title">✏️ Edit Data Diri</div>

      <form method="POST" enctype="multipart/form-data">

        <div style="text-align:center;margin-bottom:24px">
          <div class="foto-wrap" onclick="document.getElementById('inputFoto').click()">
            <?php if ($foto_src): ?>
              <img src="<?= $foto_src ?>" id="fotoPreview" alt="Foto Profil">
            <?php else: ?>
              <div class="avatar-big" id="fotoPreview"><?= $initial ?></div>
            <?php endif; ?>
            <div class="foto-overlay"><span>📷</span>Ganti Foto</div>
          </div>
          <input type="file" name="foto" id="inputFoto" accept="image/*" onchange="previewFoto(this)">
          <div class="foto-preview-badge" id="fotoBadge">📎 Foto dipilih — klik Simpan</div>
          <div style="font-size:13px;color:#64748b;margin-top:6px"><?= htmlspecialchars($user['username']) ?></div>
          <div style="margin-top:4px">
            <span style="background:#eff6ff;color:#1d4ed8;padding:2px 10px;border-radius:6px;font-size:11px;font-weight:600">👤 Masyarakat</span>
          </div>
        </div>

        <div class="field" style="margin-bottom:14px">
          <label>Nama Lengkap</label>
          <input type="text" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" required>
        </div>
        <div class="field" style="margin-bottom:14px">
          <label>NIK <span style="font-weight:400;text-transform:none;color:#94a3b8">(wajib untuk pengajuan bantuan)</span></label>
          <input type="text" name="nik" value="<?= htmlspecialchars($user['nik'] ?? '') ?>" placeholder="16 digit NIK" maxlength="16" pattern="[0-9]{16}">
        </div>
        <div class="field" style="margin-bottom:14px">
          <label>No. HP / WhatsApp</label>
          <input type="text" name="no_hp" value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>" placeholder="08xxxxxxxxxx">
        </div>
        <div class="field" style="margin-bottom:14px">
          <label>Alamat Lengkap</label>
          <textarea name="alamat" rows="3"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
        </div>
        <div class="field" style="margin-bottom:14px">
          <label>Pekerjaan</label>
          <select name="pekerjaan">
            <option value="">-- Pilih --</option>
            <?php foreach(['Buruh Harian','Petani','Nelayan','Tidak Bekerja','Lainnya'] as $opt): ?>
            <option <?= ($user['pekerjaan'] == $opt) ? 'selected' : '' ?>><?= $opt ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field" style="margin-bottom:14px">
          <label>Penghasilan per Bulan</label>
          <select name="penghasilan">
            <option value="">-- Pilih --</option>
            <?php
              $ph_opts = [
                '<500.000'           => '< Rp500.000',
                '500.000-1.000.000'  => 'Rp500.000 – Rp1.000.000',
                '1.000.000-2.000.000'=> 'Rp1.000.000 – Rp2.000.000',
                '>2.000.000'         => '> Rp2.000.000',
              ];
              foreach($ph_opts as $val=>$label):
            ?>
            <option value="<?= $val ?>" <?= ($user['penghasilan'] == $val) ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field" style="margin-bottom:14px">
          <label>Jumlah Tanggungan</label>
          <input type="number" name="tanggungan" value="<?= (int)($user['tanggungan'] ?? 0) ?>" min="0">
        </div>
        <div class="field" style="margin-bottom:20px">
          <label>Status Rumah</label>
          <select name="status_rumah">
            <option value="">-- Pilih --</option>
            <?php foreach(['Milik Sendiri','Kontrak','Menumpang'] as $opt): ?>
            <option <?= ($user['status_rumah'] == $opt) ? 'selected' : '' ?>><?= $opt ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <button type="submit" name="update" class="btn btn-navy" style="width:100%">💾 Simpan Perubahan</button>
      </form>
    </div>

    <!-- KANAN -->
    <div style="display:flex;flex-direction:column;gap:20px">

      <!-- Informasi Akun -->
      <div class="card">
        <div class="card-title">📊 Informasi Akun</div>
        <?php
          $info_items = [
            'Username'   => $user['username']   ?? '—',
            'NIK'        => $user['nik']         ?: '__EMPTY__',
            'No. HP'     => $user['no_hp']       ?: '__EMPTY__',
            'Alamat'     => $user['alamat']      ?: '__EMPTY__',
            'Pekerjaan'  => $user['pekerjaan']   ?: '__EMPTY__',
            'Penghasilan'=> $user['penghasilan'] ?: '__EMPTY__',
          ];
          foreach($info_items as $label => $val):
            $isEmpty = ($val === '__EMPTY__');
            if ($isEmpty) $val = 'Belum diisi';
        ?>
        <div class="info-row">
          <span class="info-label"><?= $label ?></span>
          <span class="info-value <?= $isEmpty ? 'empty' : '' ?>"><?= htmlspecialchars($val) ?></span>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Ganti Password — FIX: password_verify + password_hash -->
      <div class="card">
        <div class="card-title">🔒 Ganti Password</div>
        <form method="POST">
          <div class="field" style="margin-bottom:14px">
            <label>Password Lama</label>
            <div class="pw-wrap">
              <input type="password" name="pw_lama" placeholder="Password saat ini" required>
              <button type="button" class="toggle-pw" onclick="togglePw(this)">👁️</button>
            </div>
          </div>
          <div class="field" style="margin-bottom:14px">
            <label>Password Baru</label>
            <div class="pw-wrap">
              <input type="password" name="pw_baru" placeholder="Min. 6 karakter" required>
              <button type="button" class="toggle-pw" onclick="togglePw(this)">👁️</button>
            </div>
          </div>
          <div class="field" style="margin-bottom:20px">
            <label>Konfirmasi Password Baru</label>
            <div class="pw-wrap">
              <input type="password" name="pw_konfirm" placeholder="Ulangi password baru" required>
              <button type="button" class="toggle-pw" onclick="togglePw(this)">👁️</button>
            </div>
          </div>
          <button type="submit" name="ganti_pw" class="btn btn-navy" style="width:100%">🔒 Ganti Password</button>
        </form>
      </div>

    </div>
  </div>
</div>

<script>
function previewFoto(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      const wrap = document.querySelector('.foto-wrap');
      let preview = document.getElementById('fotoPreview');
      if (preview.tagName === 'DIV') {
        const img = document.createElement('img');
        img.id = 'fotoPreview';
        img.style.cssText = 'width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid #e2e8f0;display:block;';
        wrap.replaceChild(img, preview);
        preview = img;
      }
      preview.src = e.target.result;
      const topbarImg = document.querySelector('.profile-mini img');
      if (topbarImg) topbarImg.src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
    document.getElementById('fotoBadge').style.display = 'block';
  }
}

function togglePw(btn) {
  const input = btn.previousElementSibling;
  input.type = input.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>