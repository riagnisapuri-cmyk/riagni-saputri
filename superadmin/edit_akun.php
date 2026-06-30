<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

$msg = $err = "";

// Ambil data akun
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$akun = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id='$id'"));

if (!$akun) {
    header("Location: kelola_akun.php");
    exit;
}

if (isset($_POST['update'])) {
    csrf_verify();

    $nama     = trim(mysqli_real_escape_string($conn, $_POST['nama']));
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $role     = mysqli_real_escape_string($conn, $_POST['role']);
    $password = $_POST['password'];
    $konfirm  = $_POST['konfirm'];

    $allowed_roles = ['superadmin','admin','petugas','user'];

    if (!$nama || !$username || !$role) {
        $err = "Nama, username, dan role wajib diisi!";
    } elseif (!in_array($role, $allowed_roles)) {
        $err = "Role tidak valid.";
    } else {
        // Cek username duplikat (kecuali diri sendiri)
        $cek = mysqli_query($conn, "SELECT id FROM users WHERE username='$username' AND id!='$id'");
        if (mysqli_num_rows($cek) > 0) {
            $err = "Username '$username' sudah digunakan akun lain.";
        } else {
            if ($password) {
                if (strlen($password) < 6) {
                    $err = "Password minimal 6 karakter.";
                } elseif ($password !== $konfirm) {
                    $err = "Konfirmasi password tidak cocok.";
                } else {
                    // Pakai password_hash (bcrypt) — jauh lebih aman daripada md5.
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    mysqli_query($conn, "
                        UPDATE users
                        SET nama='$nama', username='$username', password='$hash', role='$role'
                        WHERE id='$id'
                    ");
                    $msg = "Akun berhasil diperbarui (termasuk password).";
                    $akun = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id='$id'"));
                }
            } else {
                mysqli_query($conn, "
                    UPDATE users
                    SET nama='$nama', username='$username', role='$role'
                    WHERE id='$id'
                ");
                $msg = "Akun berhasil diperbarui.";
                $akun = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id='$id'"));
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Super Admin — Edit Akun</title>
<link rel="stylesheet" href="sa_style.css">
<style>
.role-option input[type="radio"] { display: none; }
.role-option-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; }
.role-option label {
  display: flex; flex-direction: column; align-items: center;
  gap: 6px; padding: 14px 10px;
  border: 1.5px solid var(--sa-border); border-radius: 12px;
  cursor: pointer; text-align: center; transition: border-color .2s, background .2s;
}
.role-option label:hover { border-color: rgba(232,184,75,0.4); background: rgba(232,184,75,0.05); }
.role-option input:checked + label { border-color: var(--sa-gold); background: rgba(232,184,75,0.1); }
.role-icon { font-size: 24px; }
.role-name { font-size: 11px; font-weight: 700; color: var(--sa-text); text-transform: uppercase; }
.password-wrap { position: relative; }
.password-wrap input { padding-right: 44px !important; }
.toggle-pw { position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
  background: none; border: none; color: var(--sa-muted); cursor: pointer; font-size: 16px; padding: 0; }
.password-hint { font-size: 11px; color: var(--sa-muted); margin-top: 5px; }
</style>
</head>
<body>

<?php include '_sidebar.php'; ?>

<main class="sa-main">

  <div class="sa-topbar">
    <div>
      <h1 class="sa-page-title">Edit Akun</h1>
      <p class="sa-page-sub">Ubah data akun: <b><?= htmlspecialchars($akun['nama']) ?></b></p>
    </div>
    <div class="sa-breadcrumb">
      <a href="index.php">Dashboard</a> / <a href="kelola_akun.php">Kelola Akun</a> / Edit
    </div>
  </div>

  <?php if ($msg): ?>
  <div class="sa-alert sa-alert-success">✅ <?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>
  <?php if ($err): ?>
  <div class="sa-alert sa-alert-error">⚠️ <?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <div class="sa-card">
    <div class="sa-card-title">✏️ Edit Data Akun</div>

    <form method="POST" autocomplete="off">
      <?= csrf_field() ?>

      <!-- Role -->
      <div class="sa-field" style="margin-bottom:28px">
        <label style="margin-bottom:14px;display:block">Role Akun</label>
        <div class="role-option-grid">
          <?php foreach (['superadmin'=>['⭐','Super Admin'],'admin'=>['🛡️','Admin'],'petugas'=>['📋','Petugas'],'user'=>['👤','User']] as $rv => [$icon, $label]): ?>
          <div class="role-option">
            <input type="radio" name="role" id="r_<?= $rv ?>" value="<?= $rv ?>"
              <?= $akun['role']===$rv ? 'checked':'' ?>>
            <label for="r_<?= $rv ?>">
              <span class="role-icon"><?= $icon ?></span>
              <span class="role-name"><?= $label ?></span>
            </label>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="sa-form-grid" style="margin-bottom:24px">
        <div class="sa-field">
          <label for="nama">Nama Lengkap</label>
          <input type="text" id="nama" name="nama"
                 value="<?= htmlspecialchars($akun['nama']) ?>" required>
        </div>
        <div class="sa-field">
          <label for="username">Username / Email</label>
          <input type="text" id="username" name="username"
                 value="<?= htmlspecialchars($akun['username']) ?>" required>
        </div>
        <div class="sa-field">
          <label for="password">Password Baru <span style="color:var(--sa-muted);font-weight:400;text-transform:none">(kosongkan jika tidak diubah)</span></label>
          <div class="password-wrap">
            <input type="password" id="password" name="password" placeholder="Masukkan password baru">
            <button type="button" class="toggle-pw" onclick="togglePw('password')">👁️</button>
          </div>
          <p class="password-hint">Min. 6 karakter</p>
        </div>
        <div class="sa-field">
          <label for="konfirm">Konfirmasi Password Baru</label>
          <div class="password-wrap">
            <input type="password" id="konfirm" name="konfirm" placeholder="Ulangi password baru">
            <button type="button" class="toggle-pw" onclick="togglePw('konfirm')">👁️</button>
          </div>
        </div>
      </div>

      <div style="display:flex;gap:12px">
        <button type="submit" name="update" class="sa-btn sa-btn-gold">💾 Simpan Perubahan</button>
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
</script>

</body>
</html>