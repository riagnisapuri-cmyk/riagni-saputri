<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

/*
|--------------------------------------------------------------------------
| AKSI VERIFIKASI
|--------------------------------------------------------------------------
*/

if (isset($_GET['setuju'])) {
    $id   = (int)$_GET['setuju'];
    $stmt = mysqli_prepare($conn,
        "UPDATE pengajuan SET status='diterima', tanggal_proses=CURDATE() WHERE id_pengajuan=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    header("Location: verifikasi.php");
    exit;
}

if (isset($_GET['tolak'])) {
    $id   = (int)$_GET['tolak'];
    $stmt = mysqli_prepare($conn,
        "UPDATE pengajuan SET status='ditolak', tanggal_proses=CURDATE() WHERE id_pengajuan=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    header("Location: verifikasi.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$cari   = trim($_GET['cari'] ?? '');
$status = $_GET['status'] ?? '';

$params      = [];
$types       = '';
$where_parts = [];

if ($cari !== '') {
    $like          = "%$cari%";
    $where_parts[] = "(p.nik LIKE ? OR m.nama LIKE ?)";
    $params[]      = $like;
    $params[]      = $like;
    $types        .= 'ss';
}

if ($status !== '') {
    $where_parts[] = "p.status=?";
    $params[]      = $status;
    $types        .= 's';
}

$where = $where_parts ? 'WHERE ' . implode(' AND ', $where_parts) : '';

/*
|--------------------------------------------------------------------------
| STATISTIK
|--------------------------------------------------------------------------
*/

$pending  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM pengajuan WHERE status='pending'"));
$diterima = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM pengajuan WHERE status='diterima'"));
$ditolak  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM pengajuan WHERE status='ditolak'"));

/*
|--------------------------------------------------------------------------
| DATA — JOIN ke masyarakat agar nama tampil
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT p.*,
           m.nama          AS nama_pemohon,
           m.alamat,
           m.no_hp
    FROM   pengajuan p
    LEFT JOIN masyarakat m ON p.nik = m.nik
    $where
    ORDER  BY p.id_pengajuan DESC
";

if ($params) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $data = mysqli_stmt_get_result($stmt);
} else {
    $data = mysqli_query($conn, $sql);
    if (!$data) die("Error Query: " . mysqli_error($conn));
}

/*
|--------------------------------------------------------------------------
| HITUNG KELENGKAPAN DOKUMEN
|--------------------------------------------------------------------------
*/
function hitungKelengkapan(array $row): int
{
    $fields  = ['nik', 'alasan', 'dokumen'];
    $isi     = 0;
    foreach ($fields as $f) {
        if (!empty($row[$f])) $isi++;
    }
    return (int)round(($isi / count($fields)) * 100);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Pengajuan</title>
    <link rel="stylesheet" href="petugas_style.css">
    <style>
        .progress { width:100%; background:#e5e7eb; border-radius:20px; overflow:hidden; }
        .progress-bar { height:10px; background:#22c55e; transition:.3s; }
        .berkas a { text-decoration:none; }
        .detail-box { background:#f8fafc; border-radius:8px; padding:10px 14px; font-size:13px; line-height:1.8; margin-top:4px; }
        .detail-box b { color:#374151; }
        /* Modal */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:999; justify-content:center; align-items:center; }
        .modal-overlay.open { display:flex; }
        .modal { background:#fff; border-radius:16px; padding:28px 32px; max-width:520px; width:95%; box-shadow:0 10px 40px rgba(0,0,0,.2); }
        .modal h3 { margin:0 0 16px; font-size:18px; }
        .modal .row { display:flex; gap:8px; margin-bottom:8px; font-size:14px; }
        .modal .lbl { color:#6b7280; min-width:110px; }
        .modal .val { color:#111827; font-weight:500; }
        .modal-actions { display:flex; gap:10px; margin-top:20px; }
        .btn-close { background:#f3f4f6; color:#374151; border:none; padding:9px 18px; border-radius:8px; cursor:pointer; font-size:14px; }
    </style>
</head>
<body>

<?php include '_sidebar.php'; ?>

<div class="main">

    <div class="page-header">
        <div>
            <h1>Verifikasi Pengajuan</h1>
            <p>Kelola dan verifikasi pengajuan bantuan sosial</p>
        </div>
    </div>

    <!-- STATISTIK -->
    <div class="stats-grid">
        <div class="stat-card yellow">
            <div class="stat-icon">⏳</div>
            <div class="stat-num"><?= $pending['total'] ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon">✅</div>
            <div class="stat-num"><?= $diterima['total'] ?></div>
            <div class="stat-label">Diterima</div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon">❌</div>
            <div class="stat-num"><?= $ditolak['total'] ?></div>
            <div class="stat-label">Ditolak</div>
        </div>
    </div>

    <!-- FILTER -->
    <div class="card">
        <form method="GET">
            <div class="filter-bar">
                <input type="text" name="cari"
                       placeholder="Cari NIK atau nama..."
                       value="<?= htmlspecialchars($cari) ?>">
                <select name="status">
                    <option value="">Semua Status</option>
                    <option value="pending"   <?= $status === 'pending'   ? 'selected' : '' ?>>Pending</option>
                    <option value="diterima"  <?= $status === 'diterima'  ? 'selected' : '' ?>>Diterima</option>
                    <option value="ditolak"   <?= $status === 'ditolak'   ? 'selected' : '' ?>>Ditolak</option>
                </select>
                <button type="submit" class="btn btn-navy">🔍 Cari</button>
                <?php if ($cari || $status): ?>
                    <a href="verifikasi.php" class="btn btn-ghost">✖ Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- TABEL -->
    <div class="card">
        <div class="card-title">📄 Daftar Pengajuan</div>

        <div class="table-wrap">
            <table>
                <tr>
                    <th>ID</th>
                    <th>NIK</th>
                    <th>Nama</th>
                    <th>Alasan</th>
                    <th>Dokumen</th>
                    <th>Kelengkapan</th>
                    <th>Tgl Pengajuan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>

                <?php
                $rows = [];
                while ($d = mysqli_fetch_assoc($data)) $rows[] = $d;

                if (empty($rows)): ?>
                <tr>
                    <td colspan="9" style="text-align:center;padding:24px;">
                        Tidak ada data pengajuan
                    </td>
                </tr>
                <?php else: ?>

                <?php foreach ($rows as $d):
                    $lengkap = hitungKelengkapan($d);
                    $barColor = $lengkap >= 80 ? '#22c55e' : ($lengkap >= 40 ? '#f59e0b' : '#ef4444');
                    $safeId  = (int)$d['id_pengajuan'];
                ?>
                <tr>
                    <td><?= $safeId ?></td>
                    <td><?= htmlspecialchars($d['nik']) ?></td>
                    <td><b><?= htmlspecialchars($d['nama_pemohon'] ?? '-') ?></b></td>

                    <td style="max-width:160px;white-space:normal;font-size:13px">
                        <?= htmlspecialchars($d['alasan'] ?? '-') ?>
                    </td>

                    <td>
                        <?php if (!empty($d['dokumen'])): ?>
                            <a href="../uploads/dokumen/<?= htmlspecialchars($d['dokumen']) ?>"
                               target="_blank" class="btn btn-ghost btn-sm">
                                📎 Lihat
                            </a>
                        <?php else: ?>
                            <span style="color:#9ca3af;font-size:12px">Tidak ada</span>
                        <?php endif; ?>
                    </td>

                    <td style="min-width:140px">
                        <div class="progress">
                            <div class="progress-bar"
                                 style="width:<?= $lengkap ?>%;background:<?= $barColor ?>">
                            </div>
                        </div>
                        <div style="margin-top:4px;font-size:12px;color:#6b7280"><?= $lengkap ?>%</div>
                    </td>

                    <td style="font-size:13px;color:#6b7280">
                        <?= !empty($d['tanggal_pengajuan'])
                            ? date('d/m/Y', strtotime($d['tanggal_pengajuan']))
                            : '-' ?>
                    </td>

                    <td>
                        <?php if ($d['status'] === 'pending'): ?>
                            <span class="badge badge-pending">Pending</span>
                        <?php elseif ($d['status'] === 'diterima'): ?>
                            <span class="badge badge-diterima">Diterima</span>
                        <?php else: ?>
                            <span class="badge badge-ditolak">Ditolak</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <!-- Tombol Detail -->
                        <button class="btn btn-ghost btn-sm"
                                onclick='bukaDetail(<?= json_encode([
                                    "id"      => $safeId,
                                    "nik"     => $d['nik'],
                                    "nama"    => $d['nama_pemohon'] ?? '-',
                                    "alamat"  => $d['alamat'] ?? '-',
                                    "no_hp"   => $d['no_hp'] ?? '-',
                                    "alasan"  => $d['alasan'] ?? '-',
                                    "status"  => $d['status'],
                                    "tgl"     => $d['tanggal_pengajuan'] ?? '',
                                    "tgl_p"   => $d['tanggal_proses'] ?? '',
                                ]) ?>)'>
                            🔍 Detail
                        </button>

                        <?php if ($d['status'] === 'pending'): ?>
                            <a href="?setuju=<?= $safeId ?>"
                               class="btn btn-green btn-sm"
                               onclick="return confirm('Setujui pengajuan ini?')">✔ Setujui</a>
                            <a href="?tolak=<?= $safeId ?>"
                               class="btn btn-red btn-sm"
                               onclick="return confirm('Tolak pengajuan ini?')">✖ Tolak</a>
                        <?php else: ?>
                            <span style="color:#9ca3af;font-size:12px">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </table>
        </div>
    </div>

</div><!-- /main -->

<!-- ===== MODAL DETAIL ===== -->
<div class="modal-overlay" id="modalOverlay" onclick="if(event.target===this)tutupModal()">
    <div class="modal">
        <h3>📄 Detail Pengajuan #<span id="mId"></span></h3>
        <div class="row"><span class="lbl">NIK</span>          <span class="val" id="mNik"></span></div>
        <div class="row"><span class="lbl">Nama</span>         <span class="val" id="mNama"></span></div>
        <div class="row"><span class="lbl">Alamat</span>       <span class="val" id="mAlamat"></span></div>
        <div class="row"><span class="lbl">No HP</span>        <span class="val" id="mHp"></span></div>
        <div class="row"><span class="lbl">Alasan</span>       <span class="val" id="mAlasan"></span></div>
        <div class="row"><span class="lbl">Status</span>       <span class="val" id="mStatus"></span></div>
        <div class="row"><span class="lbl">Tgl Pengajuan</span><span class="val" id="mTgl"></span></div>
        <div class="row"><span class="lbl">Tgl Proses</span>   <span class="val" id="mTglP"></span></div>
        <div class="modal-actions">
            <button class="btn-close" onclick="tutupModal()">Tutup</button>
        </div>
    </div>
</div>

<script>
function bukaDetail(d){
    document.getElementById('mId').textContent     = d.id;
    document.getElementById('mNik').textContent    = d.nik;
    document.getElementById('mNama').textContent   = d.nama;
    document.getElementById('mAlamat').textContent = d.alamat;
    document.getElementById('mHp').textContent     = d.no_hp;
    document.getElementById('mAlasan').textContent = d.alasan;
    document.getElementById('mStatus').textContent = d.status.toUpperCase();
    document.getElementById('mTgl').textContent    = d.tgl   || '-';
    document.getElementById('mTglP').textContent   = d.tgl_p || '-';
    document.getElementById('modalOverlay').classList.add('open');
}
function tutupModal(){
    document.getElementById('modalOverlay').classList.remove('open');
}
</script>

</body>
</html>