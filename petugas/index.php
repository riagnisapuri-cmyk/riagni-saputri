<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

/*
|--------------------------------------------------------------------------
| STATISTIK PENGAJUAN
|--------------------------------------------------------------------------
*/
function qCount($conn, $table, $where = '')
{
    $q = mysqli_query($conn, "SELECT COUNT(*) total FROM $table" . ($where ? " WHERE $where" : ''));
    return (int)(mysqli_fetch_assoc($q)['total'] ?? 0);
}

$pending    = qCount($conn, 'pengajuan', "status='pending'");
$diterima   = qCount($conn, 'pengajuan', "status='diterima'");
$ditolak    = qCount($conn, 'pengajuan', "status='ditolak'");
$masyarakat = qCount($conn, 'masyarakat');
$surveyHari = qCount($conn, 'survey',    "tanggal_survey = CURDATE()");
$belumSalur = qCount($conn, 'penyaluran_bantuan', "status='belum_disalurkan'");

/*
|--------------------------------------------------------------------------
| PENGAJUAN TERBARU (5 terakhir, JOIN nama)
|--------------------------------------------------------------------------
*/
$pengajuan = mysqli_query($conn, "
    SELECT p.id_pengajuan, p.nik, p.status, p.tanggal_pengajuan,
           m.nama AS nama_pemohon
    FROM   pengajuan p
    LEFT JOIN masyarakat m ON p.nik = m.nik
    ORDER BY p.id_pengajuan DESC
    LIMIT 5
");
if (!$pengajuan) die("Error Query: " . mysqli_error($conn));

/*
|--------------------------------------------------------------------------
| DATA CHART — pengajuan 6 bulan terakhir
|--------------------------------------------------------------------------
*/
$chartData = [];
for ($i = 5; $i >= 0; $i--) {
    $bulan  = date('Y-m', strtotime("-$i months"));
    $label  = date('M Y', strtotime("-$i months"));
    $q      = mysqli_query($conn, "SELECT COUNT(*) c FROM pengajuan WHERE DATE_FORMAT(tanggal_pengajuan,'%Y-%m')='$bulan'");
    $chartData[] = ['label' => $label, 'val' => (int)(mysqli_fetch_assoc($q)['c'] ?? 0)];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Petugas</title>
    <link rel="stylesheet" href="petugas_style.css">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }
        .quick-actions { display:flex; gap:10px; flex-wrap:wrap; }
        .announcement {
            border-left: 4px solid #22c55e;
            padding: 14px 16px;
            border-radius: 10px;
            background: #f8fafc;
            margin-bottom: 12px;
        }
        .announcement h4 { margin: 0 0 6px; font-size:14px; }
        .announcement p  { margin:0; font-size:13px; color:#4b5563; }
        .badge-dot {
            display: inline-block; width:8px; height:8px;
            border-radius:50%; margin-right:6px;
        }
        .badge-dot.green  { background:#22c55e; }
        .badge-dot.yellow { background:#f59e0b; }
        .badge-dot.red    { background:#ef4444; }
        /* Mini chart */
        .chart-wrap { padding: 10px 0 0; }
        .bar-chart   { display:flex; align-items:flex-end; gap:8px; height:120px; }
        .bar-col     { flex:1; display:flex; flex-direction:column; align-items:center; gap:4px; }
        .bar         { width:100%; background:#0f172a; border-radius:4px 4px 0 0; min-height:4px; transition:.3s; }
        .bar:hover   { background:#22c55e; }
        .bar-label   { font-size:10px; color:#9ca3af; text-align:center; }
        .bar-val     { font-size:11px; color:#374151; font-weight:600; }
        /* Status badge inline */
        .status-pending  { background:#FEF3C7; color:#92400E; padding:3px 10px; border-radius:20px; font-size:12px; }
        .status-diterima { background:#DCFCE7; color:#166534; padding:3px 10px; border-radius:20px; font-size:12px; }
        .status-ditolak  { background:#FEE2E2; color:#991B1B; padding:3px 10px; border-radius:20px; font-size:12px; }
    </style>
</head>
<body>

<?php include '_sidebar.php'; ?>

<div class="main">

    <div class="page-header">
        <div>
            <h1>Selamat Datang, <?= htmlspecialchars($_SESSION['nama']) ?> 👋</h1>
            <p>Panel Petugas SIBANSOS — <?= date('l, d F Y') ?></p>
        </div>
        <div style="text-align:right;font-size:13px;color:#6b7280">
            🕐 <?= date('H:i') ?> WIB
        </div>
    </div>

    <!-- STATISTIK UTAMA -->
    <div class="stats-grid">
        <div class="stat-card navy">
            <div class="stat-icon">📄</div>
            <div class="stat-num"><?= $pending ?></div>
            <div class="stat-label">Menunggu Verifikasi</div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon">✅</div>
            <div class="stat-num"><?= $diterima ?></div>
            <div class="stat-label">Pengajuan Diterima</div>
        </div>
        <div class="stat-card yellow">
            <div class="stat-icon">👥</div>
            <div class="stat-num"><?= $masyarakat ?></div>
            <div class="stat-label">Data Masyarakat</div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon">❌</div>
            <div class="stat-num"><?= $ditolak ?></div>
            <div class="stat-label">Pengajuan Ditolak</div>
        </div>
    </div>

    <div class="dashboard-grid">

        <!-- KIRI: Tabel + Grafik -->
        <div>
            <!-- Pengajuan Terbaru -->
            <div class="card">
                <div class="card-title" style="display:flex;justify-content:space-between;align-items:center">
                    <span>📋 Pengajuan Terbaru</span>
                    <a href="verifikasi.php" style="font-size:13px;color:#22c55e;text-decoration:none">Lihat semua →</a>
                </div>

                <div class="table-wrap">
                    <table>
                        <tr>
                            <th>Nama</th>
                            <th>NIK</th>
                            <th>Tgl Pengajuan</th>
                            <th>Status</th>
                        </tr>
                        <?php while ($p = mysqli_fetch_assoc($pengajuan)): ?>
                        <tr>
                            <td>
                                <b><?= htmlspecialchars(
                                    !empty($p['nama_pemohon'])
                                        ? $p['nama_pemohon']
                                        : 'Pengajuan #' . $p['id_pengajuan']
                                ) ?></b>
                            </td>
                            <td style="font-size:12px;color:#6b7280;font-family:monospace">
                                <?= htmlspecialchars($p['nik']) ?>
                            </td>
                            <td style="font-size:12px;color:#9ca3af">
                                <?= !empty($p['tanggal_pengajuan'])
                                    ? date('d/m/Y', strtotime($p['tanggal_pengajuan']))
                                    : '-' ?>
                            </td>
                            <td>
                                <?php if ($p['status'] === 'pending'): ?>
                                    <span class="status-pending">Pending</span>
                                <?php elseif ($p['status'] === 'diterima'): ?>
                                    <span class="status-diterima">Diterima</span>
                                <?php else: ?>
                                    <span class="status-ditolak">Ditolak</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            </div>

<div class="card">
    <div class="card-title">📈 Tren Pengajuan (6 Bulan Terakhir)</div>

    <canvas id="grafikPengajuan" height="220"></canvas>
</div>

</div> <!-- tutup kolom kiri -->

<!-- KANAN -->
<div>

            <!-- Notifikasi -->
            <div class="card">
                <div class="card-title">🔔 Notifikasi</div>

                <?php if ($pending > 0): ?>
                <div class="announcement" style="border-color:#f59e0b">
                    <h4>⏳ Verifikasi Menunggu</h4>
                    <p>Ada <b><?= $pending ?></b> pengajuan yang perlu diverifikasi segera.</p>
                    <a href="verifikasi.php?status=pending" class="btn btn-yellow btn-sm" style="margin-top:8px;display:inline-block">
                        Proses Sekarang
                    </a>
                </div>
                <?php else: ?>
                <div class="announcement" style="border-color:#22c55e">
                    <h4>✅ Semua Bersih</h4>
                    <p>Tidak ada pengajuan yang menunggu verifikasi.</p>
                </div>
                <?php endif; ?>

                <?php if ($surveyHari > 0): ?>
                <div class="announcement" style="border-color:#3b82f6">
                    <h4>🏠 Survey Hari Ini</h4>
                    <p>Ada <b><?= $surveyHari ?></b> jadwal survey untuk hari ini.</p>
                    <a href="survey.php?status=jadwal" class="btn btn-ghost btn-sm" style="margin-top:8px;display:inline-block">
                        Lihat Jadwal
                    </a>
                </div>
                <?php endif; ?>

                <?php if ($belumSalur > 0): ?>
                <div class="announcement" style="border-color:#ef4444">
                    <h4>🎁 Bantuan Belum Disalurkan</h4>
                    <p>Ada <b><?= $belumSalur ?></b> bantuan yang belum disalurkan.</p>
                    <a href="penyaluran.php?status=belum_disalurkan" class="btn btn-red btn-sm" style="margin-top:8px;display:inline-block">
                        Lihat Data
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Ringkasan Status -->
            <div class="card">
                <div class="card-title">📊 Ringkasan Status</div>
                <?php
                $totalPengajuan = $pending + $diterima + $ditolak;
                $pctDiterima    = $totalPengajuan > 0 ? round($diterima / $totalPengajuan * 100) : 0;
                $pctDitolak     = $totalPengajuan > 0 ? round($ditolak  / $totalPengajuan * 100) : 0;
                $pctPending     = $totalPengajuan > 0 ? round($pending   / $totalPengajuan * 100) : 0;
                ?>
                <div style="font-size:13px;margin-bottom:10px;color:#6b7280">
                    Total Pengajuan: <b style="color:#111827"><?= $totalPengajuan ?></b>
                </div>
                <?php foreach ([
                    ['Diterima', $pctDiterima, '#22c55e', 'green'],
                    ['Pending',  $pctPending,  '#f59e0b', 'yellow'],
                    ['Ditolak',  $pctDitolak,  '#ef4444', 'red'],
                ] as [$lbl, $pct, $col, $cl]): ?>
                <div style="margin-bottom:12px">
                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px">
                        <span><span class="badge-dot <?= $cl ?>"></span><?= $lbl ?></span>
                        <span style="color:<?= $col ?>;font-weight:600"><?= $pct ?>%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $col ?>"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</div>
<script>
const ctx = document.getElementById('grafikPengajuan');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($chartData, 'label')); ?>,
        datasets: [{
            label: 'Jumlah Pengajuan',
            data: <?= json_encode(array_column($chartData, 'val')); ?>,
            backgroundColor: '#22c55e',
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>
</body>
</html>