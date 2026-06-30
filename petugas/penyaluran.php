<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

$msg = '';
$err = '';

/*
|--------------------------------------------------------------------------
| SIMPAN (INSERT)  — hanya satu blok, bug duplikat dihapus
|--------------------------------------------------------------------------
*/
if (isset($_POST['simpan'])) {
    $id_masyarakat = mysqli_real_escape_string($conn, $_POST['id_masyarakat']);
    $id_bansos     = (int)$_POST['id_bansos'];
    $jumlah        = (int)$_POST['jumlah'];
    $tanggal       = $_POST['tanggal'];
    $status        = in_array($_POST['status'], ['belum_disalurkan','sudah_disalurkan'])
                     ? $_POST['status'] : 'belum_disalurkan';

    $insert = mysqli_query($conn, "
        INSERT INTO penyaluran_bantuan
            (id_masyarakat, id_bansos, jumlah, tanggal_penyaluran, status)
        VALUES
            ('$id_masyarakat','$id_bansos','$jumlah','$tanggal','$status')
    ");

    if ($insert) {
        $msg = "Data penyaluran berhasil ditambahkan";
    } else {
        $err = "Gagal menyimpan: " . mysqli_error($conn);
    }
}

/*
|--------------------------------------------------------------------------
| UPDATE STATUS (mark sudah/belum)
|--------------------------------------------------------------------------
*/
if (isset($_GET['tandai'])) {
    $id      = (int)$_GET['tandai'];
    $newStat = $_GET['s'] === 'sudah' ? 'sudah_disalurkan' : 'belum_disalurkan';
    mysqli_query($conn, "UPDATE penyaluran_bantuan SET status='$newStat' WHERE id_penyaluran=$id");
    header("Location: penyaluran.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| HAPUS
|--------------------------------------------------------------------------
*/
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM penyaluran_bantuan WHERE id_penyaluran=$id");
    header("Location: penyaluran.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| STATISTIK
|--------------------------------------------------------------------------
*/
function hitungBansos($conn, $nama)
{
    $q = mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM   penyaluran_bantuan pb
        JOIN   bantuan_sosial bs ON pb.id_bansos = bs.id_bansos
        WHERE  bs.nama_bantuan = '" . mysqli_real_escape_string($conn, $nama) . "'
    ");
    return (int)(mysqli_fetch_assoc($q)['total'] ?? 0);
}

$statPKH   = hitungBansos($conn, 'PKH');
$statBPNT  = hitungBansos($conn, 'BPNT');
$statBLT   = hitungBansos($conn, 'BLT');
$statTotal = (int)(mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM penyaluran_bantuan"))['total'] ?? 0);

$statSudah = (int)(mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM penyaluran_bantuan WHERE status='sudah_disalurkan'"))['total'] ?? 0);
$ms = mysqli_query($conn, "

SELECT
    p.nik,
    m.nama,
    p.id_bansos,
    bs.nama_bantuan
FROM pengajuan p
JOIN masyarakat m
    ON p.nik = m.nik
JOIN bantuan_sosial bs
    ON p.id_bansos = bs.id_bansos
WHERE p.status='diterima'
ORDER BY m.nama
");

/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/
$filterStatus = $_GET['status'] ?? '';
$filterCari   = trim($_GET['cari'] ?? '');
$whereArr     = [];

if ($filterStatus !== '') $whereArr[] = "pb.status='".mysqli_real_escape_string($conn,$filterStatus)."'";
if ($filterCari   !== '') $whereArr[] = "(m.nama LIKE '%".mysqli_real_escape_string($conn,$filterCari)."%' OR m.nik LIKE '%".mysqli_real_escape_string($conn,$filterCari)."%')";

$whereClause = $whereArr ? 'WHERE ' . implode(' AND ', $whereArr) : '';

/*
|--------------------------------------------------------------------------
| DATA TABLE
|--------------------------------------------------------------------------
*/
$data = mysqli_query($conn, "
    SELECT  pb.id_penyaluran,
            pb.jumlah,
            pb.tanggal_penyaluran,
            pb.status,
            m.nama  AS nama_penerima,
            m.nik,
            bs.nama_bantuan
    FROM    penyaluran_bantuan pb
    LEFT JOIN masyarakat    m  ON pb.id_masyarakat = m.nik
    LEFT JOIN bantuan_sosial bs ON pb.id_bansos     = bs.id_bansos
    $whereClause
    ORDER BY pb.id_penyaluran DESC
");
if (!$data) die("ERROR DATA: " . mysqli_error($conn));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Penyaluran Bantuan</title>
    <link rel="stylesheet" href="petugas_style.css">
    <style>
        .progress-mini { width:80px; height:8px; background:#e5e7eb; border-radius:20px; overflow:hidden; display:inline-block; vertical-align:middle; }
        .progress-mini-bar { height:100%; background:#22c55e; }
    </style>
</head>
<body>

<?php include '_sidebar.php'; ?>

<div class="main">

    <div class="page-header">
        <div>
            <h1>Penyaluran Bantuan</h1>
            <p>Kelola data bantuan sosial kepada masyarakat</p>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($err): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <!-- STATISTIK -->
    <div class="stats-grid">
        <div class="stat-card green">
            <div class="stat-icon">🎁</div>
            <div class="stat-num"><?= $statPKH ?></div>
            <div class="stat-label">PKH</div>
        </div>
        <div class="stat-card navy">
            <div class="stat-icon">🛒</div>
            <div class="stat-num"><?= $statBPNT ?></div>
            <div class="stat-label">BPNT</div>
        </div>
        <div class="stat-card yellow">
            <div class="stat-icon">💵</div>
            <div class="stat-num"><?= $statBLT ?></div>
            <div class="stat-label">BLT</div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon">📊</div>
            <div class="stat-num"><?= $statTotal ?></div>
            <div class="stat-label">Total</div>
        </div>
    </div>

    <!-- PROGRESS PENYALURAN -->
    <?php if ($statTotal > 0): $pct = round($statSudah / $statTotal * 100); ?>
    <div class="card" style="padding:16px 20px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
            <span style="font-weight:600;font-size:14px">Progress Penyaluran</span>
            <span style="font-size:14px;color:#22c55e;font-weight:700"><?= $pct ?>%</span>
        </div>
        <div class="progress" style="height:14px">
            <div class="progress-bar" style="width:<?= $pct ?>%;height:100%"></div>
        </div>
        <div style="margin-top:6px;font-size:12px;color:#6b7280">
            <?= $statSudah ?> dari <?= $statTotal ?> bantuan sudah disalurkan
        </div>
    </div>
    <?php endif; ?>

    <!-- FORM TAMBAH -->
    <div class="card">
        <div class="card-title">➕ Tambah Data Penyaluran</div>

        <form method="POST">
            <div class="form-grid">

                <div class="field">
                  <div class="field">
    <label>Jenis Bantuan</label>

    <select name="id_bansos" required>
        <option value="">-- Pilih Bantuan --</option>

        <?php
        $bs = mysqli_query($conn, "SELECT * FROM bantuan_sosial");
        while($b = mysqli_fetch_assoc($bs)):
        ?>
            <option value="<?= $b['id_bansos']; ?>">
                <?= htmlspecialchars($b['nama_bantuan']); ?>
            </option>
        <?php endwhile; ?>

    </select>
</div>
                </div>
                <div class="field">
                   <div class="field">
                </div>

                <div class="field">
                    <label>Jumlah (Rp)</label>
                    <input type="number" name="jumlah" min="0" step="1000"
                           placeholder="0" required>
                </div>

                <div class="field">
                    <label>Tanggal Penyaluran</label>
                    <input type="date" name="tanggal"
                           value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="field">
                    <label>Status</label>
                    <select name="status">
                        <option value="belum_disalurkan">Belum Disalurkan</option>
                        <option value="sudah_disalurkan">Sudah Disalurkan</option>
                    </select>
                </div>

            </div>

            <button type="submit" name="simpan" class="btn btn-green">
                💾 Simpan Data
            </button>
        </form>
    </div>

    <!-- FILTER TABLE -->
    <div class="card">
        <form method="GET">
            <div class="filter-bar">
                <input type="text" name="cari"
                       placeholder="Cari nama / NIK..."
                       value="<?= htmlspecialchars($filterCari) ?>">
                <select name="status">
                    <option value="">Semua Status</option>
                    <option value="sudah_disalurkan" <?= $filterStatus==='sudah_disalurkan'?'selected':'' ?>>Sudah</option>
                    <option value="belum_disalurkan" <?= $filterStatus==='belum_disalurkan'?'selected':'' ?>>Belum</option>
                </select>
                <button type="submit" class="btn btn-navy">🔍 Cari</button>
                <?php if ($filterCari || $filterStatus): ?>
                    <a href="penyaluran.php" class="btn btn-ghost">✖ Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- TABLE -->
    <div class="card">
        <div class="card-title">📋 Data Penyaluran</div>

        <div class="table-wrap">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Penerima</th>
                    <th>NIK</th>
                    <th>Jenis</th>
                    <th>Jumlah</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>

                <?php
                $rows = [];
                while ($d = mysqli_fetch_assoc($data)) $rows[] = $d;

                if (empty($rows)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;padding:24px;color:#9ca3af">
                        Belum ada data penyaluran
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($rows as $d): $id = (int)$d['id_penyaluran']; ?>
                <tr>
                    <td><?= $id ?></td>
                    <td><b><?= htmlspecialchars($d['nama_penerima'] ?? 'Tidak Ditemukan') ?></b></td>
                    <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($d['nik'] ?? '') ?></td>
                    <td><?= htmlspecialchars($d['nama_bantuan'] ?? '-') ?></td>
                    <td>Rp <?= number_format((int)$d['jumlah'], 0, ',', '.') ?></td>
                    <td><?= date('d/m/Y', strtotime($d['tanggal_penyaluran'])) ?></td>
                    <td>
                        <?php if ($d['status'] === 'sudah_disalurkan'): ?>
                            <span class="badge badge-diterima">✅ Sudah</span>
                        <?php else: ?>
                            <span class="badge badge-pending">⏳ Belum</span>
                        <?php endif; ?>
                    </td>
                    <td style="white-space:nowrap">
                        <!-- Toggle status -->
                        <?php if ($d['status'] === 'belum_disalurkan'): ?>
                            <a href="?tandai=<?= $id ?>&s=sudah"
                               class="btn btn-green btn-sm"
                               onclick="return confirm('Tandai sudah disalurkan?')">
                                ✔ Salurkan
                            </a>
                        <?php else: ?>
                            <a href="?tandai=<?= $id ?>&s=belum"
                               class="btn btn-yellow btn-sm"
                               onclick="return confirm('Tandai belum disalurkan?')">
                                ↩ Batalkan
                            </a>
                        <?php endif; ?>
                        <a href="?hapus=<?= $id ?>"
                           class="btn btn-red btn-sm"
                           onclick="return confirm('Hapus data ini?')">
                            🗑️ Hapus
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
</body>
</html>