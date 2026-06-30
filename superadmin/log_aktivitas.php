<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

// ── Hapus semua log ── (sekarang lewat POST + CSRF, bukan link GET biasa)
if (isset($_POST['clear_log'])) {
    csrf_verify();
    mysqli_query($conn, "DELETE FROM log_aktivitas");
    header("Location: log_aktivitas.php");
    exit;
}

// ── Filter ──
$filter_aksi = isset($_GET['aksi']) ? mysqli_real_escape_string($conn, $_GET['aksi']) : '';
$filter_role = isset($_GET['role']) ? mysqli_real_escape_string($conn, $_GET['role']) : '';
$filter_tgl  = isset($_GET['tgl'])  ? mysqli_real_escape_string($conn, $_GET['tgl'])  : '';
$search      = isset($_GET['q'])    ? mysqli_real_escape_string($conn, $_GET['q'])    : '';

$where = "WHERE 1=1";
if ($filter_aksi) $where .= " AND aksi='$filter_aksi'";
if ($filter_role) $where .= " AND role='$filter_role'";
if ($filter_tgl)  $where .= " AND DATE(waktu)='$filter_tgl'";
if ($search)      $where .= " AND (nama LIKE '%$search%' OR username LIKE '%$search%' OR ip_address LIKE '%$search%')";

// Statistik
$total_log    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM log_aktivitas"))['c'];
$total_login  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM log_aktivitas WHERE aksi='login'"))['c'];
$total_logout = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM log_aktivitas WHERE aksi='logout'"))['c'];
$total_hari   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM log_aktivitas WHERE DATE(waktu)=CURDATE()"))['c'];

// Pagination
$per_page = 20;
$page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset   = ($page - 1) * $per_page;

$total_filtered = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM log_aktivitas $where"))['c'];
$total_pages    = ceil($total_filtered / $per_page);

$data = mysqli_query($conn,"
    SELECT * FROM log_aktivitas $where
    ORDER BY waktu DESC
    LIMIT $per_page OFFSET $offset
");

// Query string untuk pagination
$qs = http_build_query(array_filter([
    'q'    => $search,
    'aksi' => $filter_aksi,
    'role' => $filter_role,
    'tgl'  => $filter_tgl,
]));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Super Admin — Log Aktivitas</title>
<link rel="stylesheet" href="sa_style.css">
<style>
.page-nav { display: flex; gap: 6px; align-items: center; margin-top: 20px; flex-wrap: wrap; }
.page-nav a, .page-nav span {
  padding: 7px 13px;
  border-radius: 7px;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  border: 1px solid var(--sa-border);
  color: var(--sa-muted);
}
.page-nav a:hover { border-color: var(--sa-gold); color: var(--sa-gold); }
.page-nav .current { background: rgba(232,184,75,0.15); border-color: var(--sa-gold); color: var(--sa-gold); }
</style>
</head>
<body>

<?php include '_sidebar.php'; ?>

<main class="sa-main">

  <div class="sa-topbar">
    <div>
      <h1 class="sa-page-title">Log Aktivitas</h1>
      <p class="sa-page-sub">Riwayat seluruh aktivitas login & logout pengguna</p>
    </div>
    <div class="sa-breadcrumb">
      <a href="index.php">Dashboard</a> / Log Aktivitas
    </div>
  </div>

  <!-- Stat Cards -->
  <div class="sa-stats" style="grid-template-columns:repeat(4,1fr);margin-bottom:28px">
    <div class="sa-stat-card gold">
      <div class="sa-stat-icon">📋</div>
      <div class="sa-stat-num"><?= $total_log ?></div>
      <div class="sa-stat-label">Total Log</div>
    </div>
    <div class="sa-stat-card green">
      <div class="sa-stat-icon">🟢</div>
      <div class="sa-stat-num"><?= $total_login ?></div>
      <div class="sa-stat-label">Total Login</div>
    </div>
    <div class="sa-stat-card red">
      <div class="sa-stat-icon">🔴</div>
      <div class="sa-stat-num"><?= $total_logout ?></div>
      <div class="sa-stat-label">Total Logout</div>
    </div>
    <div class="sa-stat-card blue">
      <div class="sa-stat-icon">📅</div>
      <div class="sa-stat-num"><?= $total_hari ?></div>
      <div class="sa-stat-label">Aktivitas Hari Ini</div>
    </div>
  </div>

  <div class="sa-card">
    <!-- Filter Bar + Tombol Bersihkan Log (2 form terpisah, tidak nested) -->
    <div class="sa-filter-bar" style="flex-wrap:wrap;align-items:flex-start">
      <form method="GET" style="display:flex;gap:12px;flex:1;flex-wrap:wrap">
        <input type="text" name="q" class="sa-search"
               placeholder="🔍 Cari nama, username, IP..."
               value="<?= htmlspecialchars($search) ?>" style="min-width:220px">

        <select name="aksi" class="sa-select-filter" onchange="this.form.submit()">
          <option value="">Semua Aksi</option>
          <option value="login"  <?= $filter_aksi==='login'?'selected':'' ?>>🟢 Login</option>
          <option value="logout" <?= $filter_aksi==='logout'?'selected':'' ?>>🔴 Logout</option>
        </select>

        <select name="role" class="sa-select-filter" onchange="this.form.submit()">
          <option value="">Semua Role</option>
          <option value="superadmin" <?= $filter_role==='superadmin'?'selected':'' ?>>⭐ Super Admin</option>
          <option value="admin"      <?= $filter_role==='admin'?'selected':'' ?>>🛡️ Admin</option>
          <option value="petugas"    <?= $filter_role==='petugas'?'selected':'' ?>>📋 Petugas</option>
          <option value="masyarakat"       <?= $filter_role==='masyarakat'?'selected':'' ?>>👤 masyarakat</option>
        </select>

        <input type="date" name="tgl" class="sa-select-filter"
               value="<?= htmlspecialchars($filter_tgl) ?>"
               onchange="this.form.submit()">

        <button type="submit" class="sa-btn sa-btn-gold">Cari</button>
        <a href="log_aktivitas.php" class="sa-btn sa-btn-ghost">Reset</a>
      </form>

      <?php if ($total_log > 0): ?>
      <form method="POST" onsubmit="return confirm('Hapus SEMUA log aktivitas? Tidak bisa dibatalkan!')">
        <?= csrf_field() ?>
        <button type="submit" name="clear_log" class="sa-btn sa-btn-red">
          🗑️ Bersihkan Log
        </button>
      </form>
      <?php endif; ?>
    </div>

    <p style="font-size:12px;color:var(--sa-muted);margin-bottom:16px">
      Menampilkan <b style="color:var(--sa-text)"><?= $total_filtered ?></b> log
      <?php if ($total_pages > 1): ?>
      · Halaman <b style="color:var(--sa-text)"><?= $page ?></b> dari <b style="color:var(--sa-text)"><?= $total_pages ?></b>
      <?php endif; ?>
    </p>

    <div class="sa-table-wrap">
      <table class="sa-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Nama</th>
            <th>Username</th>
            <th>Role</th>
            <th>Aksi</th>
            <th>IP Address</th>
            <th>Waktu</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $no = ($page - 1) * $per_page + 1;
          $found = false;
          while ($l = mysqli_fetch_assoc($data)):
            $found = true;
          ?>
          <tr>
            <td style="color:var(--sa-muted);font-size:12px"><?= $no++ ?></td>
            <td>
              <div style="font-weight:600"><?= htmlspecialchars($l['nama']) ?></div>
            </td>
            <td style="font-family:monospace;font-size:12px;color:var(--sa-muted)">
              <?= htmlspecialchars($l['username']) ?>
            </td>
            <td>
              <span class="badge badge-<?= $l['role'] ?>">
                <?= ucfirst($l['role']) ?>
              </span>
            </td>
            <td>
              <span class="badge badge-<?= $l['aksi'] ?>">
                <?= $l['aksi'] === 'login' ? '🟢 Login' : '🔴 Logout' ?>
              </span>
            </td>
            <td>
              <code style="font-size:12px;color:var(--sa-muted);background:rgba(255,255,255,0.04);padding:3px 8px;border-radius:5px">
                <?= htmlspecialchars($l['ip_address']) ?>
              </code>
            </td>
            <td style="font-size:12px;color:var(--sa-muted)">
              <?= date('d/m/Y', strtotime($l['waktu'])) ?>
              <br>
              <span style="font-size:11px"><?= date('H:i:s', strtotime($l['waktu'])) ?></span>
            </td>
          </tr>
          <?php endwhile; ?>
          <?php if (!$found): ?>
          <tr>
            <td colspan="7">
              <div class="sa-empty">
                <div class="sa-empty-icon">📭</div>
                <p>Tidak ada log yang ditemukan.</p>
              </div>
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="page-nav">
      <?php if ($page > 1): ?>
      <a href="?<?= $qs ?>&page=1">« Pertama</a>
      <a href="?<?= $qs ?>&page=<?= $page-1 ?>">‹ Sebelum</a>
      <?php endif; ?>

      <?php
      $range = 2;
      for ($p = max(1, $page-$range); $p <= min($total_pages, $page+$range); $p++):
      ?>
      <?php if ($p == $page): ?>
        <span class="current"><?= $p ?></span>
      <?php else: ?>
        <a href="?<?= $qs ?>&page=<?= $p ?>"><?= $p ?></a>
      <?php endif; ?>
      <?php endfor; ?>

      <?php if ($page < $total_pages): ?>
      <a href="?<?= $qs ?>&page=<?= $page+1 ?>">Sesudah ›</a>
      <a href="?<?= $qs ?>&page=<?= $total_pages ?>">Terakhir »</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </div>

</main>

</body>
</html>