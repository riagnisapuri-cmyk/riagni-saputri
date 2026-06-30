<?php
session_start();
include 'config/koneksi.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = $success = "";

if (isset($_POST['register'])) {
    $nama     = trim($_POST['nama']);
    $nik      = trim($_POST['nik']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $konfirm  = $_POST['konfirm'];

    // Validasi semua field wajib
    if (!$nama || !$nik || !$username || !$password || !$konfirm) {
        $error = "Semua field wajib diisi!";

    // Validasi format NIK (16 digit angka)
    } elseif (!preg_match('/^[0-9]{16}$/', $nik)) {
        $error = "NIK harus 16 digit angka.";

    // Validasi panjang password
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";

    // Validasi konfirmasi password
    } elseif ($password !== $konfirm) {
        $error = "Konfirmasi password tidak cocok.";

    } else {
        $nik_esc      = mysqli_real_escape_string($conn, $nik);
        $username_esc = mysqli_real_escape_string($conn, $username);
        $nama_esc     = mysqli_real_escape_string($conn, $nama);

        // Cek NIK sudah terdaftar
        $cek_nik = mysqli_query($conn, "SELECT id FROM users WHERE nik='$nik_esc'");
        if (mysqli_num_rows($cek_nik) > 0) {
            $error = "NIK <strong>$nik</strong> sudah terdaftar.";

        // Cek username sudah dipakai
        } else {
            $cek_user = mysqli_query($conn, "SELECT id FROM users WHERE username='$username_esc'");
            if (mysqli_num_rows($cek_user) > 0) {
                $error = "Username <strong>$username_esc</strong> sudah digunakan.";
            } else {
                // Simpan dengan password_hash (bcrypt) dan role masyarakat
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $q = mysqli_query($conn,
                    "INSERT INTO users (nama, nik, username, password, role)
                     VALUES ('$nama_esc', '$nik_esc', '$username_esc', '$hash', 'masyarakat')"
                );
                if ($q) {
                    $success = "Akun berhasil dibuat! Silakan login.";
                } else {
                    $error = "Gagal membuat akun: " . mysqli_error($conn);
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIBANSOS — Daftar Akun</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Fraunces:ital,wght@0,300;0,700;1,300&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}

:root{
  --navy:#0b1f3a;
  --navy2:#122a4f;
  --navy3:#1a3a6e;
  --gold:#c8993a;
  --gold2:#e8b84b;
  --gold3:#f5d27a;
  --cream:#faf8f3;
  --red:#c0392b;
  --green:#15803d;
}

html,body{min-height:100%;background:var(--navy)}

body{
  font-family:'Sora',sans-serif;
  display:flex;
  align-items:center;
  justify-content:center;
  min-height:100vh;
  position:relative;
  overflow-x:hidden;
  padding:40px 16px;
}

.bg-art{
  position:fixed;
  inset:0;
  pointer-events:none;
  z-index:0;
}

.card-outer{
  position:relative;
  z-index:1;
  width:100%;
  max-width:900px;
  display:grid;
  grid-template-columns:1fr 1fr;
  background:var(--cream);
  border-radius:24px;
  overflow:hidden;
  box-shadow:0 40px 100px rgba(0,0,0,0.5);
  animation:appear .7s cubic-bezier(.22,1,.36,1) both;
}

@keyframes appear{
  from{opacity:0;transform:translateY(30px) scale(.97)}
  to{opacity:1;transform:translateY(0) scale(1)}
}

/* ─── LEFT info panel ─── */
.info-panel{
  background:var(--navy);
  padding:52px 44px;
  display:flex;
  flex-direction:column;
  justify-content:space-between;
  position:relative;
  overflow:hidden;
}

.info-panel-bg{
  position:absolute;
  inset:0;
  pointer-events:none;
}

.info-top{ position:relative; z-index:1; }

.logo-box{
  display:inline-flex;
  align-items:center;
  gap:14px;
  margin-bottom:40px;
}

.logo-icon{
  width:48px;height:48px;
  background:linear-gradient(135deg,var(--gold) 0%,var(--gold3) 100%);
  border-radius:14px;
  display:flex;align-items:center;justify-content:center;
  font-size:22px;
  flex-shrink:0;
}

.logo-name{
  font-family:'Fraunces',serif;
  font-size:22px;
  font-weight:700;
  color:white;
  line-height:1;
}

.logo-sub{
  font-size:11px;
  color:rgba(255,255,255,0.45);
  display:block;
  margin-top:2px;
}

.info-title{
  font-family:'Fraunces',serif;
  font-size:30px;
  font-weight:700;
  color:white;
  line-height:1.2;
  margin-bottom:12px;
}

.info-title em{ color:var(--gold2); font-style:italic; }

.info-desc{
  font-size:13px;
  color:rgba(255,255,255,0.55);
  line-height:1.75;
  font-weight:300;
  margin-bottom:40px;
}

.checklist{
  list-style:none;
  display:flex;
  flex-direction:column;
  gap:14px;
}

.checklist li{
  display:flex;
  align-items:flex-start;
  gap:12px;
  font-size:13px;
  color:rgba(255,255,255,0.7);
  line-height:1.4;
}

.check-icon{
  width:22px;height:22px;
  background:rgba(200,153,58,0.15);
  border:1px solid rgba(200,153,58,0.3);
  border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:11px;
  flex-shrink:0;
  color:var(--gold2);
  margin-top:1px;
}

.info-footer{
  position:relative;
  z-index:1;
  font-size:11px;
  color:rgba(255,255,255,0.25);
}

/* ─── RIGHT form panel ─── */
.form-panel{
  padding:48px 48px;
  display:flex;
  flex-direction:column;
  justify-content:center;
  position:relative;
}

.form-eyebrow{
  font-size:11px;
  font-weight:600;
  letter-spacing:2.5px;
  text-transform:uppercase;
  color:var(--gold);
  margin-bottom:8px;
}

.form-heading{
  font-family:'Fraunces',serif;
  font-size:28px;
  font-weight:700;
  color:var(--navy);
  margin-bottom:4px;
}

.form-sub{
  font-size:13px;
  color:#9ca3af;
  margin-bottom:24px;
  line-height:1.5;
}

.sep{
  width:100%;
  height:1px;
  background:linear-gradient(90deg,var(--gold) 0%,rgba(200,153,58,0) 50%);
  margin-bottom:24px;
}

.alert{
  display:flex;gap:10px;align-items:flex-start;
  padding:12px 14px;border-radius:10px;
  font-size:13px;margin-bottom:20px;
  line-height:1.5;
}

.alert-error{
  background:#fef2f2;
  border:1px solid #fecaca;
  border-left:3px solid var(--red);
  color:var(--red);
}

.alert-success{
  background:#f0fdf4;
  border:1px solid #bbf7d0;
  border-left:3px solid var(--green);
  color:var(--green);
}

.alert-success a{
  color:var(--green);
  font-weight:700;
  text-decoration:underline;
}

.two-col{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:14px;
}

.field{ margin-bottom:16px; }

.field label{
  display:block;
  font-size:11.5px;
  font-weight:600;
  color:var(--navy);
  letter-spacing:.5px;
  text-transform:uppercase;
  margin-bottom:7px;
}

.input-wrap{ position:relative; }

.field input{
  width:100%;
  padding:12px 14px 12px 40px;
  background:white;
  border:1.5px solid #e5e7eb;
  border-radius:10px;
  font-size:13.5px;
  font-family:'Sora',sans-serif;
  color:var(--navy);
  outline:none;
  transition:border-color .2s,box-shadow .2s;
}

.field input::placeholder{ color:#d1d5db; }

.field input:focus{
  border-color:var(--navy3);
  box-shadow:0 0 0 3px rgba(26,58,110,0.07);
}

.ico{
  position:absolute;
  left:13px;top:50%;transform:translateY(-50%);
  font-size:15px;
  color:#d1d5db;
  pointer-events:none;
  transition:color .2s;
}

.field input:focus ~ .ico{ color:var(--navy3); }

.hint{
  font-size:11px;
  color:#c4c9d4;
  margin-top:5px;
}

.btn-daftar{
  width:100%;
  padding:14px;
  background:var(--navy);
  color:white;
  border:none;
  border-radius:10px;
  font-size:14px;
  font-weight:600;
  font-family:'Sora',sans-serif;
  cursor:pointer;
  letter-spacing:.3px;
  transition:transform .15s,box-shadow .2s;
  margin-top:6px;
}

.btn-daftar:hover{
  transform:translateY(-2px);
  box-shadow:0 10px 28px rgba(11,31,58,0.3);
}

.btn-daftar:active{ transform:translateY(0); }

.login-row{
  text-align:center;
  margin-top:20px;
  font-size:13px;
  color:#9ca3af;
}

.login-row a{
  color:var(--navy);
  font-weight:600;
  text-decoration:none;
  border-bottom:1.5px solid var(--gold2);
  padding-bottom:1px;
}

.login-row a:hover{ color:var(--gold); }

@media(max-width:700px){
  .card-outer{grid-template-columns:1fr}
  .info-panel{display:none}
  .form-panel{padding:40px 28px}
  .two-col{grid-template-columns:1fr}
}
</style>
</head>
<body>

<svg class="bg-art" viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
  <rect width="1440" height="900" fill="#0b1f3a"/>
  <circle cx="200" cy="200" r="350" fill="rgba(200,153,58,0.04)"/>
  <circle cx="1300" cy="700" r="280" fill="rgba(26,58,110,0.15)"/>
  <circle cx="1200" cy="150" r="180" fill="none" stroke="rgba(200,153,58,0.06)" stroke-width="1"/>
  <circle cx="300" cy="750" r="140" fill="none" stroke="rgba(200,153,58,0.05)" stroke-width="1"/>
</svg>

<div class="card-outer">

  <!-- Info panel kiri -->
  <div class="info-panel">
    <svg class="info-panel-bg" viewBox="0 0 430 700" xmlns="http://www.w3.org/2000/svg">
      <circle cx="380" cy="80" r="150" fill="none" stroke="rgba(200,153,58,0.07)" stroke-width="1"/>
      <circle cx="380" cy="80" r="90" fill="none" stroke="rgba(200,153,58,0.1)" stroke-width="1"/>
      <circle cx="50" cy="640" r="120" fill="none" stroke="rgba(255,255,255,0.04)" stroke-width="1"/>
      <polygon points="0,680 90,700 0,700" fill="rgba(200,153,58,0.06)"/>
      <polygon points="350,0 430,0 430,80" fill="rgba(200,153,58,0.06)"/>
    </svg>

    <div class="info-top">
      <div class="logo-box">
        <div class="logo-icon">🏛️</div>
        <div>
          <div class="logo-name">SIBANSOS</div>
          <span class="logo-sub">Sistem Bantuan Sosial</span>
        </div>
      </div>

      <h2 class="info-title">Daftar &<br><em>Akses Layanan</em></h2>
      <p class="info-desc">Buat akun masyarakat untuk mengakses informasi dan layanan bantuan sosial.</p>

      <ul class="checklist">
        <li>
          <div class="check-icon">✓</div>
          <span>Cek status pengajuan bantuan sosial</span>
        </li>
        <li>
          <div class="check-icon">✓</div>
          <span>Lihat informasi bantuan yang tersedia</span>
        </li>
        <li>
          <div class="check-icon">✓</div>
          <span>Verifikasi data penerima bantuan</span>
        </li>
        <li>
          <div class="check-icon">✓</div>
          <span>Notifikasi distribusi bantuan terbaru</span>
        </li>
      </ul>
    </div>

    <div class="info-footer">© 2025 SIBANSOS · Portal Masyarakat</div>
  </div>

  <!-- Form panel kanan -->
  <div class="form-panel">

    <p class="form-eyebrow">Pendaftaran Masyarakat</p>
    <h2 class="form-heading">Buat Akun Baru</h2>
    <p class="form-sub">Lengkapi data diri untuk mendaftarkan akun.</p>

    <div class="sep"></div>

    <?php if ($error): ?>
    <div class="alert alert-error">
      <span>⚠</span>
      <span><?= $error ?></span>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success">
      <span>✓</span>
      <span><?= $success ?> <a href="login.php">Login sekarang →</a></span>
    </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">

      <!-- Nama Lengkap -->
      <div class="field">
        <label for="nama">Nama Lengkap</label>
        <div class="input-wrap">
          <input type="text" id="nama" name="nama"
                 placeholder="Masukkan nama lengkap"
                 value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : '' ?>"
                 required>
          <span class="ico">👤</span>
        </div>
      </div>

      <!-- NIK -->
      <div class="field">
        <label for="nik">NIK (Nomor Induk Kependudukan)</label>
        <div class="input-wrap">
          <input type="text" id="nik" name="nik"
                 placeholder="16 digit NIK sesuai KTP"
                 maxlength="16"
                 pattern="[0-9]{16}"
                 value="<?= isset($_POST['nik']) ? htmlspecialchars($_POST['nik']) : '' ?>"
                 required>
          <span class="ico">🪪</span>
        </div>
        <p class="hint">Sesuai KTP, 16 digit angka</p>
      </div>

      <!-- Username -->
      <div class="field">
        <label for="username">Username</label>
        <div class="input-wrap">
          <input type="text" id="username" name="username"
                 placeholder="Pilih username unik"
                 value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                 required>
          <span class="ico">@</span>
        </div>
      </div>

      <!-- Password -->
      <div class="two-col">
        <div class="field">
          <label for="password">Password</label>
          <div class="input-wrap">
            <input type="password" id="password" name="password"
                   placeholder="Min. 6 karakter" required>
            <span class="ico">🔒</span>
          </div>
          <p class="hint">Minimal 6 karakter</p>
        </div>

        <div class="field">
          <label for="konfirm">Konfirmasi Password</label>
          <div class="input-wrap">
            <input type="password" id="konfirm" name="konfirm"
                   placeholder="Ulangi password" required>
            <span class="ico">🔒</span>
          </div>
        </div>
      </div>

      <button type="submit" name="register" class="btn-daftar">
        Daftar Sekarang &rarr;
      </button>

    </form>

    <p class="login-row">
      Sudah punya akun? <a href="login.php">Masuk di sini</a>
    </p>

  </div>
</div>

</body>
</html>