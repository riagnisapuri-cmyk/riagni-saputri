<?php
session_start();
include 'config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] === 'user' || $_SESSION['role'] === 'masyarakat') {
    header("Location: masyarakat/index.php");
    exit;
}

$msg = $err = "";

if (isset($_POST['simpan'])) {
    $nama_petugas = mysqli_real_escape_string($conn, $_POST['nama_petugas']);
    $username     = mysqli_real_escape_string($conn, $_POST['username']);
    $password     = mysqli_real_escape_string($conn, $_POST['password']);

    if (!$nama_petugas || !$username || !$password) {
        $err = "Semua field wajib diisi!";
    } else {
        $cek = mysqli_query($conn, "SELECT id_petugas FROM petugas WHERE username='$username'");
        if (mysqli_num_rows($cek) > 0) {
            $err = "Username '$username' sudah digunakan.";
        } else {
            $query = mysqli_query($conn, "
                INSERT INTO petugas (nama_petugas, username, password)
                VALUES ('$nama_petugas', '$username', '$password')
            ");
            if ($query) {
                $msg = "Petugas berhasil ditambahkan.";
            } else {
                $err = mysqli_error($conn);
            }
        }
    }
}

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM petugas WHERE id_petugas='$id'");
    header("Location: petugas.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>SIBANSOS — Petugas</title>
<style>
*{ margin:0; padding:0; box-sizing:border-box; font-family:Arial; }
body{ background:#edf1f5; }

.sidebar{
    width:250px; height:100vh; background:#17375e;
    position:fixed; left:0; top:0; padding:25px;
    display:flex; flex-direction:column;
}
.sidebar h2{ color:white; text-align:center; margin-bottom:40px; font-size:35px; }
.user-info{
    color:white; text-align:center; margin-bottom:30px;
    padding:10px; background:rgba(255,255,255,0.1);
    border-radius:10px; line-height:1.8;
}
.sidebar a{
    display:block; color:white; text-decoration:none;
    padding:15px; margin-top:10px; border-radius:10px;
    transition:0.3s; font-size:20px;
}
.sidebar a:hover{ background:#2e5c92; }
.sidebar a.active{ background:#2e5c92; }
.logout{
    margin-top:auto; background:#dc2626; color:white;
    text-align:center; padding:12px; border-radius:8px;
    text-decoration:none; font-size:16px;
}

.main{ margin-left:270px; padding:30px; }

.header{
    background:white; padding:30px; border-radius:20px;
    box-shadow:0 2px 10px rgba(0,0,0,0.1); margin-bottom:30px;
}
.header h1{ font-size:40px; color:#111; }
.header p{ margin-top:8px; color:gray; font-size:18px; }

.card{
    background:white; padding:28px; border-radius:20px;
    box-shadow:0 2px 10px rgba(0,0,0,0.1); margin-bottom:24px;
}
.card h2{ color:#17375e; font-size:24px; margin-bottom:20px; }

.form-grid{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap:16px;
    margin-bottom:16px;
}

.field label{
    display:block; font-size:13px; font-weight:bold;
    color:#444; margin-bottom:6px;
}
.field input{
    width:100%; padding:12px 14px;
    border:1.5px solid #ddd; border-radius:10px;
    font-size:14px; outline:none; transition:border-color .2s;
}
.field input:focus{ border-color:#17375e; }

.btn-simpan{
    background:#17375e; color:white; border:none;
    padding:13px 28px; border-radius:10px;
    font-size:15px; font-weight:bold; cursor:pointer;
    transition:background .2s;
}
.btn-simpan:hover{ background:#2e5c92; }

.alert{
    padding:12px 16px; border-radius:10px;
    font-size:14px; margin-bottom:20px;
    display:flex; align-items:center; gap:8px;
}
.alert-success{ background:#dcfce7; border:1px solid #86efac; color:#166534; }
.alert-error  { background:#fef2f2; border:1px solid #fca5a5; color:#991b1b; }

table{ width:100%; border-collapse:collapse; }
table th{
    background:#17375e; color:white;
    padding:14px 16px; text-align:left; font-size:14px;
}
table td{
    padding:14px 16px; border-bottom:1px solid #eee;
    font-size:14px; color:#333;
}
tr:hover td{ background:#f8fafc; }

.btn-hapus{
    background:#fef2f2; color:#dc2626;
    border:1px solid #fca5a5; padding:6px 14px;
    border-radius:7px; font-size:13px; font-weight:600;
    text-decoration:none; transition:background .2s;
}
.btn-hapus:hover{ background:#fee2e2; }

.pw-masked{ font-family:monospace; color:#999; letter-spacing:2px; }

.empty-state{
    text-align:center; padding:50px 20px; color:#999;
}
.empty-state .icon{ font-size:48px; margin-bottom:12px; }
</style>
</head>
<body>

<div class="sidebar">
    <h2>SIBANSOS</h2>
    <div class="user-info">
        Selamat Datang<br>
        <b><?= htmlspecialchars($_SESSION['nama']) ?></b>
    </div>
    <a href="index.php">Dashboard</a>
    <a href="masyarakat.php">Masyarakat</a>
    <a href="bantuan.php">Bantuan Sosial</a>
    <a href="penerima.php">Penerima Bantuan</a>
    <a href="petugas.php" class="active">Petugas</a>
    <a href="logout.php" class="logout">Logout</a>
</div>

<div class="main">

    <div class="header">
        <h1>Data Petugas</h1>
        <p>Kelola akun petugas lapangan SIBANSOS</p>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($err): ?>
    <div class="alert alert-error">⚠️ <?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <!-- Form Tambah -->
    <div class="card">
        <h2>➕ Tambah Petugas</h2>
        <form method="POST">
            <div class="form-grid">
                <div class="field">
                    <label>Nama Petugas</label>
                    <input type="text" name="nama_petugas" placeholder="Nama lengkap petugas" required>
                </div>
                <div class="field">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Username login" required>
                </div>
                <div class="field">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
            </div>
            <button type="submit" name="simpan" class="btn-simpan">
                Simpan Petugas
            </button>
        </form>
    </div>

    <!-- Tabel Data -->
    <div class="card">
        <h2>📋 Daftar Petugas</h2>
        <?php
        $data = mysqli_query($conn, "SELECT * FROM petugas ORDER BY id_petugas ASC");
        $total = mysqli_num_rows($data);
        ?>
        <p style="font-size:13px;color:#999;margin-bottom:16px">
            Total: <b style="color:#17375e"><?= $total ?></b> petugas terdaftar
        </p>

        <?php if ($total > 0): ?>
        <table>
            <tr>
                <th>#</th>
                <th>Nama Petugas</th>
                <th>Username</th>
                <th>Password</th>
                <th>Aksi</th>
            </tr>
            <?php $no = 1; while ($d = mysqli_fetch_assoc($data)): ?>
            <tr>
                <td style="color:#999"><?= $no++ ?></td>
                <td><b><?= htmlspecialchars($d['nama_petugas']) ?></b></td>
                <td style="font-family:monospace"><?= htmlspecialchars($d['username']) ?></td>
                <td><span class="pw-masked">••••••••</span></td>
                <td>
                    <a href="petugas.php?hapus=<?= $d['id_petugas'] ?>"
                       class="btn-hapus"
                       onclick="return confirm('Hapus petugas <?= addslashes($d['nama_petugas']) ?>?')">
                        🗑️ Hapus
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <div class="icon">👤</div>
            <p>Belum ada petugas terdaftar.</p>
        </div>
        <?php endif; ?>
    </div>

</div>
</body>
</html>