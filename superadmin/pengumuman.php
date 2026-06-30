<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

$msg = $err = "";

// ── Cek kolom tabel pengumuman ──
$cols_res = mysqli_query($conn, "SHOW COLUMNS FROM pengumuman");
$cols = [];
while ($c = mysqli_fetch_assoc($cols_res)) $cols[] = $c['Field'];
$has_status     = in_array('status', $cols);
$has_created_at = in_array('created_at', $cols) || in_array('tanggal', $cols);
$col_date       = in_array('created_at', $cols) ? 'created_at' : (in_array('tanggal', $cols) ? 'tanggal' : null);
$pk             = in_array('id_pengumuman', $cols) ? 'id_pengumuman' : 'id';

// ── Hapus ──
if (isset($_POST['hapus'])) {
    csrf_verify();
    $id = (int)$_POST['hapus'];
    mysqli_query($conn, "DELETE FROM pengumuman WHERE $pk='$id'");
    $msg = "Pengumuman berhasil dihapus.";
}

// ── Toggle status (aktif/nonaktif) ──
if (isset($_POST['toggle_status']) && $has_status) {
    csrf_verify();
    $id         = (int)$_POST['id'];
    $new_status = $_POST['new_status'] === 'aktif' ? 'aktif' : 'nonaktif';
    $new_status_esc = mysqli_real_escape_string($conn, $new_status);
    mysqli_query($conn, "UPDATE pengumuman SET status='$new_status_esc' WHERE $pk='$id'");
    $msg = "Status pengumuman diperbarui.";
}

// ── Tambah pengumuman ──
if (isset($_POST['tambah'])) {
    csrf_verify();
    $judul  = mysqli_real_escape_string($conn, trim($_POST['judul']));
    $isi    = mysqli_real_escape_string($conn, trim($_POST['isi']));
    if (!$judul || !$isi) {
        $err = "Judul dan isi pengumuman wajib diisi!";
    } else {
        $status_val = $has_status ? ", status='aktif'" : "";
        $date_val   = $col_date   ? ", $col_date=NOW()" : "";
        mysqli_query($conn, "INSERT INTO pengumuman (judul, isi $status_val, $date_val) VALUES ('$judul', '$isi')");
        // Fix query karena ada extra comma — pakai approach yang benar:
        // Build columns & values dynamically
        $insert_cols = ['judul', 'isi'];
        $insert_vals = ["'$judul'", "'$isi'"];
        if ($has_status) { $insert_cols[] = 'status'; $insert_vals[] = "'aktif'"; }
        if ($col_date)   { $insert_cols[] = $col_date; $insert_vals[] = "NOW()"; }
        $q = mysqli_query($conn, "INSERT INTO pengumuman (" . implode(',',$insert_cols) . ") VALUES (" . implode(',',$insert_vals) . ")");
        $msg = "Pengumuman berhasil ditambahkan.";
    }
}

// ── Update pengumuman ──
if (isset($_POST['update'])) {
    csrf_verify();
    $id    = (int)$_POST['id'];
    $judul = mysqli_real_escape_string($conn, trim($_POST['judul']));
    $isi   = mysqli_real_escape_string($conn, trim($_POST['isi']));
    if (!$judul || !$isi) {
        $err = "Judul dan isi wajib diisi!";
    } else {
        mysqli_query($conn, "UPDATE pengumuman SET judul='$judul', isi='$isi' WHERE $pk='$id'");
        $msg = "Pengumuman berhasil diperbarui.";
        header("Location: pengumuman.php?updated=1");
        exit;
    }
}

// Data
$search = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';
$where  = $search ? "WHERE judul LIKE '%$search%' OR isi LIKE '%$search%'" : "";
$order  = $col_date ? "ORDER BY $col_date DESC" : "ORDER BY $pk DESC";
$data   = mysqli_query($conn, "SELECT * FROM pengumuman $where $order");
$total  = mysqli_num_rows($data);

// Edit mode
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id   = (int)$_GET['edit'];
    $edit_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengumuman WHERE $pk='$edit_id'"));
}

if (isset($_GET['updated'])) $msg = "Pengumuman berhasil diperbarui.";
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Super Admin — Pengumuman</title>
<link rel="stylesheet" href="sa_style.css">
<style>
.pengumuman-list { display:flex;flex-direction:column;gap:14px; }
.pengumuman-item {
  background:var(--sa-bg);border:1.5px solid var(--sa-border);border-radius:12px;
  padding:18px 20px;transition:border-color .2s;
}
.pengumuman-item:hover { border-color:rgba(232,184,75,0.25); }
.pengumuman-item.nonaktif { opacity:.5; }
.peng-header { display:flex;align-items:flex-start;gap:12px;margin-bottom:10px; }
.peng-judul { font-size:15px;font-weight:700;color:var(--sa-text);flex:1;line-height:1.3; }
.peng-isi { font-size:13px;color:var(--sa-muted);line-height:1.7;margin-bottom:12px; }
.peng-meta { font-size:11px;color:var(--sa-muted); }
.peng-actions { display:flex;gap:8px;margin-top:12px;padding-top:12px;border-top:1px solid var(--sa-border); }
.badge-aktif    { background:rgba(34,197,94,0.15);color:#4ade80;border:1px solid rgba(34,197,94,0.3); }
.badge-nonaktif { background:rgba(139,148,158,0.15);color:#8b949e;border:1px solid rgba(139,148,158,0.25); }
</style>
</head>
<body>
<?php include '_sidebar.php'; ?>
<main class="sa-main">

  <div class="sa-topbar">
    <div>
      <h1 class="sa-page-title">Pengumuman</h1>
      <p class="sa-page-sub">Kelola pengumuman & informasi publik sistem SIBANSOS</p>
    </div>
    <div class="sa-breadcrumb">
      <a href="index.php">Dashboard</a> / Pengumuman
    </div>
  </div>

  <?php if ($msg): ?><div class="sa-alert sa-alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="sa-alert sa-alert-error">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start">

    <!-- Kiri: Daftar -->
    <div>
      <div class="sa-card">
        <div class="sa-card-title">
          📢 Daftar Pengumuman
          <span style="font-size:12px;color:var(--sa-muted);font-weight:400"><?= $total ?> pengumuman</span>
        </div>

        <form method="GET" class="sa-filter-bar" style="margin-bottom:20px">
          <input type="text" name="q" class="sa-search" placeholder="🔍 Cari pengumuman..." value="<?= htmlspecialchars($search) ?>">
          <button type="submit" class="sa-btn sa-btn-gold">Cari</button>
          <a href="pengumuman.php" class="sa-btn sa-btn-ghost">Reset</a>
        </form>

        <div class="pengumuman-list">
          <?php
          $data = mysqli_query($conn, "SELECT * FROM pengumuman $where $order");
          $found = false;
          while ($d = mysqli_fetch_assoc($data)):
            $found = true;
            $is_aktif = !$has_status || ($d['status'] ?? 'aktif') === 'aktif';
          ?>
          <div class="pengumuman-item <?= !$is_aktif ? 'nonaktif' : '' ?>">
            <div class="peng-header">
              <div class="peng-judul"><?= htmlspecialchars($d['judul']) ?></div>
              <?php if ($has_status): ?>
              <span class="badge <?= $is_aktif ? 'badge-aktif' : 'badge-nonaktif' ?>">
                <?= $is_aktif ? '🟢 Aktif' : '⭕ Nonaktif' ?>
              </span>
              <?php endif; ?>
            </div>
            <div class="peng-isi"><?= nl2br(htmlspecialchars(mb_substr($d['isi'],0,200))) ?><?= mb_strlen($d['isi'])>200?'…':'' ?></div>
            <div class="peng-meta">
              #<?= $d[$pk] ?>
              <?php if ($col_date && $d[$col_date]): ?>
               · <?= date('d/m/Y H:i', strtotime($d[$col_date])) ?>
              <?php endif; ?>
            </div>
            <div class="peng-actions">
              <a href="?edit=<?= $d[$pk] ?>" class="sa-btn sa-btn-blue" style="padding:5px 12px;font-size:12px">✏️ Edit</a>
              <?php if ($has_status): ?>
              <form method="POST" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $d[$pk] ?>">
                <input type="hidden" name="new_status" value="<?= $is_aktif ? 'nonaktif' : 'aktif' ?>">
                <button type="submit" name="toggle_status" class="sa-btn sa-btn-ghost" style="padding:5px 12px;font-size:12px">
                  <?= $is_aktif ? '⭕ Nonaktifkan' : '🟢 Aktifkan' ?>
                </button>
              </form>
              <?php endif; ?>
              <form method="POST" onsubmit="return confirm('Hapus pengumuman ini?')" style="margin-left:auto">
                <?= csrf_field() ?>
                <input type="hidden" name="hapus" value="<?= $d[$pk] ?>">
                <button type="submit" class="sa-btn sa-btn-red" style="padding:5px 12px;font-size:12px">🗑️ Hapus</button>
              </form>
            </div>
          </div>
          <?php endwhile; ?>
          <?php if (!$found): ?>
          <div class="sa-empty"><div class="sa-empty-icon">📭</div><p>Belum ada pengumuman.</p></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Kanan: Form -->
    <div>
      <?php if ($edit_data): ?>
      <div class="sa-card" style="border-color:rgba(59,130,246,0.3)">
        <div class="sa-card-title" style="color:#60a5fa">✏️ Edit Pengumuman</div>
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= $edit_data[$pk] ?>">
          <div class="sa-field" style="margin-bottom:14px">
            <label>Judul Pengumuman</label>
            <input type="text" name="judul" value="<?= htmlspecialchars($edit_data['judul']) ?>" required>
          </div>
          <div class="sa-field" style="margin-bottom:16px">
            <label>Isi Pengumuman</label>
            <textarea name="isi" rows="5" required
              style="width:100%;padding:12px 14px;background:var(--sa-bg);border:1.5px solid var(--sa-border);border-radius:10px;font-size:14px;font-family:Sora,sans-serif;color:var(--sa-text);outline:none;resize:vertical"
              onfocus="this.style.borderColor='var(--sa-gold)'" onblur="this.style.borderColor='var(--sa-border)'"><?= htmlspecialchars($edit_data['isi']) ?></textarea>
          </div>
          <div style="display:flex;gap:10px">
            <button type="submit" name="update" class="sa-btn sa-btn-blue">💾 Simpan Edit</button>
            <a href="pengumuman.php" class="sa-btn sa-btn-ghost">Batal</a>
          </div>
        </form>
      </div>
      <?php endif; ?>

      <!-- Form Tambah -->
      <div class="sa-card">
        <div class="sa-card-title">📢 Buat Pengumuman Baru</div>
        <form method="POST">
          <?= csrf_field() ?>
          <div class="sa-field" style="margin-bottom:14px">
            <label for="judul_baru">Judul Pengumuman</label>
            <input type="text" id="judul_baru" name="judul" placeholder="Masukkan judul..." required>
          </div>
          <div class="sa-field" style="margin-bottom:16px">
            <label for="isi_baru">Isi Pengumuman</label>
            <textarea id="isi_baru" name="isi" rows="5" required
              style="width:100%;padding:12px 14px;background:var(--sa-bg);border:1.5px solid var(--sa-border);border-radius:10px;font-size:14px;font-family:Sora,sans-serif;color:var(--sa-text);outline:none;resize:vertical"
              onfocus="this.style.borderColor='var(--sa-gold)'" onblur="this.style.borderColor='var(--sa-border)'"
              placeholder="Tulis isi pengumuman di sini..."></textarea>
          </div>
          <button type="submit" name="tambah" class="sa-btn sa-btn-gold" style="width:100%;justify-content:center">
            📢 Publikasikan
          </button>
        </form>
      </div>
    </div>
  </div>

</main>
</body>
</html>