<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

$msg = $err = "";

// ── Verifikasi (setujui / tolak) ──────────────────────────────────────────
if (isset($_POST['verifikasi'])) {
    $nik     = $_POST['nik'];
    $status  = $_POST['status_verifikasi'];
    $catatan = $_POST['catatan'] ?? '';
    $admin   = $_SESSION['user_id'];
    $tgl     = date('Y-m-d');

    if (!in_array($status, ['terverifikasi', 'ditolak'])) {
        $err = "Status tidak valid.";
    } else {
        // Pastikan kolom ada
        mysqli_query($conn, "ALTER TABLE masyarakat
            ADD COLUMN IF NOT EXISTS status_verifikasi ENUM('belum','terverifikasi','ditolak') NOT NULL DEFAULT 'belum',
            ADD COLUMN IF NOT EXISTS catatan_verifikasi TEXT NULL,
            ADD COLUMN IF NOT EXISTS id_admin_verifikasi INT NULL,
            ADD COLUMN IF NOT EXISTS tanggal_verifikasi DATE NULL
        ");

        $stmt = $conn->prepare("UPDATE masyarakat SET status_verifikasi=?, catatan_verifikasi=?, id_admin_verifikasi=?, tanggal_verifikasi=? WHERE nik=?");
        $stmt->bind_param("ssiss", $status, $catatan, $admin, $tgl, $nik);
        if ($stmt->execute()) {
            $msg = "Masyarakat berhasil di" . ($status === 'terverifikasi' ? 'verifikasi' : 'tolak') . ".";
        } else {
            $err = "Gagal: " . $stmt->error;
        }
        $stmt->close();
    }
}

// ── Edit data masyarakat ──────────────────────────────────────────────────
if (isset($_POST['edit'])) {
    $nik         = $_POST['nik'];
    $nama        = $_POST['nama'];
    $alamat      = $_POST['alamat'];
    $pekerjaan   = $_POST['pekerjaan'];
    $penghasilan = (int)$_POST['penghasilan'];
    $tanggungan  = (int)$_POST['tanggungan'];
    $kondisi     = $_POST['kondisi_rumah'];
    $no_hp       = $_POST['no_hp'];

    $stmt = $conn->prepare("UPDATE masyarakat SET nama=?, alamat=?, pekerjaan=?, penghasilan=?, tanggungan=?, kondisi_rumah=?, no_hp=? WHERE nik=?");
    $stmt->bind_param("sssiiiss", $nama, $alamat, $pekerjaan, $penghasilan, $tanggungan, $kondisi, $no_hp, $nik);
    if ($stmt->execute()) {
        $msg = "Data masyarakat berhasil diperbarui.";
    } else {
        $err = "Gagal: " . $stmt->error;
    }
    $stmt->close();
}

// ── Pastikan kolom verifikasi tersedia ───────────────────────────────────
mysqli_query($conn, "ALTER TABLE masyarakat
    ADD COLUMN IF NOT EXISTS status_verifikasi ENUM('belum','terverifikasi','ditolak') NOT NULL DEFAULT 'belum',
    ADD COLUMN IF NOT EXISTS catatan_verifikasi TEXT NULL,
    ADD COLUMN IF NOT EXISTS id_admin_verifikasi INT NULL,
    ADD COLUMN IF NOT EXISTS tanggal_verifikasi DATE NULL
");

// ── Filter & Search ───────────────────────────────────────────────────────
$filter = $_GET['status'] ?? '';
$search = $_GET['q'] ?? '';
$where  = "WHERE 1=1";
$params = [];
$types  = "";

if ($filter && in_array($filter, ['belum','terverifikasi','ditolak'])) {
    $where .= " AND m.status_verifikasi=?";
    $params[] = $filter;
    $types .= "s";
}
if ($search) {
    $where .= " AND (m.nik LIKE ? OR m.nama LIKE ? OR m.alamat LIKE ?)";
    $s = "%$search%";
    $params[] = $s; $params[] = $s; $params[] = $s;
    $types .= "sss";
}

// ── Detail masyarakat ─────────────────────────────────────────────────────
$detail = null;
$riwayat = null;
if (isset($_GET['detail'])) {
    $nik_d  = $_GET['detail'];
    $stmt = $conn->prepare("SELECT m.*, a.nama AS nama_admin FROM masyarakat m LEFT JOIN admin a ON m.id_admin_verifikasi = a.id_admin WHERE m.nik=?");
    $stmt->bind_param("s", $nik_d);
    $stmt->execute();
    $detail = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($detail) {
        $stmt = $conn->prepare("SELECT p.*, bs.nama_bantuan FROM pengajuan p LEFT JOIN bantuan_sosial bs ON p.id_bansos = bs.id_bansos WHERE p.nik=? ORDER BY p.tanggal_pengajuan DESC");
        $stmt->bind_param("s", $nik_d);
        $stmt->execute();
        $riwayat = $stmt->get_result();
        $stmt->close();
    }
}

// ── Statistik ─────────────────────────────────────────────────────────────
$stat_total    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM masyarakat"))['c'];
$stat_terverif = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM masyarakat WHERE status_verifikasi='terverifikasi'"))['c'];
$stat_belum    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM masyarakat WHERE status_verifikasi='belum'"))['c'];
$stat_ditolak  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM masyarakat WHERE status_verifikasi='ditolak'"))['c'];

// ── Data utama tabel ──────────────────────────────────────────────────────
$sql = "SELECT m.* FROM masyarakat m $where ORDER BY m.nama ASC";
$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$data  = $stmt->get_result();
$total = $data->num_rows;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Admin — Verifikasi Masyarakat</title>
<link rel="stylesheet" href="admin_style.css">
<style>
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.badge-terverifikasi{background:#d1fae5;color:#065f46}
.badge-belum{background:#fef9c3;color:#713f12}
.badge-ditolak-v{background:#fee2e2;color:#7f1d1d}
.dg{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px}
.di label{font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;font-weight:600;display:block}
.di p{font-size:14px;color:#1f2937;font-weight:500;margin-top:3px}
.income-bar{height:6px;background:#e5e7eb;border-radius:99px;margin-top:6px}
.income-fill{height:100%;background:#17375e;border-radius:99px}
</style>
</head>
<body>
<?php include '_sidebar.php'; ?>
<div class="main">

  <div class="page-header">
    <div><h1>Verifikasi Masyarakat</h1><p>Verifikasi kelayakan warga penerima bantuan sosial</p></div>
    <div class="breadcrumb"><a href="index.php">Dashboard</a> / Verifikasi Masyarakat</div>
  </div>

  <?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div class="stats-grid">
    <div class="stat-card navy"><div class="stat-icon">👥</div><div class="stat-num"><?= $stat_total ?></div><div class="stat-label">Total Masyarakat</div></div>
    <div class="stat-card green"><div class="stat-icon">✅</div><div class="stat-num"><?= $stat_terverif ?></div><div class="stat-label">Terverifikasi</div></div>
    <div class="stat-card yellow"><div class="stat-icon">⏳</div><div class="stat-num"><?= $stat_belum ?></div><div class="stat-label">Belum Diverifikasi</div></div>
    <div class="stat-card red"><div class="stat-icon">❌</div><div class="stat-num"><?= $stat_ditolak ?></div><div class="stat-label">Ditolak</div></div>
  </div>

  <?php if ($detail): ?>
  <div class="card" style="border-left:4px solid #17375e;margin-bottom:24px">
    <div class="card-title">
      👤 Detail — <?= htmlspecialchars($detail['nama']) ?>
      <a href="verifikasi_masyarakat.php" class="btn btn-ghost btn-sm" style="margin-left:auto">← Kembali</a>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:28px">
      <div>
        <div class="section-title">Data Pribadi</div>
        <div class="dg">
          <div class="di"><label>NIK</label><p style="font-family:monospace"><?= htmlspecialchars($detail['nik']) ?></p></div>
          <div class="di"><label>Nama Lengkap</label><p><?= htmlspecialchars($detail['nama']) ?></p></div>
          <div class="di" style="grid-column:span 2"><label>Alamat</label><p><?= htmlspecialchars($detail['alamat']??'-') ?></p></div>
          <div class="di"><label>Pekerjaan</label><p><?= htmlspecialchars($detail['pekerjaan']??'-') ?></p></div>
          <div class="di"><label>No. HP</label><p><?= htmlspecialchars($detail['no_hp']??'-') ?></p></div>
          <div class="di"><label>Penghasilan/Bulan</label>
            <p style="color:#166534;font-weight:700">Rp <?= number_format($detail['penghasilan']??0,0,',','.') ?></p>
            <?php $pct = min(100, round(($detail['penghasilan']??0)/5000000*100)); ?>
            <div class="income-bar"><div class="income-fill" style="width:<?= $pct ?>%"></div></div>
          </div>
          <div class="di"><label>Tanggungan</label><p><?= (int)($detail['tanggungan']??0) ?> orang</p></div>
          <div class="di"><label>Kondisi Rumah</label>
            <?php $k=$detail['kondisi_rumah']??''; $kls=$k==='Layak Huni'?'badge-diterima':($k==='Tidak Layak'?'badge-ditolak':'badge-pending'); ?>
            <p><span class="badge <?= $kls ?>"><?= htmlspecialchars($k?:'-') ?></span></p>
          </div>
        </div>

        <div class="section-title">✏️ Edit Data</div>
        <form method="POST">
          <input type="hidden" name="nik" value="<?= htmlspecialchars($detail['nik']) ?>">
          <div class="field" style="margin-bottom:10px"><label>Nama</label><input type="text" name="nama" value="<?= htmlspecialchars($detail['nama']) ?>" required></div>
          <div class="field" style="margin-bottom:10px"><label>Alamat</label><textarea name="alamat" rows="2"><?= htmlspecialchars($detail['alamat']??'') ?></textarea></div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
            <div class="field"><label>Pekerjaan</label><input type="text" name="pekerjaan" value="<?= htmlspecialchars($detail['pekerjaan']??'') ?>"></div>
            <div class="field"><label>No. HP</label><input type="text" name="no_hp" value="<?= htmlspecialchars($detail['no_hp']??'') ?>"></div>
            <div class="field"><label>Penghasilan (Rp)</label><input type="number" name="penghasilan" value="<?= (int)($detail['penghasilan']??0) ?>"></div>
            <div class="field"><label>Tanggungan</label><input type="number" name="tanggungan" value="<?= (int)($detail['tanggungan']??0) ?>" min="0"></div>
          </div>
          <div class="field" style="margin-bottom:14px">
            <label>Kondisi Rumah</label>
            <select name="kondisi_rumah">
              <?php foreach(['Layak Huni','Kurang Layak','Tidak Layak'] as $opt): ?>
              <option <?= ($detail['kondisi_rumah']??'')===$opt?'selected':'' ?>><?= $opt ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" name="edit" class="btn btn-yellow btn-sm">💾 Simpan Perubahan</button>
        </form>
      </div>

      <div>
        <div class="section-title">Status Verifikasi</div>
        <?php $sv=$detail['status_verifikasi']??'belum'; $sv_cls=$sv==='terverifikasi'?'badge-terverifikasi':($sv==='ditolak'?'badge-ditolak-v':'badge-belum'); $sv_label=$sv==='terverifikasi'?'✅ Terverifikasi':($sv==='ditolak'?'❌ Ditolak':'⏳ Belum Diverifikasi'); ?>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
          <span class="badge <?= $sv_cls ?>" style="font-size:13px;padding:5px 14px"><?= $sv_label ?></span>
          <?php if($detail['tanggal_verifikasi']): ?>
          <span style="font-size:12px;color:#9ca3af"><?= date('d/m/Y', strtotime($detail['tanggal_verifikasi'])) ?><?php if($detail['nama_admin']): ?> · oleh <?= htmlspecialchars($detail['nama_admin']) ?><?php endif; ?></span>
          <?php endif; ?>
        </div>

        <?php if($detail['catatan_verifikasi']): ?>
        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:12px;font-size:13px;margin-bottom:16px"><b>Catatan:</b> <?= nl2br(htmlspecialchars($detail['catatan_verifikasi'])) ?></div>
        <?php endif; ?>

        <form method="POST" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:16px">
          <input type="hidden" name="nik" value="<?= htmlspecialchars($detail['nik']) ?>">
          <div class="field" style="margin-bottom:12px"><label>Catatan / Keterangan</label><textarea name="catatan" rows="3" placeholder="Tulis catatan verifikasi..."></textarea></div>
          <div style="display:flex;gap:10px">
            <button type="submit" name="verifikasi" value="1" onclick="this.form.querySelector('[name=status_verifikasi]').value='terverifikasi'" class="btn btn-green btn-sm">✅ Verifikasi</button>
            <button type="submit" name="verifikasi" value="1" onclick="this.form.querySelector('[name=status_verifikasi]').value='ditolak'" class="btn btn-red btn-sm">❌ Tolak</button>
            <input type="hidden" name="status_verifikasi" value="">
          </div>
        </form>

        <div class="section-title" style="margin-top:20px">📋 Riwayat Pengajuan</div>
        <?php if(!$riwayat || $riwayat->num_rows === 0): ?>
          <p style="font-size:13px;color:#9ca3af">Belum pernah mengajukan bantuan.</p>
        <?php else: ?>
          <?php while($r = $riwayat->fetch_assoc()): ?>
          <div style="border:1px solid #e5e7eb;border-radius:10px;padding:12px;margin-bottom:10px">
            <div style="font-weight:600;font-size:14px;margin-bottom:4px"><?= htmlspecialchars($r['nama_bantuan']??'-') ?></div>
            <div style="display:flex;align-items:center;gap:10px">
              <?php if($r['status']==='diterima'): ?><span class="badge badge-diterima">✅ Diterima</span>
              <?php elseif($r['status']==='ditolak'): ?><span class="badge badge-ditolak">❌ Ditolak</span>
              <?php else: ?><span class="badge badge-pending">⏳ Pending</span><?php endif; ?>
              <span style="font-size:12px;color:#9ca3af"><?= date('d/m/Y', strtotime($r['tanggal_pengajuan'])) ?></span>
            </div>
          </div>
          <?php endwhile; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-title">👥 Daftar Masyarakat</div>
    <form method="GET" class="filter-bar">
      <input type="text" name="q" placeholder="🔍 Cari NIK, nama, atau alamat..." value="<?= htmlspecialchars($search) ?>" style="flex:1">
      <select name="status" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <option value="belum" <?= $filter==='belum'?'selected':'' ?>>⏳ Belum Diverifikasi</option>
        <option value="terverifikasi" <?= $filter==='terverifikasi'?'selected':'' ?>>✅ Terverifikasi</option>
        <option value="ditolak" <?= $filter==='ditolak'?'selected':'' ?>>❌ Ditolak</option>
      </select>
      <button type="submit" class="btn btn-navy btn-sm">Cari</button>
      <a href="verifikasi_masyarakat.php" class="btn btn-ghost btn-sm">Reset</a>
    </form>

    <p style="font-size:13px;color:#6b7280;margin-bottom:16px">Menampilkan <b style="color:#1f2937"><?= $total ?></b> data</p>

    <div class="table-wrap">
      <table>
        <tr><th>#</th><th>NIK</th><th>Nama</th><th>Pekerjaan</th><th>Penghasilan</th><th>Tanggungan</th><th>Kondisi Rumah</th><th>Status Verifikasi</th><th>Aksi</th></tr>
        <?php $no=1; while($d=$data->fetch_assoc()): $sv=$d['status_verifikasi']??'belum'; $sv_cls=$sv==='terverifikasi'?'badge-terverifikasi':($sv==='ditolak'?'badge-ditolak-v':'badge-belum'); $sv_label=$sv==='terverifikasi'?'✅ Terverifikasi':($sv==='ditolak'?'❌ Ditolak':'⏳ Belum'); $k=$d['kondisi_rumah']??''; $kls=$k==='Layak Huni'?'badge-diterima':($k==='Tidak Layak'?'badge-ditolak':'badge-pending'); ?>
        <tr>
          <td style="color:#999"><?= $no++ ?></td>
          <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($d['nik']) ?></td>
          <td><b><?= htmlspecialchars($d['nama']) ?></b><?php if(!empty($d['no_hp'])): ?><div style="font-size:11px;color:#9ca3af"><?= htmlspecialchars($d['no_hp']) ?></div><?php endif; ?></td>
          <td style="font-size:13px"><?= htmlspecialchars($d['pekerjaan']??'-') ?></td>
          <td style="color:#166534;font-weight:600">Rp <?= number_format($d['penghasilan']??0,0,',','.') ?></td>
          <td style="text-align:center"><?= (int)($d['tanggungan']??0) ?> org</td>
          <td><span class="badge <?= $kls ?>"><?= htmlspecialchars($k?:'-') ?></span></td>
          <td><span class="badge <?= $sv_cls ?>"><?= $sv_label ?></span></td>
          <td>
    <div class="action-btns">
        <a href="verifikasi_masyarakat.php?detail=<?= urlencode($d['nik']) ?>"
           class="btn btn-navy btn-sm">
            🔍 Detail
        </a>
    </div>
</td>
        </tr>
        <?php endwhile; if($total==0): ?><tr><td colspan="9"><div class="empty-state"><div class="icon">👥</div><p>Tidak ada data ditemukan.</p></div></td></tr><?php endif; ?>
      </table>
    </div>
  </div>
</div>
</body>
</html>