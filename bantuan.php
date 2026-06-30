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

$search = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';
$where  = $search ? "WHERE nama_bantuan LIKE '%$search%' OR jenis_bantuan LIKE '%$search%'" : '';
$data   = mysqli_query($conn,"SELECT * FROM bantuan_sosial $where ORDER BY id_bansos ASC");
$total  = mysqli_num_rows($data);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>SIBANSOS — Data Bantuan Sosial</title>
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
    background:white; padding:24px 28px; border-radius:16px;
    box-shadow:0 2px 10px rgba(0,0,0,0.08); margin-bottom:24px;
    display:flex; align-items:center; justify-content:space-between;
}
.header h1{ font-size:28px; color:#111; }
.header p{ color:gray; font-size:14px; margin-top:4px; }

.card{
    background:white; padding:24px 28px; border-radius:16px;
    box-shadow:0 2px 10px rgba(0,0,0,0.08);
}

.search-bar{ display:flex; gap:10px; margin-bottom:18px; }
.search-bar input{
    flex:1; padding:11px 16px;
    border:1.5px solid #e5e7eb; border-radius:9px;
    font-size:14px; outline:none; transition:border-color .2s;
}
.search-bar input:focus{ border-color:#17375e; }
.search-bar button{
    padding:11px 20px; background:#17375e; color:white;
    border:none; border-radius:9px; font-size:14px; font-weight:600; cursor:pointer;
}
.search-bar a{
    padding:11px 16px; background:#f3f4f6; color:#6b7280;
    border:1px solid #e5e7eb; border-radius:9px; font-size:14px;
    text-decoration:none; display:flex; align-items:center;
}

table{ width:100%; border-collapse:collapse; }
table th{
    background:#17375e; color:white;
    padding:13px 16px; text-align:left; font-size:13px;
}
table td{
    padding:13px 16px; border-bottom:1px solid #f3f4f6; font-size:14px;
}
tr:hover td{ background:#f9fafb; }

.badge{
    display:inline-block; padding:4px 10px; border-radius:20px;
    font-size:12px; font-weight:600;
    background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe;
}

.empty-state{ text-align:center; padding:50px 20px; color:#9ca3af; }
.empty-state .icon{ font-size:48px; margin-bottom:10px; }
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
    <a href="bantuan.php" class="active">Bantuan Sosial</a>
    <a href="penerima.php">Penerima Bantuan</a>
    <a href="petugas.php">Petugas</a>
    <a href="logout.php" class="logout">Logout</a>
</div>

<div class="main">

    <div class="header">
        <div>
            <h1>Data Bantuan Sosial</h1>
            <p>Informasi program bantuan sosial yang tersedia</p>
        </div>
        <div style="background:#eff6ff;border:1px solid #bfdbfe;padding:10px 18px;border-radius:10px;text-align:center">
            <div style="font-size:28px;font-weight:700;color:#1d4ed8"><?= $total ?></div>
            <div style="font-size:11px;color:#1e40af;text-transform:uppercase;letter-spacing:.5px">Program</div>
        </div>
    </div>

    <div class="card">

        <form method="GET" class="search-bar">
            <input type="text" name="q"
                   placeholder="🔍 Cari nama atau jenis bantuan..."
                   value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Cari</button>
            <?php if($search): ?>
            <a href="bantuan.php">Reset</a>
            <?php endif; ?>
        </form>

        <p style="font-size:13px;color:#6b7280;margin-bottom:14px">
            <?= $search ? "Hasil: <b>$total</b> data" : "Total <b>$total</b> program bantuan" ?>
        </p>

        <?php if($total > 0): ?>
        <table>
            <tr>
                <th>#</th>
                <th>Nama Bantuan</th>
                <th>Jenis</th>
                <th>Tanggal</th>
                <th>Jumlah Bantuan</th>
            </tr>
            <?php $no=1; while($d=mysqli_fetch_assoc($data)): ?>
            <tr>
                <td style="color:#9ca3af"><?= $no++ ?></td>
                <td><b><?= htmlspecialchars($d['nama_bantuan']) ?></b></td>
                <td><span class="badge"><?= htmlspecialchars($d['jenis_bantuan']?:'-') ?></span></td>
                <td style="font-size:13px;color:#6b7280">
                    <?= $d['tanggal_bantuan'] ? date('d/m/Y', strtotime($d['tanggal_bantuan'])) : '—' ?>
                </td>
                <td style="color:#16a34a;font-weight:700">
                    Rp <?= number_format($d['jumlah_bantuan'],0,',','.') ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <div class="icon">🎁</div>
            <p>Tidak ada program bantuan ditemukan.</p>
        </div>
        <?php endif; ?>

    </div>
</div>
</body>
</html>