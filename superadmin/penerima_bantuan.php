<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

$msg = $err = "";

// ── Update status verifikasi ──
if (isset($_POST['update_status'])) {
    csrf_verify();
    $id                = (int)$_POST['id'];
    $status_verifikasi = mysqli_real_escape_string($conn, $_POST['status_verifikasi']);
    $allowed           = ['pending','diterima','ditolak'];
    if (!in_array($status_verifikasi, $allowed)) {
        $err = "Status tidak valid.";
    } else {
        $tgl = ($status_verifikasi !== 'pending') ? ", tanggal_verifikasi=CURDATE()" : "";
        mysqli_query($conn, "UPDATE penerima_bantuan SET status_verifikasi='$status_verifikasi' $tgl WHERE id_penerima='$id'");
        $msg = "Status verifikasi berhasil diperbarui.";
    }
}

// ── Filter ──
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$filter_bansos = isset($_GET['bansos']) ? (int)$_GET['bansos'] : 0;
$search        = isset($_GET['q'])      ? mysqli_real_escape_string($conn, $_GET['q'])      : '';

$where = "WHERE 1=1";
if ($filter_status) $where .= " AND pb.status_verifikasi='$filter_status'";
if ($filter_bansos) $where .= " AND pb.id_bansos='$filter_bansos'";
if ($search)        $where .= " AND (m.nama LIKE '%$search%' OR pb.nik LIKE '%$search%')";

// Statistik
$stat_total    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM penerima_bantuan"))['c'];
$stat_pending  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM penerima_bantuan WHERE status_verifikasi='pending'"))['c'];
$stat_diterima = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM penerima_bantuan WHERE status_verifikasi='diterima'"))['c'];
$stat_ditolak  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM penerima_bantuan WHERE status_verifikasi='ditolak'"))['c'];

// Pagination
$per_page = 15;
$page     = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$offset   = ($page-1)*$per_page;

$total_filtered = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT COUNT(*) c FROM penerima_bantuan pb
    LEFT JOIN masyarakat m ON pb.nik=m.nik
    $where
"))['c'];
$total_pages = ceil($total_filtered/$per_page);

$data = mysqli_query($conn,"
    SELECT pb.*,
           m.nama AS nama_penerima,
           m.alamat,
           jb.nama_jenis,
           pt.nama AS nama_petugas
    FROM penerima_bantuan pb
    LEFT JOIN masyarakat m ON pb.nik=m.nik
    LEFT JOIN jenis_bantuan jb ON pb.id_bansos=jb.id
    LEFT JOIN petugas pt ON pb.id_petugas=pt.id_petugas
    $where
    ORDER BY pb.id_penerima DESC
    LIMIT $per_page OFFSET $offset
");

$qs = http_build_query(array_filter(['q'=>$search,'status'=>$filter_status,'bansos'=>$filter_bansos?:'']));

function verif_badge($s) {
    $map = [
        'pending'  => ['badge-pending',  '⏳ Pending'],
        'diterima' => ['badge-diterima', '✅ Diterima'],
        'ditolak'  => ['badge-ditolak',  '❌ Ditolak'],
    ];
    [$cls,$label] = $map[$s] ?? ['','—'];
    return "<span class=\"badge $cls\">$label</span>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Super Admin — Penerima Bantuan</title>
<link rel="stylesheet" href="sa_style.css">
<style>
.badge-pending  { background:rgba(234,179,8,0.15);color:#fbbf24;border:1px solid rgba(234,179,8,0.3); }
.badge-diterima { background:rgba(34,197,94,0.15);color:#4ade80;border:1px solid rgba(34,197,94,0.3); }
.badge-ditolak  { background:rgba(239,68,68,0.15);color:#f87171;border:1px solid rgba(239,68,68,0.3); }
.modal-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:999;align-items:center;justify-content:center; }
.modal-overlay.show { display:flex; }
.modal-box { background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:16px;padding:32px;width:100%;max-width:440px;animation:pop-in .25s ease; }
@keyframes pop-in { from{transform:scale(.9);opacity:0} to{transform:scale(1);opacity:1} }
.modal-title { font-size:17px;font-weight:700;color:var(--sa-text);margin-bottom:20px; }
.page-nav { display:flex;gap:6px;align-items:center;margin-top:20px;flex-wrap:wrap; }
.page-nav a,.page-nav span { padding:7px 13px;border-radius:7px;font-size:13px;font-weight:600;text-decoration:none;border:1px solid var(--sa-border);color:var(--sa-muted); }
.page-nav a:hover { border-color:var(--sa-gold);color:var(--sa-gold); }
.page-nav .current { background:rgba(232,184,75,0.15);border-color:var(--sa-gold);color:var(--sa-gold); }
</style>
</head>
<body>
<?php include '_sidebar.php'; ?>
<main class="sa-main">

  <div class="sa-topbar">
    <div>
      <h1 class="sa-page-title">Penerima Bantuan</h1>
      <p class="sa-page-sub">Data seluruh penerima & status verifikasi lapangan</p>
    </div>
    <div class="sa-breadcrumb">
      <a href="index.php">Dashboard</a> / Penerima
    </div>
  </div>

  <?php if ($msg): ?><div class="sa-alert sa-alert-success">✅ <?= $msg ?></div><?php endif; ?>
  <?php if ($err): ?><div class="sa-alert sa-alert-error">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <!-- Stat Cards -->
  <div class="sa-stats" style="grid-template-columns:repeat(4,1fr);margin-bottom:28px">
    <div class="sa-stat-card gold">
      <div class="sa-stat-icon">👥</div>
      <div class="sa-stat-num"><?= $stat_total ?></div>
      <div class="sa-stat-label">Total Penerima</div>
    </div>
    <div class="sa-stat-card" style="border-top:3px solid #fbbf24">
      <div class="sa-stat-icon">⏳</div>
      <div class="sa-stat-num"><?= $stat_pending ?></div>
      <div class="sa-stat-label">Pending</div>
    </div>
    <div class="sa-stat-card green">
      <div class="sa-stat-icon">✅</div>
      <div class="sa-stat-num"><?= $stat_diterima ?></div>
      <div class="sa-stat-label">Terverifikasi</div>
    </div>
    <div class="sa-stat-card red">
      <div class="sa-stat-icon">❌</div>
      <div class="sa-stat-num"><?= $stat_ditolak ?></div>
      <div class="sa-stat-label">Ditolak</div>
    </div>
  </div>

  <div class="sa-card">
    <form method="GET" class="sa-filter-bar" style="flex-wrap:wrap">
      <input type="text" name="q" class="sa-search" placeholder="🔍 Cari nama / NIK..." value="<?= htmlspecialchars($search) ?>" style="min-width:200px">
      <select name="status" class="sa-select-filter" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <option value="pending"  <?= $filter_status==='pending' ?'selected':'' ?>>⏳ Pending</option>
        <option value="diterima" <?= $filter_status==='diterima'?'selected':'' ?>>✅ Diterima</option>
        <option value="ditolak"  <?= $filter_status==='ditolak' ?'selected':'' ?>>❌ Ditolak</option>
      </select>
      <select name="bansos" class="sa-select-filter" onchange="this.form.submit()">
        <option value="">Semua Bantuan</option>
        <?php
        $jl = mysqli_query($conn,"SELECT * FROM jenis_bantuan ORDER BY nama_jenis ASC");
        while ($j = mysqli_fetch_assoc($jl)):
        ?>
        <option value="<?= $j['id'] ?>" <?= $filter_bansos==$j['id']?'selected':'' ?>><?= htmlspecialchars($j['nama_jenis']) ?></option>
        <?php endwhile; ?>
      </select>
      <button type="submit" class="sa-btn sa-btn-gold">Cari</button>
      <a href="penerima_bantuan.php" class="sa-btn sa-btn-ghost">Reset</a>
    </form>

    <p style="font-size:12px;color:var(--sa-muted);margin-bottom:16px">
      Menampilkan <b style="color:var(--sa-text)"><?= $total_filtered ?></b> penerima
    </p>

    <div class="sa-table-wrap">
      <table class="sa-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Penerima</th>
            <th>Jenis Bantuan</th>
            <th>Petugas</th>
            <th>Tgl. Verifikasi</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $no = ($page-1)*$per_page+1;
          $found = false;
          while ($d = mysqli_fetch_assoc($data)):
            $found = true;
          ?>
          <tr>
            <td style="color:var(--sa-muted);font-size:12px"><?= $no++ ?></td>
            <td>
              <div style="font-weight:600"><?= htmlspecialchars($d['nama_penerima'] ?? '—') ?></div>
              <div style="font-size:11px;color:var(--sa-muted);font-family:monospace"><?= htmlspecialchars($d['nik']) ?></div>
            </td>
            <td><span class="badge badge-admin"><?= htmlspecialchars($d['nama_jenis'] ?? '—') ?></span></td>
            <td style="font-size:13px;color:var(--sa-muted)"><?= htmlspecialchars($d['nama_petugas'] ?? '—') ?></td>
            <td style="font-size:12px;color:var(--sa-muted)"><?= $d['tanggal_verifikasi'] ? date('d/m/Y', strtotime($d['tanggal_verifikasi'])) : '—' ?></td>
            <td><?= verif_badge($d['status_verifikasi']) ?></td>
            <td>
              <button class="sa-btn sa-btn-blue" style="padding:5px 12px;font-size:12px"
                onclick="openModal(<?= $d['id_penerima'] ?>,'<?= addslashes(htmlspecialchars($d['nama_penerima']??'')) ?>','<?= $d['status_verifikasi'] ?>')">
                ⚙️ Verifikasi
              </button>
            </td>
          </tr>
          <?php endwhile; ?>
          <?php if (!$found): ?>
          <tr><td colspan="7"><div class="sa-empty"><div class="sa-empty-icon">📭</div><p>Tidak ada data penerima.</p></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="page-nav">
      <?php if($page>1): ?><a href="?<?= $qs ?>&page=1">« Pertama</a><a href="?<?= $qs ?>&page=<?= $page-1 ?>">‹ Sebelum</a><?php endif; ?>
      <?php for($p=max(1,$page-2);$p<=min($total_pages,$page+2);$p++): ?>
      <?= $p==$page?"<span class=\"current\">$p</span>":"<a href=\"?$qs&page=$p\">$p</a>" ?>
      <?php endfor; ?>
      <?php if($page<$total_pages): ?><a href="?<?= $qs ?>&page=<?= $page+1 ?>">Sesudah ›</a><a href="?<?= $qs ?>&page=<?= $total_pages ?>">Terakhir »</a><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

</main>

<!-- Modal Verifikasi -->
<div class="modal-overlay" id="verifModal">
  <div class="modal-box">
    <div class="modal-title">✅ Update Status Verifikasi</div>
    <p style="font-size:13px;color:var(--sa-muted);margin-bottom:20px" id="modalNama">—</p>
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="modalId">
      <div class="sa-field" style="margin-bottom:20px">
        <label>Status Verifikasi</label>
        <select name="status_verifikasi" id="modalStatus" style="width:100%;padding:12px 14px;background:var(--sa-bg);border:1.5px solid var(--sa-border);border-radius:10px;font-size:14px;font-family:Sora,sans-serif;color:var(--sa-text);outline:none;">
          <option value="pending">⏳ Pending</option>
          <option value="diterima">✅ Diterima</option>
          <option value="ditolak">❌ Ditolak</option>
        </select>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" class="sa-btn sa-btn-ghost" onclick="closeModal()">Batal</button>
        <button type="submit" name="update_status" class="sa-btn sa-btn-gold">💾 Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id, nama, status) {
  document.getElementById('modalId').value = id;
  document.getElementById('modalNama').textContent = 'Penerima: ' + nama;
  document.getElementById('modalStatus').value = status;
  document.getElementById('verifModal').classList.add('show');
}
function closeModal() { document.getElementById('verifModal').classList.remove('show'); }
document.getElementById('verifModal').addEventListener('click', function(e) { if(e.target===this) closeModal(); });
</script>
</body>
</html>