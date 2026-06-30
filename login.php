<?php
session_start();
include 'config/koneksi.php';

if (isset($_SESSION['user_id'])) {
     $role = $_SESSION['role'] ?? 'user';
    if ($role === 'superadmin') header("Location: superadmin/index.php");
    elseif ($role === 'admin')  header("Location: admin/index.php");
    elseif ($role === 'petugas') header("Location: petugas/index.php");
    else header("Location: index.php");
    exit;
}

$error = "";

if (isset($_POST['login'])) {

    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    // PENTING: password TIDAK di-escape. Dia tidak pernah masuk ke query SQL
    // mentah, hanya dibandingkan di memori lewat password_verify()/===.
    // Kalau di-escape, password yang mengandung ' atau \ jadi salah dianggap salah.
    $password = trim($_POST['password']);

    // Cari user berdasarkan username atau NIK
    if (preg_match('/^[0-9]{16}$/', $username)) {

        $query = mysqli_query($conn, "
            SELECT * FROM users
            WHERE nik='$username'
            AND role='masyarakat'
            LIMIT 1
        ");

    } else {

        $query = mysqli_query($conn, "
            SELECT * FROM users
            WHERE username='$username'
            LIMIT 1
        ");
    }

    if (mysqli_num_rows($query) > 0) {

        $u = mysqli_fetch_assoc($query);
        $valid = false;

        // Password baru: hash bcrypt via password_hash()
        if (password_verify($password, $u['password'])) {
            $valid = true;
        }
        // Password lama: masih disimpan plain text — tetap diterima untuk
        // kompatibilitas akun lama, tapi langsung di-upgrade ke hash bcrypt
        // begitu berhasil login supaya makin aman ke depannya.
        elseif ($password === $u['password']) {
            $valid = true;
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE users SET password='$new_hash' WHERE id='".$u['id']."'");
        }

        if ($valid) {

            $_SESSION['user_id']  = $u['id'];
            $_SESSION['username'] = $u['username'];
            $_SESSION['nama']     = $u['nama'];
            $_SESSION['role']     = $u['role'];

            // Catat log login (dipasangkan dengan log logout yang sudah ada)
            $ip = $_SERVER['REMOTE_ADDR'];
            mysqli_query($conn, "
                INSERT INTO log_aktivitas (user_id, username, nama, role, aksi, ip_address)
                VALUES (
                    '".$u['id']."',
                    '".mysqli_real_escape_string($conn, $u['username'])."',
                    '".mysqli_real_escape_string($conn, $u['nama'])."',
                    '".mysqli_real_escape_string($conn, $u['role'])."',
                    'login',
                    '".mysqli_real_escape_string($conn, $ip)."'
                )
            ");

            switch ($u['role']) {
                case 'superadmin':
                    header("Location: superadmin/index.php");
                    break;

                case 'admin':
                    header("Location: admin/index.php");
                    break;

                case 'petugas':
                    header("Location: petugas/index.php");
                    break;

                case 'masyarakat':
                    header("Location: masyarakat.php");
                    break;
            }

            exit;

        } else {
            $error = "Username / NIK atau Password salah!";
        }

    } else {
        $error = "Username / NIK atau Password salah!";
    }

} 

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIBANSOS — Masuk</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Fraunces:ital,wght@0,300;0,700;1,300&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{--navy:#0b1f3a;--navy3:#1a3a6e;--gold:#c8993a;--gold2:#e8b84b;--gold3:#f5d27a;--cream:#faf8f3;--red:#c0392b;--text-dim:rgba(255,255,255,0.5);--text-mid:rgba(255,255,255,0.75);}
html,body{height:100%;overflow:hidden}
body{font-family:'Sora',sans-serif;background:var(--navy);display:flex;align-items:stretch;}
.panel-art{width:55%;position:relative;background:var(--navy);overflow:hidden;display:flex;align-items:center;justify-content:center;}
.geo-bg{position:absolute;inset:0;width:100%;height:100%;}
.art-content{position:relative;z-index:2;padding:60px;color:white;}
.garuda-emblem{width:80px;height:80px;background:linear-gradient(135deg,var(--gold) 0%,var(--gold3) 100%);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:36px;margin-bottom:32px;animation:pulse-ring 3s ease infinite;}
@keyframes pulse-ring{0%,100%{box-shadow:0 0 0 1px rgba(200,153,58,0.3),0 0 40px rgba(200,153,58,0.15)}50%{box-shadow:0 0 0 8px rgba(200,153,58,0.08),0 0 60px rgba(200,153,58,0.25)}}
.art-tag{display:inline-flex;align-items:center;gap:8px;background:rgba(200,153,58,0.12);border:1px solid rgba(200,153,58,0.3);color:var(--gold3);font-size:11px;font-weight:600;letter-spacing:2.5px;text-transform:uppercase;padding:6px 16px;border-radius:30px;margin-bottom:24px;}
.art-tag::before{content:'';width:6px;height:6px;background:var(--gold);border-radius:50%;}
.art-title{font-family:'Fraunces',serif;font-size:52px;font-weight:700;line-height:1.05;color:white;margin-bottom:8px;}
.art-title span{color:var(--gold2);font-style:italic;}
.art-subtitle{font-size:13.5px;color:var(--text-mid);line-height:1.7;max-width:340px;margin-bottom:48px;font-weight:300;}
.stats-row{display:flex;}
.stat{flex:1;padding:16px 20px;position:relative;}
.stat+.stat::before{content:'';position:absolute;left:0;top:20%;height:60%;width:1px;background:rgba(255,255,255,0.1);}
.stat-num{font-family:'Fraunces',serif;font-size:28px;font-weight:700;color:var(--gold2);display:block;line-height:1;margin-bottom:4px;}
.stat-label{font-size:11px;color:var(--text-dim);text-transform:uppercase;letter-spacing:1px;}
.panel-form{width:45%;background:var(--cream);display:flex;align-items:center;justify-content:center;padding:60px 52px;position:relative;overflow:hidden;}
.form-wrap{width:100%;max-width:380px;position:relative;z-index:1;animation:slide-up .6s cubic-bezier(.22,1,.36,1) both;}
@keyframes slide-up{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
.form-eyebrow{font-size:11px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:var(--gold);margin-bottom:10px;}
.form-heading{font-family:'Fraunces',serif;font-size:34px;font-weight:700;color:var(--navy);line-height:1.1;margin-bottom:6px;}
.form-desc{font-size:13.5px;color:#6b7280;margin-bottom:24px;line-height:1.6;}
.role-hint{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px;}
.role-badge{font-size:11px;padding:4px 10px;border-radius:20px;font-weight:600;}
.badge-sa{background:#fef3c7;color:#92400e;}
.badge-admin{background:#dbeafe;color:#1e40af;}
.badge-petugas{background:#dcfce7;color:#166534;}
.badge-user{background:#f3f4f6;color:#374151;}
.divider-line{width:100%;height:1px;background:linear-gradient(90deg,var(--gold) 0%,rgba(200,153,58,0) 60%);margin-bottom:28px;}
.error-msg{display:flex;align-items:flex-start;gap:10px;background:#fef2f2;border:1px solid #fecaca;border-left:3px solid var(--red);padding:12px 14px;border-radius:8px;margin-bottom:24px;font-size:13px;color:var(--red);animation:shake .4s ease;}
@keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-6px)}75%{transform:translateX(6px)}}
.field{margin-bottom:20px;}
.field label{display:block;font-size:12px;font-weight:600;color:var(--navy);letter-spacing:.5px;text-transform:uppercase;margin-bottom:8px;}
.field input{width:100%;padding:13px 14px;background:white;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;font-family:'Sora',sans-serif;color:var(--navy);outline:none;transition:border-color .2s,box-shadow .2s;}
.field input:focus{border-color:var(--navy3);box-shadow:0 0 0 3px rgba(26,58,110,0.08);}
.btn-submit{width:100%;padding:15px;background:var(--navy);color:white;border:none;border-radius:10px;font-size:14.5px;font-weight:600;font-family:'Sora',sans-serif;cursor:pointer;transition:transform .15s,box-shadow .2s;}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(11,31,58,0.28);}
.register-row{text-align:center;margin-top:28px;font-size:13px;color:#9ca3af;}
.register-row a{color:var(--navy);font-weight:600;text-decoration:none;border-bottom:1.5px solid var(--gold2);padding-bottom:1px;}
.footer-brand{position:absolute;bottom:24px;left:0;right:0;text-align:center;font-size:11px;color:#c4c9d4;}
</style>
</head>
<body>
<div class="panel-art">
  <svg class="geo-bg" viewBox="0 0 700 900" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
    <rect width="700" height="900" fill="#0b1f3a"/>
    <polygon points="0,0 300,0 0,250" fill="#0f2847" opacity=".9"/>
    <polygon points="700,900 700,580 400,900" fill="#0f2847" opacity=".9"/>
    <circle cx="580" cy="140" r="180" fill="none" stroke="rgba(200,153,58,0.06)" stroke-width="1"/>
    <circle cx="580" cy="140" r="120" fill="none" stroke="rgba(200,153,58,0.08)" stroke-width="1"/>
    <circle cx="580" cy="140" r="60" fill="rgba(200,153,58,0.04)"/>
    <line x1="60" y1="220" x2="280" y2="220" stroke="rgba(200,153,58,0.15)" stroke-width="1"/>
    <polygon points="0,860 80,900 0,900" fill="rgba(200,153,58,0.07)"/>
    <polygon points="620,0 700,0 700,80" fill="rgba(200,153,58,0.07)"/>
  </svg>
  <div class="art-content">
    <div class="garuda-emblem">🏛️</div>
    <div class="art-tag">Sistem Informasi</div>
    <h1 class="art-title">SI<span>BANSOS</span></h1>
    <p class="art-subtitle">Platform terintegrasi pengelolaan data dan distribusi bantuan sosial kepada masyarakat.</p>
    <div class="stats-row">
      <div class="stat"><span class="stat-num">4</span><span class="stat-label">Role Akses</span></div>
      <div class="stat"><span class="stat-num">Real</span><span class="stat-label">Time Data</span></div>
      <div class="stat"><span class="stat-num">100%</span><span class="stat-label">Terverifikasi</span></div>
    </div>
  </div>
</div>
<div class="panel-form">
  <div class="form-wrap">
    <p class="form-eyebrow">Portal Sistem</p>
    <h2 class="form-heading">Selamat Datang</h2>
    <p class="form-desc">Masuk menggunakan akun yang telah terdaftar pada sistem.</p>
    <div class="divider-line"></div>
    <?php if ($error): ?>
    <div class="error-msg"><span>⚠</span><span><?= htmlspecialchars($error) ?></span></div>
    <?php endif; ?>
    <form method="POST" autocomplete="off">

    <div class="field">
        <label>Username / NIK</label>
        <input type="text" name="username"
               placeholder="Masukkan Username atau NIK"
               required autofocus>
    </div>

    <div class="field">
        <label>Password</label>
        <input type="password" name="password"
               placeholder="Masukkan password"
               required>
    </div>

    <button type="submit" name="login" class="btn-submit">
        Masuk ke Sistem &rarr;
    </button>

</form>
    <p class="register-row">Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
  </div>
  <p class="footer-brand">© 2025 SIBANSOS · Sistem Bantuan Sosial</p>
</div>
</body>
</html>