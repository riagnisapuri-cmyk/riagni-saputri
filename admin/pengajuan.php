<?php
date_default_timezone_set('Asia/Makassar');
require_once 'auth_guard.php';
include '../config/koneksi.php';

$msg = $err = "";

// ── Approve / Tolak Pengajuan ─────────────────────────────────────────────
if (isset($_POST['proses'])) {
    $id      = (int) $_POST['id_pengajuan'];
    $status  = $_POST['status'];
    $catatan = $_POST['catatan_admin'] ?? '';
    $admin   = $_SESSION['user_id'];
    $tgl     = date('Y-m-d');

    if (!in_array($status, ['diterima', 'ditolak'])) {
        $err = "Status tidak valid.";
    } else {
        $stmt = $conn->prepare("UPDATE pengajuan SET status=?, catatan_admin=?, id_admin=?, tanggal_proses=? WHERE id_pengajuan=?");
        $stmt->bind_param("ssisi", $status, $catatan, $admin, $tgl, $id);
        if ($stmt->execute()) {
            $msg = "Pengajuan berhasil di" . ($status === 'diterima' ? 'terima' : 'tolak') . ".";
        } else {
            $err = "Gagal memproses: " . $stmt->error;
        }
        $stmt->close();
    }
}

// ── Tambah Pengajuan Manual ───────────────────────────────────────────────
if (isset($_POST['tambah'])) {
    $nik       = $_POST['nik'];
    $id_bansos = (int) $_POST['id_bansos'];
    $alasan    = $_POST['alasan'];
    $status    = 'pending';

    $stmt = $conn->prepare("SELECT nik FROM masyarakat WHERE nik=?");
    $stmt->bind_param("s", $nik);
    $stmt->execute();
    if (!$stmt->get_result()->num_rows) {
        $err = "NIK tidak ditemukan di data masyarakat.";
    } else {
        $stmt2 = $conn->prepare("SELECT id_pengajuan FROM pengajuan WHERE nik=? AND id_bansos=? AND status != 'ditolak'");
        $stmt2->bind_param("si", $nik, $id_bansos);
        $stmt2->execute();
        if ($stmt2->get_result()->num_rows) {
            $err = "Masyarakat ini sudah memiliki pengajuan aktif untuk program tersebut.";
        } else {
            $stmt3 = $conn->prepare("INSERT INTO pengajuan (nik, id_bansos, alasan, status) VALUES (?, ?, ?)");
            $stmt3->bind_param("siss", $nik, $id_bansos, $alasan, $status);
            $stmt3->execute() ? $msg = "Pengajuan berhasil ditambahkan." : $err = "Gagal: " . $stmt3->error;
            $stmt3->close();
        }
        $stmt2->close();
    }
    $stmt->close();
}

// ── Hapus Pengajuan ───────────────────────────────────────────────────────
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    $stmt = $conn->prepare("DELETE FROM pengajuan WHERE id_pengajuan=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: pengajuan.php?msg=" . urlencode("Pengajuan berhasil dihapus."));
    exit;
}
if (isset($_GET['msg'])) $msg = htmlspecialchars($_GET['msg']);

// ── Detail Pengajuan ──────────────────────────────────────────────────────
$detail = null;
if (isset($_GET['detail'])) {
    $id_d = (int) $_GET['detail'];
    $stmt = $conn->prepare("SELECT p.*, m.nama, m.alamat, m.pekerjaan, m.penghasilan, m.tanggungan, m.kondisi_rumah, m.no_hp, m.status_verifikasi, bs.nama_bantuan, bs.jumlah_bantuan, a.nama AS nama_admin FROM pengajuan p LEFT JOIN masyarakat m ON p.nik = m.nik LEFT JOIN bantuan_sosial bs ON p.id_bansos = bs.id_bansos LEFT JOIN admin a ON p.id_admin = a.id_admin WHERE p.id_pengajuan = ?");
    $stmt->bind_param("i", $id_d);
    $stmt->execute();
    $detail = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ... sisa kode filter, statistik, list sama kayak punyamu, tapi semua query utama pake prepare ...
// ── Filter & Search ───────────────────────────────────────────────────────
$filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$search = isset($_GET['q'])      ? mysqli_real_escape_string($conn, $_GET['q'])      : '';
$where  = "WHERE 1=1";
if ($filter) $where .= " AND p.status='$filter'";
if ($search) $where .= " AND (m.nama LIKE '%$search%' OR p.nik LIKE '%$search%' OR bs.nama_bantuan LIKE '%$search%')";

// ── Statistik ─────────────────────────────────────────────────────────────
$stat_total   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM pengajuan"))['c'];
$stat_pending = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM pengajuan WHERE status='pending'"))['c'];
$stat_terima  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM pengajuan WHERE status='diterima'"))['c'];
$stat_tolak   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM pengajuan WHERE status='ditolak'"))['c'];

// ── Data Program Bantuan (untuk form tambah) ──────────────────────────────
$list_bansos = mysqli_query($conn, "SELECT id_bansos, nama_bantuan FROM bantuan_sosial ORDER BY nama_bantuan ASC");

// ── Data Masyarakat (untuk autocomplete) ─────────────────────────────────
$list_masyarakat = mysqli_query($conn, "SELECT nik, nama FROM masyarakat ORDER BY nama ASC");

// ── Query Utama ───────────────────────────────────────────────────────────
$data  = mysqli_query($conn, "
    SELECT p.*, m.nama, m.status_verifikasi, bs.nama_bantuan, bs.jumlah_bantuan
    FROM pengajuan p
    LEFT JOIN masyarakat m ON p.nik = m.nik
    LEFT JOIN bantuan_sosial bs ON p.id_bansos = bs.id_bansos
    $where
    ORDER BY
        CASE p.status WHEN 'pending' THEN 0 WHEN 'diterima' THEN 1 ELSE 2 END,
        p.tanggal_pengajuan DESC
");
$total = mysqli_num_rows($data);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Admin — Pengajuan Bantuan</title>
<link rel="stylesheet" href="admin_style.css">
<style>
/* ── Modal ── */
.modal-overlay {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,.45); z-index:1000;
    align-items:center; justify-content:center;
}
.modal-overlay.open { display:flex; }
.modal-box {
    background:#fff; border-radius:16px; padding:28px;
    width:min(640px,95vw); max-height:90vh; overflow-y:auto;
    box-shadow:0 20px 60px rgba(0,0,0,.25);
    animation:popIn .2s ease;
}
@keyframes popIn { from{transform:scale(.94);opacity:0} to{transform:scale(1);opacity:1} }
.modal-header {
    display:flex; align-items:center; justify-content:space-between;
    margin-bottom:20px; padding-bottom:16px; border-bottom:1px solid #e5e7eb;
}
.modal-close {
    width:32px; height:32px; border-radius:50%; border:none;
    background:#f3f4f6; cursor:pointer; font-size:18px;
    display:flex; align-items:center; justify-content:center;
}
.modal-close:hover { background:#e5e7eb; }
/* ── Detail grid ── */
.dg  { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:18px; }
.di label { font-size:11px; color:#6b7280; text-transform:uppercase; letter-spacing:.5px; font-weight:600; display:block; }
.di p { font-size:14px; color:#1f2937; font-weight:500; margin-top:3px; }
.section-title {
    font-size:11px; font-weight:700; color:#6b7280;
    text-transform:uppercase; letter-spacing:.6px;
    margin:18px 0 10px; padding-bottom:6px; border-bottom:1px solid #f3f4f6;
}
/* ── Dokumen preview ── */
.dok-link {
    display:inline-flex; align-items:center; gap:6px;
    padding:6px 12px; border-radius:8px; background:#f3f4f6;
    font-size:12px; font-weight:600; color:#374151;
    text-decoration:none; margin:4px;
    border:1px solid #e5e7eb; transition:background .15s;
}
.dok-link:hover { background:#e5e7eb; }
/* ── Status warna ── */
.st-pending  { background:#fef9c3; color:#713f12; }
.st-diterima { background:#d1fae5; color:#065f46; }
.st-ditolak  { background:#fee2e2; color:#7f1d1d; }
/* ── Proses form ── */
.proses-box {
    background:#f9fafb; border:1px solid #e5e7eb;
    border-radius:12px; padding:18px; margin-top:16px;
}
/* ── Tambah form ── */
.tambah-card {
    background:#fff; border:1px solid #e5e7eb;
    border-radius:14px; padding:24px; margin-bottom:24px;
}
.tambah-card .card-title {
    font-size:15px; font-weight:700; color:#1f2937;
    margin-bottom:18px; display:flex; align-items:center; gap:8px;
}
.field-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px; }
/* ── Info badge verifikasi ── */
.verif-warn {
    display:flex; align-items:center; gap:8px;
    background:#fef3c7; border:1px solid #fde68a;
    border-radius:8px; padding:10px 14px; font-size:13px;
    color:#92400e; margin-bottom:14px;
}
</style>
</head>
<body>
<?php include '_sidebar.php'; ?>
<div class="main">

  <div class="page-header">
    <div><h1>Pengajuan Bantuan</h1><p>Verifikasi dan tindak lanjuti pengajuan masyarakat</p></div>
    <div class="breadcrumb"><a href="index.php">Dashboard</a> / Pengajuan</div>
  </div>

  <?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <!-- ── Statistik ── -->
  <div class="stats-grid">
    <div class="stat-card navy"><div class="stat-icon">📋</div><div class="stat-num"><?= $stat_total ?></div><div class="stat-label">Total Pengajuan</div></div>
    <div class="stat-card yellow"><div class="stat-icon">⏳</div><div class="stat-num"><?= $stat_pending ?></div><div class="stat-label">Pending</div></div>
    <div class="stat-card green"><div class="stat-icon">✅</div><div class="stat-num"><?= $stat_terima ?></div><div class="stat-label">Diterima</div></div>
    <div class="stat-card red"><div class="stat-icon">❌</div><div class="stat-num"><?= $stat_tolak ?></div><div class="stat-label">Ditolak</div></div>
  </div>

  <!-- ── Detail Modal (server-side) ── -->
  <?php if ($detail): ?>
  <div class="card" style="border-left:4px solid #17375e;margin-bottom:24px">
    <div class="card-title">
      📄 Detail Pengajuan #<?= $detail['id_pengajuan'] ?>
      <a href="pengajuan.php" class="btn btn-ghost btn-sm" style="margin-left:auto">← Kembali</a>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:28px">
      <!-- Kiri: Info Pemohon -->
      <div>
        <div class="section-title">👤 Data Pemohon</div>

        <?php if(($d['status_verifikasi']??'belum') !== 'terverifikasi'): ?>
        <div style="font-size:10px;color:#d97706;margin-top:2px">
        ⚠️ Belum terverifikasi
        </div>
        <?php endif; ?>

        <div class="dg">
          <div class="di"><label>NIK</label><p style="font-family:monospace"><?= $detail['nik'] ?></p></div>
          <div class="di"><label>Nama Lengkap</label><p><?= htmlspecialchars($detail['nama']??'-') ?></p></div>
          <div class="di" style="grid-column:span 2"><label>Alamat</label><p><?= htmlspecialchars($detail['alamat']??'-') ?></p></div>
          <div class="di"><label>Pekerjaan</label><p><?= htmlspecialchars($detail['pekerjaan']??'-') ?></p></div>
          <div class="di"><label>No. HP</label><p><?= $detail['no_hp']??'-' ?></p></div>
          <div class="di"><label>Penghasilan/Bulan</label><p style="color:#166534;font-weight:700">Rp <?= number_format($detail['penghasilan']??0,0,',','.') ?></p></div>
          <div class="di"><label>Tanggungan</label><p><?= $detail['tanggungan']??0 ?> orang</p></div>
          <div class="di"><label>Kondisi Rumah</label><p><?= $detail['kondisi_rumah']??'-' ?></p></div>
        </div>

        <div class="section-title">🎁 Program Bantuan</div>
        <div class="dg">
          <div class="di"><label>Nama Program</label><p><?= htmlspecialchars($detail['nama_bantuan']??'-') ?></p></div>
          <div class="di"><label>Nilai Bantuan</label><p style="color:#166534;font-weight:700">Rp <?= number_format($detail['jumlah_bantuan']??0,0,',','.') ?></p></div>
        </div>

        <div class="section-title">📝 Alasan Pengajuan</div>
        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:12px;font-size:13px;line-height:1.6">
          <?= nl2br(htmlspecialchars($detail['alasan']??'-')) ?>
        </div>
      </div>

      <!-- Kanan: Dokumen & Proses -->
      <div>
        <div class="section-title">📁 Dokumen Pendukung</div>
        <div style="margin-bottom:16px">
          <?php
          $docs = [
            'KTP'  => $detail['ktp']  ?? '',
            'KK'   => $detail['kk']   ?? '',
            'SKTM' => $detail['sktm'] ?? '',
          ];
          $ada = false;
          foreach ($docs as $label => $file):
            if (!$file) continue; $ada = true;
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $icon = in_array($ext, ['jpg','jpeg','png','gif','webp']) ? '🖼️' : '📄';
          ?>
          <a href="../uploads/<?= htmlspecialchars($file) ?>" target="_blank" class="dok-link">
            <?= $icon ?> <?= $label ?>
          </a>
          <?php endforeach; ?>
          <?php if(!$ada): ?>
          <p style="font-size:13px;color:#9ca3af">Tidak ada dokumen diunggah.</p>
          <?php endif; ?>
        </div>

        <?php if(!empty($detail['dokumen'])): ?>
        <a href="../uploads/<?= htmlspecialchars($detail['dokumen']) ?>" target="_blank" class="dok-link">
          📎 Dokumen Lain
        </a>
        <?php endif; ?>

        <!-- Status saat ini -->
        <div class="section-title">📊 Status Pengajuan</div>
        <?php
          $st = $detail['status']??'pending';
          $st_cls = $st==='diterima'?'st-diterima':($st==='ditolak'?'st-ditolak':'st-pending');
          $st_label = $st==='diterima'?'✅ Diterima':($st==='ditolak'?'❌ Ditolak':'⏳ Pending');
        ?>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
          <span class="badge <?= $st_cls ?>" style="font-size:13px;padding:5px 14px"><?= $st_label ?></span>
          <?php if($detail['tanggal_proses']): ?>
          <span style="font-size:12px;color:#9ca3af">
            <?= date('d/m/Y', strtotime($detail['tanggal_proses'])) ?>
            <?php if($detail['nama_admin']): ?> · oleh <?= htmlspecialchars($detail['nama_admin']) ?><?php endif; ?>
          </span>
          <?php endif; ?>
        </div>

        <?php if($detail['catatan_admin']): ?>
        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:12px;font-size:13px;margin-bottom:16px">
          <b>Catatan Admin:</b><br><?= nl2br(htmlspecialchars($detail['catatan_admin'])) ?>
        </div>
        <?php endif; ?>

        <!-- Form Proses -->
        <?php if($st === 'pending'): ?>
        <div class="proses-box">
          <div style="font-weight:700;font-size:13px;margin-bottom:12px">⚙️ Proses Pengajuan</div>
          <form method="POST">
            <input type="hidden" name="id_pengajuan" value="<?= $detail['id_pengajuan'] ?>">
            <div class="field" style="margin-bottom:12px">
              <label>Catatan / Keterangan</label>
              <textarea name="catatan_admin" rows="3" placeholder="Tulis alasan keputusan..."></textarea>
            </div>
            <div style="display:flex;gap:10px">
              <button type="submit" name="proses" value="1"
                onclick="this.form.querySelector('[name=status]').value='diterima'"
                class="btn btn-green btn-sm">✅ Terima</button>
              <button type="submit" name="proses" value="1"
                onclick="this.form.querySelector('[name=status]').value='ditolak'"
                class="btn btn-red btn-sm">❌ Tolak</button>
              <input type="hidden" name="status" value="">
            </div>
          </form>
        </div>
        <?php else: ?>
        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:14px;font-size:13px;color:#6b7280">
          Pengajuan ini sudah diproses. Untuk mengubah, hapus dan buat pengajuan baru.
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Form Tambah Pengajuan Manual ── -->
  <div class="tambah-card">
    <div class="card-title">➕ Tambah Pengajuan Manual</div>
    <form method="POST">
      <div class="field-row">
        <div class="field">
          <label>NIK Pemohon <span style="color:red">*</span></label>
          <select name="nik" required style="width:100%">
            <option value="">-- Pilih Masyarakat --</option>
            <?php
            $tmp = mysqli_query($conn, "SELECT nik, nama FROM masyarakat ORDER BY nama ASC");
            while($m = mysqli_fetch_assoc($tmp)):
            ?>
            <option value="<?= $m['nik'] ?>"><?= htmlspecialchars($m['nama']) ?> — <?= $m['nik'] ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="field">
          <label>Program Bantuan <span style="color:red">*</span></label>
          <select name="id_bansos" required style="width:100%">
            <option value="">-- Pilih Program --</option>
            <?php
            $tmp2 = mysqli_query($conn, "SELECT id_bansos, nama_bantuan FROM bantuan_sosial ORDER BY nama_bantuan ASC");
            while($b = mysqli_fetch_assoc($tmp2)):
            ?>
            <option value="<?= $b['id_bansos'] ?>"><?= htmlspecialchars($b['nama_bantuan']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
      </div>
      <div class="field" style="margin-bottom:14px">
        <label>Alasan Pengajuan</label>
        <textarea name="alasan" rows="3" placeholder="Tulis alasan pengajuan bantuan..."></textarea>
      </div>
      <button type="submit" name="tambah" class="btn btn-navy btn-sm">💾 Simpan Pengajuan</button>
    </form>
  </div>

  <!-- ── Tabel Daftar Pengajuan ── -->
  <div class="card">
    <div class="card-title">📋 Daftar Pengajuan</div>

    <form method="GET" class="filter-bar">
      <input type="text" name="q" placeholder="🔍 Cari nama, NIK, atau program..."
             value="<?= htmlspecialchars($search) ?>" style="flex:1">
      <select name="status" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <option value="pending"  <?= $filter==='pending' ?'selected':'' ?>>⏳ Pending</option>
        <option value="diterima" <?= $filter==='diterima'?'selected':'' ?>>✅ Diterima</option>
        <option value="ditolak"  <?= $filter==='ditolak' ?'selected':'' ?>>❌ Ditolak</option>
      </select>
      <button type="submit" class="btn btn-navy btn-sm">Cari</button>
      <a href="pengajuan.php" class="btn btn-ghost btn-sm">Reset</a>
    </form>

    <p style="font-size:13px;color:#6b7280;margin-bottom:16px">
      Menampilkan <b style="color:#1f2937"><?= $total ?></b> pengajuan
    </p>

    <div class="table-wrap">
      <table>
        <tr>
          <th>#</th>
          <th>Nama Pemohon</th>
          <th>NIK</th>
          <th>Program Bantuan</th>
          <th>Nilai Bantuan</th>
          <th>Status</th>
          <th>Tanggal Ajuan</th>
          <th>Aksi</th>
        </tr>
        <?php
        $no = 1;
        // Re-run query (sudah di-reset pointer)
        $data = mysqli_query($conn, "
            SELECT p.*, m.nama, m.status_verifikasi, bs.nama_bantuan, bs.jumlah_bantuan
            FROM pengajuan p
            LEFT JOIN masyarakat m ON p.nik = m.nik
            LEFT JOIN bantuan_sosial bs ON p.id_bansos = bs.id_bansos
            $where
            ORDER BY
                CASE p.status WHEN 'pending' THEN 0 WHEN 'diterima' THEN 1 ELSE 2 END,
                p.tanggal_pengajuan DESC
        ");
        while ($d = mysqli_fetch_assoc($data)):
          $st = $d['status']??'pending';
          $st_cls   = $st==='diterima'?'badge-diterima':($st==='ditolak'?'badge-ditolak':'badge-pending');
          $st_label = $st==='diterima'?'✅ Diterima':($st==='ditolak'?'❌ Ditolak':'⏳ Pending');
        ?>
        <tr>
          <td style="color:#999"><?= $no++ ?></td>
          <td>
            <b><?= htmlspecialchars($d['nama']??'-') ?></b>
            <?php if(($d['status_verifikasi']??'belum') !== 'terverifikasi'): ?>
            <div style="font-size:10px;color:#d97706;margin-top:2px">⚠️ Belum terverifikasi</div>
            <?php endif; ?>
          </td>
          <td style="font-family:monospace;font-size:12px"><?= $d['nik'] ?></td>
          <td style="font-size:13px"><?= htmlspecialchars($d['nama_bantuan']??'-') ?></td>
          <td style="color:#166534;font-weight:600">
            <?= $d['jumlah_bantuan'] ? 'Rp '.number_format($d['jumlah_bantuan'],0,',','.') : '-' ?>
          </td>
          <td><span class="badge <?= $st_cls ?>"><?= $st_label ?></span></td>
          <td style="font-size:12px;color:#9ca3af">
            <?= $d['tanggal_pengajuan'] ? date('d/m/Y', strtotime($d['tanggal_pengajuan'])) : '-' ?>
          </td>
          <td style="display:flex;gap:6px;flex-wrap:wrap">
            <a href="pengajuan.php?detail=<?= $d['id_pengajuan'] ?>" class="btn btn-navy btn-sm">🔍 Detail</a>
            <?php if($st === 'pending'): ?>
            <!-- Quick approve/tolak langsung dari tabel -->
            <form method="POST" style="display:inline" onsubmit="return confirm('Terima pengajuan ini?')">
              <input type="hidden" name="id_pengajuan" value="<?= $d['id_pengajuan'] ?>">
              <input type="hidden" name="status" value="diterima">
              <button type="submit" name="proses" value="1" class="btn btn-green btn-sm" title="Terima">✅</button>
            </form>
            <form method="POST" style="display:inline" onsubmit="return confirm('Tolak pengajuan ini?')">
              <input type="hidden" name="id_pengajuan" value="<?= $d['id_pengajuan'] ?>">
              <input type="hidden" name="status" value="ditolak">
              <button type="submit" name="proses" value="1" class="btn btn-red btn-sm" title="Tolak">❌</button>
            </form>
            <?php endif; ?>
            <a href="pengajuan.php?hapus=<?= $d['id_pengajuan'] ?>"
               class="btn btn-ghost btn-sm"
               onclick="return confirm('Hapus pengajuan ini permanen?')"
               title="Hapus">🗑️</a>
          </td>
        </tr>
        <?php endwhile; ?>
        <?php if($total==0): ?>
        <tr><td colspan="8">
          <div class="empty-state"><div class="icon">📋</div><p>Tidak ada pengajuan ditemukan.</p></div>
        </td></tr>
        <?php endif; ?>
      </table>
    </div>
  </div>

</div>
</body>
</html>