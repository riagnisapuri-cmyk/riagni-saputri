<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

$msg = $err = "";

// ── Update status survey ──
if (isset($_POST['update_status'])) {
    csrf_verify();
    $id      = (int)$_POST['id'];
    $status  = mysqli_real_escape_string($conn, $_POST['status']);
    $catatan = mysqli_real_escape_string($conn, trim($_POST['catatan'] ?? ''));
    $rekomendasi = mysqli_real_escape_string($conn, trim($_POST['rekomendasi'] ?? ''));
    $kelayakan   = mysqli_real_escape_string($conn, $_POST['kelayakan'] ?? '');
    $allowed_status    = ['jadwal','proses','selesai'];
    $allowed_kelayakan = ['layak','tidak_layak',''];
    if (!in_array($status, $allowed_status)) {
        $err = "Status tidak valid.";
    } elseif ($kelayakan && !in_array($kelayakan, $allowed_kelayakan)) {
        $err = "Kelayakan tidak valid.";
    } else {
        mysqli_query($conn, "
            UPDATE survey
            SET status='$status', catatan='$catatan',
                rekomendasi='$rekomendasi',
                kelayakan=" . ($kelayakan ? "'$kelayakan'" : "NULL") . "
            WHERE id_survey='$id'
        ");
        $msg = "Data survey berhasil diperbarui.";
    }
}

// ── Filter ──
$filter_status    = isset($_GET['status'])    ? mysqli_real_escape_string($conn, $_GET['status'])    : '';
$filter_kelayakan = isset($_GET['kelayakan']) ? mysqli_real_escape_string($conn, $_GET['kelayakan']) : '';
$search           = isset($_GET['q'])         ? mysqli_real_escape_string($conn, $_GET['q'])         : '';
$filter_tgl       = isset($_GET['tgl'])       ? mysqli_real_escape_string($conn, $_GET['tgl'])       : '';

$where = "WHERE 1=1";
if ($filter_status)    $where .= " AND s.status='$filter_status'";
if ($filter_kelayakan) $where .= " AND s.kelayakan='$filter_kelayakan'";
if ($filter_tgl)       $where .= " AND s.tanggal_survey='$filter_tgl'";
if ($search)           $where .= " AND (m.nama LIKE '%$search%' OR s.petugas LIKE '%$search%')";

// Statistik
$stat_total    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM survey"))['c'];
$stat_jadwal   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM survey WHERE status='jadwal'"))['c'];
$stat_proses   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM survey WHERE status='proses'"))['c'];
$stat_selesai  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM survey WHERE status='selesai'"))['c'];
$stat_layak    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM survey WHERE kelayakan='layak'"))['c'];
$stat_tdk_layak= mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM survey WHERE kelayakan='tidak_layak'"))['c'];

// Pagination
$per_page = 15;
$page     = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$offset   = ($page-1)*$per_page;

$total_filtered = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT COUNT(*) c FROM survey s
    LEFT JOIN masyarakat m ON s.id_masyarakat=m.nik
    $where
"))['c'];
$total_pages = ceil($total_filtered/$per_page);

$data = mysqli_query($conn,"
    SELECT s.*, m.nama AS nama_masyarakat
    FROM survey s
    LEFT JOIN masyarakat m ON s.id_masyarakat=m.nik
    $where
    ORDER BY s.created_at DESC
    LIMIT $per_page OFFSET $offset
");

$qs = http_build_query(array_filter(['q'=>$search,'status'=>$filter_status,'kelayakan'=>$filter_kelayakan,'tgl'=>$filter_tgl]));

function status_survey_badge($s) {
    $map = [
        'jadwal'  => ['badge-pending', '📅 Terjadwal'],
        'proses'  => ['badge-admin',   '🔄 Proses'],
        'selesai' => ['badge-diterima','✅ Selesai'],
    ];
    [$cls,$label] = $map[$s] ?? ['','—'];
    return "<span class=\"badge $cls\">$label</span>";
}
function kelayakan_badge($k) {
    if (!$k) return '<span style="color:var(--sa-muted);font-size:12px">—</span>';
    if ($k === 'layak') return '<span class="badge badge-diterima">✅ Layak</span>';
    return '<span class="badge badge-ditolak">❌ Tidak Layak</span>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Super Admin — Monitor Survey</title>
<link rel="stylesheet" href="sa_style.css">
<style>
.badge-pending  { background:rgba(234,179,8,0.15);color:#fbbf24;border:1px solid rgba(234,179,8,0.3); }
.badge-diterima { background:rgba(34,197,94,0.15);color:#4ade80;border:1px solid rgba(34,197,94,0.3); }
.badge-ditolak  { background:rgba(239,68,68,0.15);color:#f87171;border:1px solid rgba(239,68,68,0.3); }
.badge-admin    { background:rgba(59,130,246,0.15);color:#60a5fa;border:1px solid rgba(59,130,246,0.3); }
.modal-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:999;align-items:center;justify-content:center; }
.modal-overlay.show { display:flex; }
.modal-box { background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:16px;padding:32px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;animation:pop-in .25s ease; }
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
      <h1 class="sa-page-title">Monitor Survey</h1>
      <p class="sa-page-sub">Pantau hasil survey lapangan kelayakan penerima bantuan</p>
    </div>
    <div class="sa-breadcrumb">
      <a href="index.php">Dashboard</a> / Survey
    </div>
  </div>

  <?php if ($msg): ?><div class="sa-alert sa-alert-success">✅ <?= $msg ?></div><?php endif; ?>
  <?php if ($err): ?><div class="sa-alert sa-alert-error">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <!-- Stats -->
  <div class="sa-stats" style="grid-template-columns:repeat(6,1fr);margin-bottom:28px">
    <div class="sa-stat-card gold">
      <div class="sa-stat-icon">📋</div>
      <div class="sa-stat-num"><?= $stat_total ?></div>
      <div class="sa-stat-label">Total Survey</div>
    </div>
    <div class="sa-stat-card" style="border-top:3px solid #fbbf24">
      <div class="sa-stat-icon">📅</div>
      <div class="sa-stat-num"><?= $stat_jadwal ?></div>
      <div class="sa-stat-label">Terjadwal</div>
    </div>
    <div class="sa-stat-card blue">
      <div class="sa-stat-icon">🔄</div>
      <div class="sa-stat-num"><?= $stat_proses ?></div>
      <div class="sa-stat-label">Proses</div>
    </div>
    <div class="sa-stat-card green">
      <div class="sa-stat-icon">✅</div>
      <div class="sa-stat-num"><?= $stat_selesai ?></div>
      <div class="sa-stat-label">Selesai</div>
    </div>
    <div class="sa-stat-card green">
      <div class="sa-stat-icon">👍</div>
      <div class="sa-stat-num"><?= $stat_layak ?></div>
      <div class="sa-stat-label">Layak</div>
    </div>
    <div class="sa-stat-card red">
      <div class="sa-stat-icon">👎</div>
      <div class="sa-stat-num"><?= $stat_tdk_layak ?></div>
      <div class="sa-stat-label">Tidak Layak</div>
    </div>
  </div>

  <div class="sa-card">
    <form method="GET" class="sa-filter-bar" style="flex-wrap:wrap">
      <input type="text" name="q" class="sa-search" placeholder="🔍 Cari nama / petugas..." value="<?= htmlspecialchars($search) ?>" style="min-width:200px">
      <select name="status" class="sa-select-filter" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <option value="jadwal"  <?= $filter_status==='jadwal' ?'selected':'' ?>>📅 Terjadwal</option>
        <option value="proses"  <?= $filter_status==='proses' ?'selected':'' ?>>🔄 Proses</option>
        <option value="selesai" <?= $filter_status==='selesai'?'selected':'' ?>>✅ Selesai</option>
      </select>
      <select name="kelayakan" class="sa-select-filter" onchange="this.form.submit()">
        <option value="">Semua Kelayakan</option>
        <option value="layak"       <?= $filter_kelayakan==='layak'      ?'selected':'' ?>>✅ Layak</option>
        <option value="tidak_layak" <?= $filter_kelayakan==='tidak_layak'?'selected':'' ?>>❌ Tidak Layak</option>
      </select>
      <input type="date" name="tgl" class="sa-select-filter" value="<?= htmlspecialchars($filter_tgl) ?>" onchange="this.form.submit()">
      <button type="submit" class="sa-btn sa-btn-gold">Cari</button>
      <a href="monitor_survey.php" class="sa-btn sa-btn-ghost">Reset</a>
    </form>

    <p style="font-size:12px;color:var(--sa-muted);margin-bottom:16px">Menampilkan <b style="color:var(--sa-text)"><?= $total_filtered ?></b> survey</p>

    <div class="sa-table-wrap">
      <table class="sa-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Masyarakat</th>
            <th>Petugas</th>
            <th>Tgl. Survey</th>
            <th>Penghasilan</th>
            <th>Tanggungan</th>
            <th>Status</th>
            <th>Kelayakan</th>
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
              <div style="font-weight:600"><?= htmlspecialchars($d['nama_masyarakat'] ?? '—') ?></div>
              <div style="font-size:11px;color:var(--sa-muted);font-family:monospace"><?= htmlspecialchars($d['id_masyarakat']) ?></div>
            </td>
            <td style="font-size:13px;color:var(--sa-muted)"><?= htmlspecialchars($d['petugas'] ?? '—') ?></td>
            <td style="font-size:12px;color:var(--sa-muted)"><?= $d['tanggal_survey'] ? date('d/m/Y', strtotime($d['tanggal_survey'])) : '—' ?></td>
            <td style="font-family:monospace;font-size:12px;color:var(--sa-gold)">
              <?= $d['penghasilan'] ? 'Rp '.number_format($d['penghasilan'],0,',','.') : '—' ?>
            </td>
            <td style="text-align:center;font-weight:700"><?= $d['jumlah_tanggungan'] ?? '—' ?></td>
            <td><?= status_survey_badge($d['status']) ?></td>
            <td><?= kelayakan_badge($d['kelayakan']) ?></td>
            <td>
              <button class="sa-btn sa-btn-blue" style="padding:5px 12px;font-size:12px"
                onclick="openModal(
                  <?= $d['id_survey'] ?>,
                  '<?= addslashes(htmlspecialchars($d['nama_masyarakat']??'')) ?>',
                  '<?= $d['status'] ?>',
                  '<?= $d['kelayakan']??'' ?>',
                  '<?= addslashes(htmlspecialchars($d['catatan']??'')) ?>',
                  '<?= addslashes(htmlspecialchars($d['rekomendasi']??'')) ?>'
                )">
                ✏️ Edit
              </button>
            </td>
          </tr>
          <?php endwhile; ?>
          <?php if (!$found): ?>
          <tr><td colspan="9"><div class="sa-empty"><div class="sa-empty-icon">📭</div><p>Tidak ada data survey.</p></div></td></tr>
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

<!-- Modal Edit Survey -->
<div class="modal-overlay" id="surveyModal">
  <div class="modal-box">
    <div class="modal-title">📋 Edit Data Survey</div>
    <p style="font-size:13px;color:var(--sa-muted);margin-bottom:20px" id="modalNama">—</p>
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="modalId">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px">
        <div class="sa-field">
          <label>Status Survey</label>
          <select name="status" id="modalStatus" style="width:100%;padding:12px 14px;background:var(--sa-bg);border:1.5px solid var(--sa-border);border-radius:10px;font-size:14px;font-family:Sora,sans-serif;color:var(--sa-text);outline:none;">
            <option value="jadwal">📅 Terjadwal</option>
            <option value="proses">🔄 Proses</option>
            <option value="selesai">✅ Selesai</option>
          </select>
        </div>
        <div class="sa-field">
          <label>Kelayakan</label>
          <select name="kelayakan" id="modalKelayakan" style="width:100%;padding:12px 14px;background:var(--sa-bg);border:1.5px solid var(--sa-border);border-radius:10px;font-size:14px;font-family:Sora,sans-serif;color:var(--sa-text);outline:none;">
            <option value="">— Belum dinilai —</option>
            <option value="layak">✅ Layak</option>
            <option value="tidak_layak">❌ Tidak Layak</option>
          </select>
        </div>
      </div>
      <div class="sa-field" style="margin-bottom:14px">
        <label>Rekomendasi</label>
        <textarea name="rekomendasi" id="modalRekomendasi" rows="2"
          style="width:100%;padding:12px 14px;background:var(--sa-bg);border:1.5px solid var(--sa-border);border-radius:10px;font-size:13px;font-family:Sora,sans-serif;color:var(--sa-text);outline:none;resize:vertical"
          placeholder="Rekomendasi petugas..."></textarea>
      </div>
      <div class="sa-field" style="margin-bottom:20px">
        <label>Catatan Tambahan</label>
        <textarea name="catatan" id="modalCatatan" rows="2"
          style="width:100%;padding:12px 14px;background:var(--sa-bg);border:1.5px solid var(--sa-border);border-radius:10px;font-size:13px;font-family:Sora,sans-serif;color:var(--sa-text);outline:none;resize:vertical"
          placeholder="Catatan..."></textarea>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" class="sa-btn sa-btn-ghost" onclick="closeModal()">Batal</button>
        <button type="submit" name="update_status" class="sa-btn sa-btn-gold">💾 Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id, nama, status, kelayakan, catatan, rekomendasi) {
  document.getElementById('modalId').value = id;
  document.getElementById('modalNama').textContent = 'Masyarakat: ' + nama;
  document.getElementById('modalStatus').value = status;
  document.getElementById('modalKelayakan').value = kelayakan;
  document.getElementById('modalCatatan').value = catatan;
  document.getElementById('modalRekomendasi').value = rekomendasi;
  document.getElementById('surveyModal').classList.add('show');
}
function closeModal() { document.getElementById('surveyModal').classList.remove('show'); }
document.getElementById('surveyModal').addEventListener('click', function(e) { if(e.target===this) closeModal(); });
</script>
</body>
</html>