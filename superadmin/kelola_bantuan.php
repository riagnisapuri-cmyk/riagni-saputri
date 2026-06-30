<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

$msg = $err = "";

// ── Tambah jenis bantuan ──
if (isset($_POST['tambah'])) {
    csrf_verify();
    $nama_jenis = trim(mysqli_real_escape_string($conn, $_POST['nama_jenis']));
    $deskripsi  = trim(mysqli_real_escape_string($conn, $_POST['deskripsi'] ?? ''));
    if (!$nama_jenis) {
        $err = "Nama jenis bantuan wajib diisi!";
    } else {
        $cek = mysqli_query($conn, "SELECT id FROM jenis_bantuan WHERE nama_jenis='$nama_jenis'");
        if (mysqli_num_rows($cek) > 0) {
            $err = "Jenis bantuan '$nama_jenis' sudah ada.";
        } else {
            mysqli_query($conn, "INSERT INTO jenis_bantuan (nama_jenis) VALUES ('$nama_jenis')");
            $msg = "Jenis bantuan <b>" . htmlspecialchars($nama_jenis) . "</b> berhasil ditambahkan.";
        }
    }
}

// ── Hapus jenis bantuan ──
if (isset($_POST['hapus'])) {
    csrf_verify();
    $id = (int)$_POST['hapus'];
    // Cek apakah sedang dipakai
    $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM pengajuan WHERE id_bansos='$id'"));
    if ($cek['c'] > 0) {
        $err = "Tidak bisa menghapus — jenis bantuan ini masih digunakan di " . $cek['c'] . " pengajuan.";
    } else {
        mysqli_query($conn, "DELETE FROM jenis_bantuan WHERE id='$id'");
        $msg = "Jenis bantuan berhasil dihapus.";
    }
}

// ── Edit jenis bantuan ──
if (isset($_POST['update'])) {
    csrf_verify();
    $id         = (int)$_POST['id'];
    $nama_jenis = trim(mysqli_real_escape_string($conn, $_POST['nama_jenis']));
    if (!$nama_jenis) {
        $err = "Nama jenis bantuan wajib diisi!";
    } else {
        $cek = mysqli_query($conn, "SELECT id FROM jenis_bantuan WHERE nama_jenis='$nama_jenis' AND id!='$id'");
        if (mysqli_num_rows($cek) > 0) {
            $err = "Nama '$nama_jenis' sudah digunakan jenis bantuan lain.";
        } else {
            mysqli_query($conn, "UPDATE jenis_bantuan SET nama_jenis='$nama_jenis' WHERE id='$id'");
            $msg = "Jenis bantuan berhasil diperbarui.";
        }
    }
}

// Data
$search = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';
$where  = $search ? "WHERE nama_jenis LIKE '%$search%'" : "";
$data   = mysqli_query($conn, "SELECT jb.*, (SELECT COUNT(*) FROM pengajuan p WHERE p.id_bansos=jb.id) AS jumlah_pengajuan FROM jenis_bantuan jb $where ORDER BY jb.id ASC");
$total  = mysqli_num_rows($data);

// Edit mode
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id   = (int)$_GET['edit'];
    $edit_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM jenis_bantuan WHERE id='$edit_id'"));
}

// Ikon per jenis
$icons = ['Sembako'=>'🛒','Pendidikan'=>'📚','Kesehatan'=>'🏥','UMKM'=>'🏪','Lansia'=>'👴','Disabilitas'=>'♿','rumah'=>'🏠'];
function get_icon($nama, $icons) {
    foreach ($icons as $k => $v) if (stripos($nama, $k) !== false) return $v;
    return '📦';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Super Admin — Kelola Jenis Bantuan</title>
<link rel="stylesheet" href="sa_style.css">
<style>
.bantuan-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 16px;
  margin-bottom: 28px;
}
.bantuan-card {
  background: var(--sa-bg);
  border: 1.5px solid var(--sa-border);
  border-radius: 14px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  transition: border-color .2s, transform .2s;
  position: relative;
}
.bantuan-card:hover { border-color: rgba(232,184,75,0.3); transform: translateY(-2px); }
.bantuan-card-icon { font-size: 32px; }
.bantuan-card-name { font-size: 15px; font-weight: 700; color: var(--sa-text); }
.bantuan-card-count { font-size: 12px; color: var(--sa-muted); }
.bantuan-card-actions { display: flex; gap: 8px; margin-top: auto; padding-top: 10px; border-top: 1px solid var(--sa-border); }
.bantuan-card-id { position: absolute; top: 12px; right: 14px; font-size: 10px; color: var(--sa-muted); font-family: monospace; }
</style>
</head>
<body>
<?php include '_sidebar.php'; ?>
<main class="sa-main">

  <div class="sa-topbar">
    <div>
      <h1 class="sa-page-title">Kelola Jenis Bantuan</h1>
      <p class="sa-page-sub">Manajemen kategori bantuan sosial SIBANSOS</p>
    </div>
    <div class="sa-breadcrumb">
      <a href="index.php">Dashboard</a> / Jenis Bantuan
    </div>
  </div>

  <?php if ($msg): ?><div class="sa-alert sa-alert-success">✅ <?= $msg ?></div><?php endif; ?>
  <?php if ($err): ?><div class="sa-alert sa-alert-error">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start">

    <!-- Kiri: Daftar -->
    <div>
      <div class="sa-card">
        <div class="sa-card-title">
          📂 Daftar Jenis Bantuan
          <span style="font-size:12px;color:var(--sa-muted);font-weight:400"><?= $total ?> jenis</span>
        </div>

        <form method="GET" class="sa-filter-bar" style="margin-bottom:20px">
          <input type="text" name="q" class="sa-search" placeholder="🔍 Cari jenis bantuan..." value="<?= htmlspecialchars($search) ?>">
          <button type="submit" class="sa-btn sa-btn-gold">Cari</button>
          <a href="kelola_bantuan.php" class="sa-btn sa-btn-ghost">Reset</a>
        </form>

        <div class="bantuan-grid">
          <?php
          $data = mysqli_query($conn, "SELECT jb.*, (SELECT COUNT(*) FROM pengajuan p WHERE p.id_bansos=jb.id) AS jumlah_pengajuan FROM jenis_bantuan jb $where ORDER BY jb.id ASC");
          $found = false;
          while ($d = mysqli_fetch_assoc($data)):
            $found = true;
            $icon = get_icon($d['nama_jenis'], $icons);
          ?>
          <div class="bantuan-card">
            <div class="bantuan-card-id">#<?= $d['id'] ?></div>
            <div class="bantuan-card-icon"><?= $icon ?></div>
            <div class="bantuan-card-name"><?= htmlspecialchars($d['nama_jenis']) ?></div>
            <div class="bantuan-card-count">
              <?= $d['jumlah_pengajuan'] ?> pengajuan terkait
            </div>
            <div class="bantuan-card-actions">
              <a href="?edit=<?= $d['id'] ?>" class="sa-btn sa-btn-blue" style="padding:5px 12px;font-size:12px;flex:1;justify-content:center">✏️ Edit</a>
              <form method="POST" onsubmit="return confirm('Hapus jenis bantuan ini?')" style="flex:1">
                <?= csrf_field() ?>
                <input type="hidden" name="hapus" value="<?= $d['id'] ?>">
                <button type="submit" class="sa-btn sa-btn-red" style="padding:5px 12px;font-size:12px;width:100%;justify-content:center">🗑️ Hapus</button>
              </form>
            </div>
          </div>
          <?php endwhile; ?>
          <?php if (!$found): ?>
          <div style="grid-column:1/-1">
            <div class="sa-empty"><div class="sa-empty-icon">📭</div><p>Belum ada jenis bantuan.</p></div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Kanan: Form -->
    <div>
      <?php if ($edit_data): ?>
      <!-- Form Edit -->
      <div class="sa-card">
        <div class="sa-card-title">✏️ Edit Jenis Bantuan</div>
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
          <div class="sa-field" style="margin-bottom:16px">
            <label for="edit_nama">Nama Jenis Bantuan</label>
            <input type="text" id="edit_nama" name="nama_jenis" value="<?= htmlspecialchars($edit_data['nama_jenis']) ?>" required>
          </div>
          <div style="display:flex;gap:10px">
            <button type="submit" name="update" class="sa-btn sa-btn-gold">💾 Simpan</button>
            <a href="kelola_bantuan.php" class="sa-btn sa-btn-ghost">Batal</a>
          </div>
        </form>
      </div>
      <?php endif; ?>

      <!-- Form Tambah -->
      <div class="sa-card">
        <div class="sa-card-title">➕ Tambah Jenis Bantuan</div>
        <form method="POST">
          <?= csrf_field() ?>
          <div class="sa-field" style="margin-bottom:16px">
            <label for="nama_jenis">Nama Jenis Bantuan</label>
            <input type="text" id="nama_jenis" name="nama_jenis" placeholder="cth: Sembako, Pendidikan..." required>
          </div>
          <button type="submit" name="tambah" class="sa-btn sa-btn-gold" style="width:100%;justify-content:center">➕ Tambah Sekarang</button>
        </form>
      </div>

      <!-- Info -->
      <div class="sa-card" style="background:rgba(232,184,75,0.05);border-color:rgba(232,184,75,0.2)">
        <div class="sa-card-title" style="color:var(--sa-gold)">💡 Informasi</div>
        <p style="font-size:13px;color:var(--sa-muted);line-height:1.7">
          Jenis bantuan yang sudah memiliki pengajuan terkait <b style="color:var(--sa-text)">tidak bisa dihapus</b> untuk menjaga integritas data. Edit nama saja jika diperlukan perubahan.
        </p>
      </div>
    </div>
  </div>

</main>
</body>
</html>