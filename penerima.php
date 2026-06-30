<?php
session_start();
include 'config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Masyarakat tidak boleh akses halaman ini
if ($_SESSION['role'] === 'user' || $_SESSION['role'] === 'masyarakat') {
    header("Location: masyarakat/index.php");
    exit;
}

$msg = $err = "";

if (isset($_POST['simpan'])) {
    $nik                = mysqli_real_escape_string($conn, $_POST['nik']);
    $id_bansos          = mysqli_real_escape_string($conn, $_POST['id_bansos']);
    $id_petugas         = mysqli_real_escape_string($conn, $_POST['id_petugas']);
    $status_verifikasi  = mysqli_real_escape_string($conn, $_POST['status_verifikasi']);
    $tanggal_verifikasi = mysqli_real_escape_string($conn, $_POST['tanggal_verifikasi']);

    if (!$nik || !$id_bansos || !$id_petugas) {
        $err = "NIK, ID Bansos, dan Petugas wajib diisi!";
    } else {
        // Cek NIK ada di masyarakat
        $cek_nik = mysqli_query($conn, "SELECT nik FROM masyarakat WHERE nik='$nik'");
        if (mysqli_num_rows($cek_nik) == 0) {
            $err = "NIK '$nik' tidak ditemukan di data masyarakat.";
        } else {
            $query = mysqli_query($conn, "
                INSERT INTO penerima_bantuan
                (nik, id_bansos, id_petugas, status_verifikasi, tanggal_verifikasi)
                VALUES ('$nik', '$id_bansos', '$id_petugas', '$status_verifikasi', '$tanggal_verifikasi')
            ");
            if ($query) {
                $msg = "Data penerima berhasil ditambahkan.";
            } else {
                $err = mysqli_error($conn);
            }
        }
    }
}

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM penerima_bantuan WHERE id_penerima='$id'");
    header("Location: penerima.php");
    exit;
}

if (isset($_POST['update_status'])) {
    $id     = (int)$_POST['id_penerima'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE penerima_bantuan SET status_verifikasi='$status' WHERE id_penerima='$id'");
    $msg = "Status verifikasi berhasil diperbarui.";
}

// Ambil data dropdown
$list_masyarakat = mysqli_query($conn, "SELECT nik, nama FROM masyarakat ORDER BY nama");
$list_bansos     = mysqli_query($conn, "SELECT id_bansos, nama_bantuan FROM bantuan_sosial ORDER BY nama_bantuan");
$list_petugas    = mysqli_query($conn, "SELECT id_petugas, nama_petugas FROM petugas ORDER BY nama_petugas");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>SIBANSOS — Penerima Bantuan</title>
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
.span-full{ grid-column: 1 / -1; }

.field label{
    display:block; font-size:13px; font-weight:bold;
    color:#444; margin-bottom:6px;
}
.field input,
.field select{
    width:100%; padding:12px 14px;
    border:1.5px solid #ddd; border-radius:10px;
    font-size:14px; outline:none; transition:border-color .2s;
    background:white; appearance:none;
}
.field input:focus,
.field select:focus{ border-color:#17375e; }

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
    font-size:14px; color:#333; vertical-align:middle;
}
tr:hover td{ background:#f8fafc; }

.badge{
    display:inline-block; padding:5px 12px;
    border-radius:20px; font-size:12px; font-weight:600;
}
.badge-pending  { background:#fef9c3; color:#854d0e; border:1px solid #fde047; }
.badge-diterima { background:#dcfce7; color:#166534; border:1px solid #86efac; }
.badge-ditolak  { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }
.badge-kosong   { background:#f3f4f6; color:#6b7280; border:1px solid #d1d5db; }

.status-select{
    padding:6px 10px;
    border:1.5px solid #ddd; border-radius:7px;
    font-size:12px; outline:none; cursor:pointer;
    background:white;
}
.status-select:focus{ border-color:#17375e; }

.btn-hapus{
    background:#fef2f2; color:#dc2626;
    border:1px solid #fca5a5; padding:6px 14px;
    border-radius:7px; font-size:13px; font-weight:600;
    text-decoration:none; transition:background .2s;
}
.btn-hapus:hover{ background:#fee2e2; }

.btn-update{
    background:#eff6ff; color:#1d4ed8;
    border:1px solid #93c5fd; padding:6px 12px;
    border-radius:7px; font-size:12px; font-weight:600;
    cursor:pointer; transition:background .2s;
}
.btn-update:hover{ background:#dbeafe; }

.empty-state{
    text-align:center; padding:50px 20px; color:#999;
}
.empty-state .icon{ font-size:48px; margin-bottom:12px; }

.nama-kecil{ font-size:12px; color:#999; margin-top:2px; }
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
    <a href="penerima.php" class="active">Penerima Bantuan</a>
    <a href="petugas.php">Petugas</a>
    <a href="logout.php" class="logout">Logout</a>
</div>

<div class="main">

    <div class="header">
        <h1>Penerima Bantuan</h1>
        <p>Kelola dan verifikasi data penerima bantuan sosial</p>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($err): ?>
    <div class="alert alert-error">⚠️ <?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <!-- Form Tambah -->
    <div class="card">
        <h2>➕ Tambah Penerima Bantuan</h2>
        <form method="POST">
            <div class="form-grid">

                <div class="field">
                    <label>Masyarakat (NIK)</label>
                    <select name="nik" required>
                        <option value="">-- Pilih Masyarakat --</option>
                        <?php
                        $list_masyarakat = mysqli_query($conn, "SELECT nik, nama FROM masyarakat ORDER BY nama");
                        while ($m = mysqli_fetch_assoc($list_masyarakat)):
                        ?>
                        <option value="<?= $m['nik'] ?>">
                            <?= htmlspecialchars($m['nama']) ?> — <?= $m['nik'] ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="field">
                    <label>Program Bantuan</label>
                    <select name="id_bansos" required>
                        <option value="">-- Pilih Bantuan --</option>
                        <?php
                        $list_bansos = mysqli_query($conn, "SELECT id_bansos, nama_bantuan FROM bantuan_sosial ORDER BY nama_bantuan");
                        while ($b = mysqli_fetch_assoc($list_bansos)):
                        ?>
                        <option value="<?= $b['id_bansos'] ?>">
                            <?= htmlspecialchars($b['nama_bantuan']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="field">
                    <label>Petugas</label>
                    <select name="id_petugas" required>
                        <option value="">-- Pilih Petugas --</option>
                        <?php
                        $list_petugas = mysqli_query($conn, "SELECT id_petugas, nama_petugas FROM petugas ORDER BY nama_petugas");
                        while ($p = mysqli_fetch_assoc($list_petugas)):
                        ?>
                        <option value="<?= $p['id_petugas'] ?>">
                            <?= htmlspecialchars($p['nama_petugas']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="field">
                    <label>Status Verifikasi</label>
                    <select name="status_verifikasi">
                        <option value="pending">⏳ Pending</option>
                        <option value="diterima">✅ Diterima</option>
                        <option value="ditolak">❌ Ditolak</option>
                    </select>
                </div>

                <div class="field span-full">
                    <label>Tanggal Verifikasi</label>
                    <input type="date" name="tanggal_verifikasi" value="<?= date('Y-m-d') ?>">
                </div>

            </div>
            <button type="submit" name="simpan" class="btn-simpan">
                Simpan Data
            </button>
        </form>
    </div>

    <!-- Tabel Data -->
    <div class="card">
        <h2>📋 Data Penerima Bantuan</h2>
        <?php
        $data = mysqli_query($conn, "
            SELECT
                pb.*,
                m.nama AS nama_masyarakat,
                bs.nama_bantuan,
                pt.nama_petugas
            FROM penerima_bantuan pb
            LEFT JOIN masyarakat m ON pb.nik = m.nik
            LEFT JOIN bantuan_sosial bs ON pb.id_bansos = bs.id_bansos
            LEFT JOIN petugas pt ON pb.id_petugas = pt.id_petugas
            ORDER BY pb.id_penerima DESC
        ");
        $total = mysqli_num_rows($data);
        ?>
        <p style="font-size:13px;color:#999;margin-bottom:16px">
            Total: <b style="color:#17375e"><?= $total ?></b> data penerima
        </p>

        <?php if ($total > 0): ?>
        <div style="overflow-x:auto">
        <table>
            <tr>
                <th>#</th>
                <th>Masyarakat</th>
                <th>Program Bantuan</th>
                <th>Petugas</th>
                <th>Status</th>
                <th>Tanggal</th>
                <th>Aksi</th>
            </tr>
            <?php $no = 1; while ($d = mysqli_fetch_assoc($data)): ?>
            <tr>
                <td style="color:#999"><?= $no++ ?></td>
                <td>
                    <b><?= htmlspecialchars($d['nama_masyarakat'] ?? '-') ?></b>
                    <div class="nama-kecil"><?= $d['nik'] ?></div>
                </td>
                <td><?= htmlspecialchars($d['nama_bantuan'] ?? '-') ?></td>
                <td><?= htmlspecialchars($d['nama_petugas'] ?? '-') ?></td>
                <td>
                    <?php
                    $st = $d['status_verifikasi'];
                    if ($st === 'diterima'):
                    ?>
                    <span class="badge badge-diterima">✅ Diterima</span>
                    <?php elseif ($st === 'ditolak'): ?>
                    <span class="badge badge-ditolak">❌ Ditolak</span>
                    <?php elseif ($st === 'pending'): ?>
                    <span class="badge badge-pending">⏳ Pending</span>
                    <?php else: ?>
                    <span class="badge badge-kosong">— Belum diset</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:13px;color:#666">
                    <?= $d['tanggal_verifikasi'] && $d['tanggal_verifikasi'] !== '0000-00-00'
                        ? date('d/m/Y', strtotime($d['tanggal_verifikasi']))
                        : '—' ?>
                </td>
                <td style="display:flex;gap:8px;align-items:center">
                    <!-- Update Status -->
                    <form method="POST" style="display:flex;gap:6px;align-items:center">
                        <input type="hidden" name="id_penerima" value="<?= $d['id_penerima'] ?>">
                        <select name="status" class="status-select">
                            <option value="pending"  <?= $st==='pending' ?'selected':'' ?>>⏳ Pending</option>
                            <option value="diterima" <?= $st==='diterima'?'selected':'' ?>>✅ Diterima</option>
                            <option value="ditolak"  <?= $st==='ditolak' ?'selected':'' ?>>❌ Ditolak</option>
                        </select>
                        <button type="submit" name="update_status" class="btn-update">
                            Ubah
                        </button>
                    </form>
                    <a href="penerima.php?hapus=<?= $d['id_penerima'] ?>"
                       class="btn-hapus"
                       onclick="return confirm('Hapus data penerima ini?')">
                        🗑️
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="icon">📋</div>
            <p>Belum ada data penerima bantuan.</p>
        </div>
        <?php endif; ?>
    </div>

</div>
</body>
</html>