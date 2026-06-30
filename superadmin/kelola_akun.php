<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

$msg = $err = "";

// ── Hapus akun ──
if (isset($_POST['hapus'])) {
    csrf_verify();
    $id = (int) $_POST['hapus'];
    if ($id == $_SESSION['user_id']) {
        $err = "Tidak bisa menghapus akun diri sendiri!";
    } else {
        mysqli_query($conn, "DELETE FROM users WHERE id='$id'");
        $msg = "Akun berhasil dihapus.";
    }
}

// ── Update role ──
if (isset($_POST['update_role'])) {
    csrf_verify();
    $id = (int) $_POST['id'];
    if ($id == $_SESSION['user_id']) {
        $err = "Tidak bisa mengganti role akun sendiri.";
    } else {
        $role    = mysqli_real_escape_string($conn, $_POST['role']);
        // Tambahkan 'masyarakat' ke allowed roles agar data lama tidak ditolak
        $allowed = ['superadmin','admin','petugas','masyarakat'];
        if (in_array($role, $allowed)) {
            mysqli_query($conn, "UPDATE users SET role='$role' WHERE id='$id'");
            $msg = "Role akun berhasil diperbarui menjadi <b>" . htmlspecialchars(ucfirst($role)) . "</b>.";
        } else {
            $err = "Role tidak valid.";
        }
    }
}

// ── Filter & Search ──
$filter_role = isset($_GET['role']) ? mysqli_real_escape_string($conn, $_GET['role']) : '';
$search      = isset($_GET['q'])    ? mysqli_real_escape_string($conn, $_GET['q'])    : '';

$where = "WHERE 1=1";
if ($filter_role) $where .= " AND role='$filter_role'";
if ($search)      $where .= " AND (nama LIKE '%$search%' OR username LIKE '%$search%')";

$data  = mysqli_query($conn, "SELECT * FROM users $where ORDER BY id ASC");
$total = mysqli_num_rows($data);

// Helper: label role yang rapi
function role_label(string $role): string {
    $map = [
        'superadmin'  => '⭐ Super Admin',
        'admin'       => '🛡️ Admin',
        'petugas'     => '📋 Petugas',
        'user'        => '👤 User',
        'masyarakat'  => '🏘️ Masyarakat',
    ];
    return $map[$role] ?? ucfirst($role);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Super Admin — Kelola Akun</title>
<link rel="stylesheet" href="sa_style.css">
<style>
/* Badge tambahan untuk role masyarakat */
.badge-masyarakat {
  background: rgba(20,184,166,0.15);
  color: #2dd4bf;
  border: 1px solid rgba(20,184,166,0.25);
}

.role-select {
  padding: 5px 10px;
  background: var(--sa-bg);
  border: 1.5px solid var(--sa-border);
  border-radius: 6px;
  color: var(--sa-text);
  font-family: 'Sora', sans-serif;
  font-size: 12px;
  cursor: pointer;
  outline: none;
}
.role-select:focus { border-color: var(--sa-gold); }
.role-select option { background: #161b22; }

.modal-overlay {
  display: none;
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.6);
  z-index: 999;
  align-items: center;
  justify-content: center;
}
.modal-overlay.show { display: flex; }
.modal-box {
  background: var(--sa-surface);
  border: 1px solid var(--sa-border);
  border-radius: 16px;
  padding: 32px;
  width: 100%;
  max-width: 420px;
  animation: pop-in .25s ease;
}
@keyframes pop-in {
  from { transform: scale(.9); opacity: 0; }
  to   { transform: scale(1);  opacity: 1; }
}
.modal-title { font-size: 18px; font-weight: 700; color: var(--sa-text); margin-bottom: 8px; }
.modal-sub   { font-size: 13px; color: var(--sa-muted); margin-bottom: 24px; }
.modal-actions { display: flex; gap: 12px; justify-content: flex-end; }
</style>
</head>
<body>

<?php include '_sidebar.php'; ?>

<main class="sa-main">

  <div class="sa-topbar">
    <div>
      <h1 class="sa-page-title">Kelola Akun</h1>
      <p class="sa-page-sub">Manajemen seluruh akun pengguna sistem</p>
    </div>
    <div class="sa-breadcrumb">
      <a href="index.php">Dashboard</a> / Kelola Akun
    </div>
  </div>

  <?php if ($msg): ?>
  <div class="sa-alert sa-alert-success">✅ <?= $msg ?></div>
  <?php endif; ?>
  <?php if ($err): ?>
  <div class="sa-alert sa-alert-error">⚠️ <?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <div class="sa-card">
    <!-- Filter Bar -->
    <form method="GET" class="sa-filter-bar">
      <input type="text" name="q" class="sa-search"
             placeholder="🔍 Cari nama atau username..."
             value="<?= htmlspecialchars($search) ?>">
      <select name="role" class="sa-select-filter" onchange="this.form.submit()">
        <option value="">Semua Role</option>
        <option value="superadmin"  <?= $filter_role==='superadmin' ?'selected':'' ?>>⭐ Super Admin</option>
        <option value="admin"       <?= $filter_role==='admin'      ?'selected':'' ?>>🛡️ Admin</option>
        <option value="petugas"     <?= $filter_role==='petugas'    ?'selected':'' ?>>📋 Petugas</option>
        <option value="masyarakat"  <?= $filter_role==='masyarakat' ?'selected':'' ?>>🏘️ Masyarakat</option>
      </select>
      <button type="submit" class="sa-btn sa-btn-gold">Cari</button>
      <a href="kelola_akun.php" class="sa-btn sa-btn-ghost">Reset</a>
      <a href="tambah_akun.php" class="sa-btn sa-btn-gold" style="margin-left:auto">➕ Tambah Akun</a>
    </form>

    <p style="font-size:12px;color:var(--sa-muted);margin-bottom:16px">
      Menampilkan <b style="color:var(--sa-text)"><?= $total ?></b> akun
    </p>

    <div class="sa-table-wrap">
      <table class="sa-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Nama</th>
            <th>Username</th>
            <th>Role Saat Ini</th>
            <th>Ganti Role</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $no = 1;
          $data = mysqli_query($conn, "SELECT * FROM users $where ORDER BY id ASC");
          while ($d = mysqli_fetch_assoc($data)):
            $is_self = ($d['id'] == $_SESSION['user_id']);
            $role_now = $d['role'] ?? 'user';
          ?>
          <tr>
            <td style="color:var(--sa-muted)"><?= $no++ ?></td>
            <td>
              <div style="font-weight:600"><?= htmlspecialchars($d['nama']) ?></div>
              <div style="font-size:11px;color:var(--sa-muted)">ID #<?= $d['id'] ?></div>
            </td>
            <td style="font-family:monospace;font-size:13px"><?= htmlspecialchars($d['username']) ?></td>
            <td>
              <?php if ($role_now): ?>
              <span class="badge badge-<?= htmlspecialchars($role_now) ?>">
                <?= role_label($role_now) ?>
              </span>
              <?php else: ?>
              <span style="color:var(--sa-muted);font-size:12px">—</span>
              <?php endif; ?>
              <?php if ($is_self): ?>
              <span style="font-size:10px;color:var(--sa-gold);margin-left:4px">(Anda)</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!$is_self): ?>
              <form method="POST" style="display:flex;gap:8px;align-items:center">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                <select name="role" class="role-select">
                  <option value="superadmin" <?= $role_now==='superadmin' ?'selected':'' ?>>⭐ Super Admin</option>
                  <option value="admin"      <?= $role_now==='admin'      ?'selected':'' ?>>🛡️ Admin</option>
                  <option value="petugas"    <?= $role_now==='petugas'    ?'selected':'' ?>>📋 Petugas</option>
                  <option value="masyarakat" <?= $role_now==='masyarakat' ?'selected':'' ?>>🏘️ Masyarakat</option>
                </select>
                <button type="submit" name="update_role" class="sa-btn sa-btn-blue" style="padding:5px 12px;font-size:12px">
                  Simpan
                </button>
              </form>
              <?php else: ?>
              <span style="font-size:12px;color:var(--sa-muted)">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!$is_self): ?>
              <a href="edit_akun.php?id=<?= $d['id'] ?>" class="sa-btn sa-btn-blue" style="padding:6px 12px;font-size:12px">
                ✏️ Edit
              </a>
              <button type="button" class="sa-btn sa-btn-red"
                style="padding:6px 12px;font-size:12px"
                onclick="confirmHapus(<?= $d['id'] ?>, '<?= addslashes(htmlspecialchars($d['nama'])) ?>')">
                🗑️ Hapus
              </button>
              <?php else: ?>
              <span style="font-size:12px;color:var(--sa-muted)">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endwhile; ?>
          <?php if ($total === 0): ?>
          <tr>
            <td colspan="6">
              <div class="sa-empty">
                <div class="sa-empty-icon">🔍</div>
                <p>Tidak ada akun yang ditemukan.</p>
              </div>
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>

<!-- Modal Konfirmasi Hapus -->
<div class="modal-overlay" id="hapusModal">
  <div class="modal-box">
    <div class="modal-title">🗑️ Hapus Akun</div>
    <p class="modal-sub" id="modalMsg">Yakin ingin menghapus akun ini?</p>
    <div class="modal-actions">
      <button type="button" class="sa-btn sa-btn-ghost" onclick="closeModal()">Batal</button>
      <button type="button" id="modalConfirmBtn" class="sa-btn sa-btn-red" onclick="submitDelete()">Ya, Hapus</button>
    </div>
  </div>
</div>

<form id="deleteForm" method="POST" style="display:none">
  <?= csrf_field() ?>
  <input type="hidden" name="hapus" id="deleteId" value="">
</form>

<script>
let pendingDeleteId = null;

function confirmHapus(id, nama) {
  pendingDeleteId = id;
  document.getElementById('modalMsg').textContent = 'Yakin ingin menghapus akun "' + nama + '"? Tindakan ini tidak bisa dibatalkan.';
  document.getElementById('hapusModal').classList.add('show');
}
function submitDelete() {
  if (pendingDeleteId === null) return;
  document.getElementById('deleteId').value = pendingDeleteId;
  document.getElementById('deleteForm').submit();
}
function closeModal() {
  pendingDeleteId = null;
  document.getElementById('hapusModal').classList.remove('show');
}
document.getElementById('hapusModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
</script>

</body>
</html>