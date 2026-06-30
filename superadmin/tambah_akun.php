<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

$msg = $err = "";

if (isset($_POST['tambah'])) {
    csrf_verify();

    $nama_raw     = trim($_POST['nama']);
    $username_raw = trim($_POST['username']);
    $nama         = mysqli_real_escape_string($conn, $nama_raw);
    $username     = mysqli_real_escape_string($conn, $username_raw);
    $password     = $_POST['password'];
    $konfirm      = $_POST['konfirm'];
    $role         = mysqli_real_escape_string($conn, $_POST['role']);

    $allowed_roles = ['superadmin','admin','petugas','masyarakat'];

    if (!$nama || !$username || !$password || !$role) {
        $err = "Semua field wajib diisi!";
    } elseif (strlen($password) < 6) {
        $err = "Password minimal 6 karakter.";
    } elseif ($password !== $konfirm) {
        $err = "Konfirmasi password tidak cocok.";
    } elseif (!in_array($role, $allowed_roles)) {
        $err = "Role tidak valid.";
    } else {
        $cek = mysqli_query($conn, "SELECT id FROM users WHERE username='$username'");
        if (mysqli_num_rows($cek) > 0) {
            $err = "Username '$username' sudah digunakan.";
        } else {
            // Pakai password_hash (bcrypt) — jauh lebih aman daripada md5.
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $q = mysqli_query($conn, "
                INSERT INTO users (nama, username, password, role)
                VALUES ('$nama', '$username', '$hash', '$role')
            ");
            if ($q) {
                $msg = "Akun berhasil dibuat! Username: <b>" . htmlspecialchars($username_raw) . "</b> | Role: <b>" . htmlspecialchars(ucfirst($role)) . "</b>";
                // Reset form
                $nama = $username = $role = "";
            } else {
                $err = "Gagal membuat akun: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Super Admin — Tambah Akun</title>
<link rel="stylesheet" href="sa_style.css">
<style>
.sa-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.span-full { grid-column: 1 / -1; }

.role-option-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}

.role-option input[type="radio"] { display: none; }

.role-option label {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 16px 12px;
  border: 1.5px solid var(--sa-border);
  border-radius: 12px;
  cursor: pointer;
  transition: border-color .2s, background .2s;
  text-align: center;
}

.role-option label:hover {
  border-color: rgba(232,184,75,0.4);
  background: rgba(232,184,75,0.05);
}

.role-option input:checked + label {
  border-color: var(--sa-gold);
  background: rgba(232,184,75,0.1);
}

.role-icon { font-size: 28px; }

.role-name {
  font-size: 12px;
  font-weight: 700;
  color: var(--sa-text);
  text-transform: uppercase;
  letter-spacing: .5px;
}

.role-desc {
  font-size: 11px;
  color: var(--sa-muted);
  line-height: 1.4;
}

.password-wrap { position: relative; }
.password-wrap input { padding-right: 44px !important; }
.toggle-pw {
  position: absolute;
  right: 12px; top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: var(--sa-muted);
  cursor: pointer;
  font-size: 16px;
  padding: 0;
}
.toggle-pw:hover { color: var(--sa-text); }

.strength-bar {
  height: 3px;
  border-radius: 3px;
  background: var(--sa-border);
  margin-top: 6px;
  overflow: hidden;
}
.strength-fill {
  height: 100%;
  border-radius: 3px;
  transition: width .3s, background .3s;
  width: 0%;
}
</style>
</head>
<body>

<?php include '_sidebar.php'; ?>

<main class="sa-main">

  <div class="sa-topbar">
    <div>
      <h1 class="sa-page-title">Tambah Akun</h1>
      <p class="sa-page-sub">Buat akun pengguna baru untuk sistem SIBANSOS</p>
    </div>
    <div class="sa-breadcrumb">
      <a href="index.php">Dashboard</a> / <a href="kelola_akun.php">Kelola Akun</a> / Tambah
    </div>
  </div>

  <?php if ($msg): ?>
  <div class="sa-alert sa-alert-success">✅ <?= $msg ?></div>
  <?php endif; ?>
  <?php if ($err): ?>
  <div class="sa-alert sa-alert-error">⚠️ <?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <div class="sa-card">
    <div class="sa-card-title">➕ Form Tambah Akun Baru</div>

    <form method="POST" autocomplete="off">
      <?= csrf_field() ?>

      <!-- Pilih Role -->
      <div class="sa-field span-full" style="margin-bottom:28px">
        <label style="margin-bottom:14px;display:block">Pilih Role Akun</label>
        <div class="role-option-grid">
          <div class="role-option">
            <input type="radio" name="role" id="r_sa" value="superadmin"
              <?= (isset($_POST['role']) && $_POST['role']==='superadmin') ? 'checked' : '' ?>>
            <label for="r_sa">
              <span class="role-icon">⭐</span>
              <span class="role-name">Super Admin</span>
              <span class="role-desc">Akses penuh ke seluruh sistem</span>
            </label>
          </div>
          <div class="role-option">
            <input type="radio" name="role" id="r_admin" value="admin"
              <?= (!isset($_POST['role']) || $_POST['role']==='admin') ? 'checked' : '' ?>>
            <label for="r_admin">
              <span class="role-icon">🛡️</span>
              <span class="role-name">Admin</span>
              <span class="role-desc">Kelola data bansos & masyarakat</span>
            </label>
          </div>
          <div class="role-option">
            <input type="radio" name="role" id="r_petugas" value="petugas"
              <?= (isset($_POST['role']) && $_POST['role']==='petugas') ? 'checked' : '' ?>>
            <label for="r_petugas">
              <span class="role-icon">📋</span>
              <span class="role-name">Petugas</span>
              <span class="role-desc">Verifikasi penerima di lapangan</span>
            </label>
          </div>
          <div class="role-option">
            <input type="radio" name="role" id="r_masyarakat" value="masyarakat"
              <?= (isset($_POST['role']) && $_POST['role']==='masyarakat') ? 'checked' : '' ?>>
            <label for="r_masyarakat">
              <span class="role-icon">🏘️</span>
              <span class="role-name">Masyarakat</span>
              <span class="role-desc">Akses terbatas untuk masyarakat umum</span>
            </label>
          </div>
        </div>
      </div>

      <div class="sa-form-grid" style="margin-bottom:20px">
        <!-- Nama -->
        <div class="sa-field">
          <label for="nama">Nama Lengkap</label>
          <input type="text" id="nama" name="nama"
                 placeholder="Masukkan nama lengkap"
                 value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : '' ?>"
                 required>
        </div>

        <!-- Username -->
        <div class="sa-field">
          <label for="username">Username / Email</label>
          <input type="text" id="username" name="username"
                 placeholder="Username unik"
                 value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                 required>
        </div>

        <!-- Password -->
        <div class="sa-field">
          <label for="password">Password</label>
          <div class="password-wrap">
            <input type="password" id="password" name="password"
                   placeholder="Min. 6 karakter"
                   oninput="checkStrength(this.value)"
                   required>
            <button type="button" class="toggle-pw" onclick="togglePw('password')">👁️</button>
          </div>
          <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
        </div>

        <!-- Konfirmasi -->
        <div class="sa-field">
          <label for="konfirm">Konfirmasi Password</label>
          <div class="password-wrap">
            <input type="password" id="konfirm" name="konfirm"
                   placeholder="Ulangi password" required>
            <button type="button" class="toggle-pw" onclick="togglePw('konfirm')">👁️</button>
          </div>
        </div>
      </div>

      <div style="display:flex;gap:12px;align-items:center">
        <button type="submit" name="tambah" class="sa-btn sa-btn-gold">
          ➕ Buat Akun Sekarang
        </button>
        <a href="kelola_akun.php" class="sa-btn sa-btn-ghost">Batal</a>
      </div>

    </form>
  </div>

</main>

<script>
function togglePw(id) {
  const inp = document.getElementById(id);
  inp.type = inp.type === 'password' ? 'text' : 'password';
}

function checkStrength(val) {
  const fill = document.getElementById('strengthFill');
  let score = 0;
  if (val.length >= 6) score++;
  if (val.length >= 10) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^a-zA-Z0-9]/.test(val)) score++;

  const pct   = [0, 20, 40, 60, 85, 100][score];
  const color = ['#ef4444','#ef4444','#f97316','#eab308','#22c55e','#22c55e'][score];
  fill.style.width = pct + '%';
  fill.style.background = color;
}
</script>

</body>
</html>