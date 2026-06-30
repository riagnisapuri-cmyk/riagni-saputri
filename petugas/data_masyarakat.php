<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

/*
|--------------------------------------------------------------------------
| FILTER — pakai prepared statement agar aman SQL injection
|--------------------------------------------------------------------------
*/

$cari   = trim($_GET['cari'] ?? '');
$status = $_GET['status'] ?? '';

$params      = [];
$types       = '';
$where_parts = [];

if ($cari !== '') {
    $like          = "%$cari%";
    $where_parts[] = "(nama LIKE ? OR nik LIKE ?)";
    $params[]      = $like;
    $params[]      = $like;
    $types        .= 'ss';
}

if ($status !== '') {
    $where_parts[] = "status=?";
    $params[]      = $status;
    $types        .= 's';
}

$where = $where_parts ? 'WHERE ' . implode(' AND ', $where_parts) : '';

/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

$limit = 10;
$page  = max(1, (int)($_GET['page'] ?? 1));
$start = ($page - 1) * $limit;

// Total
$sqlTotal = "SELECT COUNT(*) AS total FROM masyarakat $where";
if ($params) {
    $st = mysqli_prepare($conn, $sqlTotal);
    mysqli_stmt_bind_param($st, $types, ...$params);
    mysqli_stmt_execute($st);
    $total = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($st))['total'] ?? 0);
} else {
    $total = (int)(mysqli_fetch_assoc(mysqli_query($conn, $sqlTotal))['total'] ?? 0);
}

$totalPage = max(1, (int)ceil($total / $limit));
if ($page > $totalPage) $page = $totalPage;
$start = ($page - 1) * $limit;

/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
*/

$sqlData = "SELECT * FROM masyarakat $where ORDER BY nama ASC LIMIT ?, ?";
$pData   = array_merge($params, [$start, $limit]);
$tData   = $types . 'ii';

$stData = mysqli_prepare($conn, $sqlData);
mysqli_stmt_bind_param($stData, $tData, ...$pData);
mysqli_stmt_execute($stData);
$data = mysqli_stmt_get_result($stData);

/*
|--------------------------------------------------------------------------
| STATISTIK
|--------------------------------------------------------------------------
*/
$statAktif    = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM masyarakat WHERE status='aktif'"))['c'] ?? 0);
$statNonaktif = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM masyarakat WHERE status='nonaktif'"))['c'] ?? 0);
$statTotal    = $statAktif + $statNonaktif;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Masyarakat</title>
    <link rel="stylesheet" href="petugas_style.css">
    <style>
        /* Modal */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:999; justify-content:center; align-items:center; }
        .modal-overlay.open { display:flex; }
        .modal { background:#fff; border-radius:16px; padding:28px 32px; max-width:480px; width:95%; box-shadow:0 10px 40px rgba(0,0,0,.2); }
        .modal h3 { margin:0 0 18px; font-size:18px; color:#111827; }
        .modal .row { display:flex; gap:8px; margin-bottom:10px; font-size:14px; }
        .modal .lbl { color:#6b7280; min-width:120px; }
        .modal .val { color:#111827; font-weight:600; }
        .btn-close { background:#f3f4f6; color:#374151; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; font-size:14px; }
        /* Pagination */
        .pagination { display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
        .pagination .btn { min-width:36px; text-align:center; }
    </style>
</head>
<body>

<?php include '_sidebar.php'; ?>

<div class="main">

    <div class="page-header">
        <div>
            <h1>Data Masyarakat</h1>
            <p>Kelola dan lihat data penerima bantuan sosial</p>
        </div>
        <!-- Export CSV sederhana -->
        <a href="export_masyarakat.php?cari=<?= urlencode($cari) ?>&status=<?= urlencode($status) ?>"
           class="btn btn-green">
            📥 Export CSV
        </a>
    </div>

    <!-- FILTER -->
    <div class="card">
        <form method="GET">
            <div class="filter-bar">
                <input type="text" name="cari"
                       placeholder="Cari nama atau NIK..."
                       value="<?= htmlspecialchars($cari) ?>">
                <select name="status">
                    <option value="">Semua Status</option>
                    <option value="aktif"    <?= $status === 'aktif'    ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= $status === 'nonaktif' ? 'selected' : '' ?>>Non Aktif</option>
                </select>
                <button class="btn btn-navy" type="submit">🔍 Cari</button>
                <?php if ($cari || $status): ?>
                    <a href="data_masyarakat.php" class="btn btn-ghost">✖ Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- STATISTIK -->
    <div class="stats-grid">
        <div class="stat-card green">
            <div class="stat-icon">👥</div>
            <div class="stat-num"><?= $statTotal ?></div>
            <div class="stat-label">Total Masyarakat</div>
        </div>
        <div class="stat-card navy">
            <div class="stat-icon">✅</div>
            <div class="stat-num"><?= $statAktif ?></div>
            <div class="stat-label">Aktif</div>
        </div>
        <div class="stat-card yellow">
            <div class="stat-icon">🚫</div>
            <div class="stat-num"><?= $statNonaktif ?></div>
            <div class="stat-label">Non Aktif</div>
        </div>
    </div>

    <!-- INFO HASIL PENCARIAN -->
    <?php if ($cari || $status): ?>
    <div style="padding:8px 0 4px;font-size:14px;color:#6b7280">
        Menampilkan <b><?= $total ?></b> hasil
        <?= $cari    ? 'untuk "<b>' . htmlspecialchars($cari) . '</b>"' : '' ?>
        <?= $status  ? '· status <b>' . htmlspecialchars($status) . '</b>' : '' ?>
    </div>
    <?php endif; ?>

    <!-- TABLE -->
    <div class="card">
        <div class="card-title">👥 Daftar Masyarakat
            <span style="font-size:13px;color:#6b7280;font-weight:400;margin-left:8px">
                Hal <?= $page ?> / <?= $totalPage ?>
            </span>
        </div>

        <div class="table-wrap">
            <table>
                <tr>
                    <th>#</th>
                    <th>NIK</th>
                    <th>Nama</th>
                    <th>Alamat</th>
                    <th>No HP</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>

                <?php
                $no   = $start + 1;
                $rows = [];
                while ($d = mysqli_fetch_assoc($data)) $rows[] = $d;

                if (empty($rows)): ?>
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <div class="icon">👥</div>
                            <p>Tidak ada data masyarakat</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($rows as $d): ?>
                <tr>
                    <td style="color:#9ca3af;font-size:13px"><?= $no++ ?></td>
                    <td style="font-family:monospace"><?= htmlspecialchars($d['nik']) ?></td>
                    <td><b><?= htmlspecialchars($d['nama']) ?></b></td>
                    <td style="max-width:200px;white-space:normal;font-size:13px">
                        <?= htmlspecialchars($d['alamat']) ?>
                    </td>
                    <td><?= htmlspecialchars($d['no_hp']) ?></td>
                    <td>
                        <?php if ($d['status'] === 'aktif'): ?>
                            <span class="badge badge-aktif">Aktif</span>
                        <?php else: ?>
                            <span class="badge badge-nonaktif">Non Aktif</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-ghost btn-sm"
                                onclick='bukaDetail(<?= json_encode([
                                    "nik"    => $d['nik'],
                                    "nama"   => $d['nama'],
                                    "alamat" => $d['alamat'],
                                    "no_hp"  => $d['no_hp'],
                                    "status" => $d['status'],
                                    "tgl"    => $d['tanggal_lahir'] ?? '',
                                    "pekerjaan" => $d['pekerjaan'] ?? '-',
                                    "penghasilan" => $d['penghasilan'] ?? '-',
                                ]) ?>)'>
                            🔍 Detail
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- PAGINATION -->
    <?php if ($totalPage > 1): ?>
    <div class="card" style="padding:16px">
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page-1 ?>&cari=<?= urlencode($cari) ?>&status=<?= urlencode($status) ?>"
                   class="btn btn-ghost">◀</a>
            <?php endif; ?>

            <?php
            $range = 2;
            for ($i = max(1,$page-$range); $i <= min($totalPage,$page+$range); $i++):
            ?>
            <a href="?page=<?= $i ?>&cari=<?= urlencode($cari) ?>&status=<?= urlencode($status) ?>"
               class="btn <?= $i === $page ? 'btn-navy' : 'btn-ghost' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>

            <?php if ($page < $totalPage): ?>
                <a href="?page=<?= $page+1 ?>&cari=<?= urlencode($cari) ?>&status=<?= urlencode($status) ?>"
                   class="btn btn-ghost">▶</a>
            <?php endif; ?>

            <span style="color:#9ca3af;font-size:13px;margin-left:8px">
                Total: <?= $total ?> data
            </span>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /main -->

<!-- ===== MODAL DETAIL ===== -->
<div class="modal-overlay" id="modalOverlay" onclick="if(event.target===this)tutupModal()">
    <div class="modal">
        <h3>👤 Detail Masyarakat</h3>
        <div class="row"><span class="lbl">NIK</span>         <span class="val" id="mNik"></span></div>
        <div class="row"><span class="lbl">Nama</span>        <span class="val" id="mNama"></span></div>
        <div class="row"><span class="lbl">Alamat</span>      <span class="val" id="mAlamat"></span></div>
        <div class="row"><span class="lbl">No HP</span>       <span class="val" id="mHp"></span></div>
        <div class="row"><span class="lbl">Tgl Lahir</span>   <span class="val" id="mTgl"></span></div>
        <div class="row"><span class="lbl">Pekerjaan</span>   <span class="val" id="mPekerjaan"></span></div>
        <div class="row"><span class="lbl">Penghasilan</span> <span class="val" id="mPenghasilan"></span></div>
        <div class="row"><span class="lbl">Status</span>      <span class="val" id="mStatus"></span></div>
        <div style="margin-top:20px">
            <button class="btn-close" onclick="tutupModal()">Tutup</button>
        </div>
    </div>
</div>

<script>
function bukaDetail(d){
    document.getElementById('mNik').textContent        = d.nik;
    document.getElementById('mNama').textContent       = d.nama;
    document.getElementById('mAlamat').textContent     = d.alamat;
    document.getElementById('mHp').textContent         = d.no_hp;
    document.getElementById('mTgl').textContent        = d.tgl || '-';
    document.getElementById('mPekerjaan').textContent  = d.pekerjaan || '-';
    document.getElementById('mPenghasilan').textContent= d.penghasilan || '-';
    document.getElementById('mStatus').textContent     = d.status.toUpperCase();
    document.getElementById('modalOverlay').classList.add('open');
}
function tutupModal(){
    document.getElementById('modalOverlay').classList.remove('open');
}
</script>
</body>
</html>