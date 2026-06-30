<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

// Filter
$tgl_dari = isset($_GET['dari']) ? mysqli_real_escape_string($conn,$_GET['dari']) : date('Y-m-01');
$tgl_sampai = isset($_GET['sampai']) ? mysqli_real_escape_string($conn,$_GET['sampai']) : date('Y-m-d');

// Statistik laporan
$total_masyarakat = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM masyarakat"))['c'];
$total_pengajuan  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM pengajuan WHERE DATE(tanggal_pengajuan) BETWEEN '$tgl_dari' AND '$tgl_sampai'"))['c'];
$total_diterima   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM pengajuan WHERE status='diterima' AND DATE(tanggal_pengajuan) BETWEEN '$tgl_dari' AND '$tgl_sampai'"))['c'];
$total_ditolak    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM pengajuan WHERE status='ditolak' AND DATE(tanggal_pengajuan) BETWEEN '$tgl_dari' AND '$tgl_sampai'"))['c'];
$total_pending    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM pengajuan WHERE status='pending' AND DATE(tanggal_pengajuan) BETWEEN '$tgl_dari' AND '$tgl_sampai'"))['c'];

// Data laporan pengajuan
$laporan = mysqli_query($conn,"
    SELECT p.*, m.nama, m.alamat, m.pekerjaan, m.penghasilan,
           bs.nama_bantuan, bs.jumlah_bantuan
    FROM pengajuan p
    LEFT JOIN masyarakat m ON p.nik=m.nik
    LEFT JOIN bantuan_sosial bs ON p.id_bansos=bs.id_bansos
    WHERE DATE(p.tanggal_pengajuan) BETWEEN '$tgl_dari' AND '$tgl_sampai'
    ORDER BY p.tanggal_pengajuan DESC
");

// Rekapitulasi per program
$rekap = mysqli_query($conn,"
    SELECT bs.nama_bantuan,
           COUNT(p.id_pengajuan) as total,
           SUM(p.status='diterima') as diterima,
           SUM(p.status='ditolak') as ditolak,
           SUM(p.status='pending') as pending
    FROM pengajuan p
    LEFT JOIN bantuan_sosial bs ON p.id_bansos=bs.id_bansos
    WHERE DATE(p.tanggal_pengajuan) BETWEEN '$tgl_dari' AND '$tgl_sampai'
    GROUP BY p.id_bansos
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Admin — Laporan</title>
<link rel="stylesheet" href="admin_style.css">
<style>
@media print {
  .sidebar, .no-print { display:none !important; }
  .main { margin-left:0 !important; padding:20px !important; }
  .card { box-shadow:none !important; border:1px solid #ddd !important; }
  .page-header { box-shadow:none !important; border:1px solid #ddd !important; }
}
</style>
</head>
<body>
<?php include '_sidebar.php'; ?>
<div class="main">

  <div class="page-header">
    <div><h1>Laporan</h1><p>Rekapitulasi data bantuan sosial</p></div>
    <div style="display:flex;gap:10px;align-items:center">
      <button onclick="window.print()" class="btn btn-navy no-print">🖨️ Cetak Laporan</button>
      <div class="breadcrumb no-print"><a href="index.php">Dashboard</a> / Laporan</div>
    </div>
  </div>

  <!-- Filter Tanggal -->
  <div class="card no-print">
    <div class="card-title">📅 Filter Periode</div>
    <form method="GET" style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap">
      <div class="field">
        <label>Dari Tanggal</label>
        <input type="date" name="dari" value="<?= $tgl_dari ?>">
      </div>
      <div class="field">
        <label>Sampai Tanggal</label>
        <input type="date" name="sampai" value="<?= $tgl_sampai ?>">
      </div>
      <button type="submit" class="btn btn-navy">Tampilkan</button>
    </form>
  </div>

  <!-- Header Cetak -->
  <div style="text-align:center;margin-bottom:20px;display:none" id="print-header">
    <h2 style="font-size:20px;font-weight:700">LAPORAN BANTUAN SOSIAL — SIBANSOS</h2>
    <p style="font-size:13px;color:#666">Periode: <?= date('d/m/Y',strtotime($tgl_dari)) ?> s/d <?= date('d/m/Y',strtotime($tgl_sampai)) ?></p>
    <p style="font-size:12px;color:#999">Dicetak: <?= date('d/m/Y H:i') ?> oleh <?= htmlspecialchars($_SESSION['nama']) ?></p>
    <hr style="margin:12px 0">
  </div>
  <style>@media print { #print-header { display:block !important; } }</style>

  <!-- Statistik -->
  <div class="stats-grid">
    <div class="stat-card navy"><div class="stat-icon">👥</div><div class="stat-num"><?= $total_masyarakat ?></div><div class="stat-label">Total Masyarakat</div></div>
    <div class="stat-card navy"><div class="stat-icon">📋</div><div class="stat-num"><?= $total_pengajuan ?></div><div class="stat-label">Total Pengajuan</div></div>
    <div class="stat-card green"><div class="stat-icon">✅</div><div class="stat-num"><?= $total_diterima ?></div><div class="stat-label">Diterima</div></div>
    <div class="stat-card red"><div class="stat-icon">❌</div><div class="stat-num"><?= $total_ditolak ?></div><div class="stat-label">Ditolak</div></div>
    <div class="stat-card yellow"><div class="stat-icon">⏳</div><div class="stat-num"><?= $total_pending ?></div><div class="stat-label">Pending</div></div>
  </div>

  <!-- Rekap per Program -->
  <div class="card">
    <div class="card-title">📊 Rekapitulasi per Program</div>
    <div class="table-wrap">
      <table>
        <tr><th>Program Bantuan</th><th>Total Pengajuan</th><th>Diterima</th><th>Ditolak</th><th>Pending</th></tr>
        <?php while($r=mysqli_fetch_assoc($rekap)): ?>
        <tr>
          <td><b><?= htmlspecialchars($r['nama_bantuan']??'—') ?></b></td>
          <td style="text-align:center"><b><?= $r['total'] ?></b></td>
          <td style="text-align:center"><span class="badge badge-diterima"><?= $r['diterima'] ?></span></td>
          <td style="text-align:center"><span class="badge badge-ditolak"><?= $r['ditolak'] ?></span></td>
          <td style="text-align:center"><span class="badge badge-pending"><?= $r['pending'] ?></span></td>
        </tr>
        <?php endwhile; ?>
      </table>
    </div>
  </div>

  <!-- Detail Pengajuan -->
  <div class="card">
    <div class="card-title">📄 Detail Seluruh Pengajuan</div>
    <div class="table-wrap">
      <table>
        <tr><th>#</th><th>Nama</th><th>NIK</th><th>Program</th><th>Nilai Bantuan</th><th>Status</th><th>Tanggal</th></tr>
        <?php $no=1; while($d=mysqli_fetch_assoc($laporan)): ?>
        <tr>
          <td style="color:#999"><?= $no++ ?></td>
          <td><b><?= htmlspecialchars($d['nama']??'-') ?></b><div style="font-size:11px;color:#999"><?= htmlspecialchars($d['pekerjaan']??'') ?></div></td>
          <td style="font-family:monospace;font-size:12px"><?= $d['nik'] ?></td>
          <td style="font-size:13px"><?= htmlspecialchars($d['nama_bantuan']??'-') ?></td>
          <td style="color:#166534;font-weight:600">Rp <?= number_format($d['jumlah_bantuan'],0,',','.') ?></td>
          <td>
            <?php if($d['status']==='diterima'): ?><span class="badge badge-diterima">✅ Diterima</span>
            <?php elseif($d['status']==='ditolak'): ?><span class="badge badge-ditolak">❌ Ditolak</span>
            <?php else: ?><span class="badge badge-pending">⏳ Pending</span><?php endif; ?>
          </td>
          <td style="font-size:12px;color:#999"><?= date('d/m/Y',strtotime($d['tanggal_pengajuan'])) ?></td>
        </tr>
        <?php endwhile; ?>
      </table>
    </div>
  </div>

</div>
</body>
</html>
