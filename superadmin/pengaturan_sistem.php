<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

$msg = $err = "";

// ── Cek apakah tabel pengaturan punya kolom yang benar ──
// Ambil semua pengaturan ke array asosiatif
$pengaturan = [];
$res = mysqli_query($conn, "SELECT * FROM pengaturan");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        // Coba deteksi struktur: key-value atau kolom langsung
        if (isset($row['nama_key'])) {
            $pengaturan[$row['nama_key']] = $row['nilai'];
        } elseif (isset($row['key'])) {
            $pengaturan[$row['key']] = $row['value'];
        } else {
            // Struktur kolom langsung — ambil baris pertama saja
            $pengaturan = $row;
            break;
        }
    }
}

// ── Simpan pengaturan ──
if (isset($_POST['simpan'])) {
    csrf_verify();

    $fields = [
        'nama_aplikasi'    => trim($_POST['nama_aplikasi']    ?? ''),
        'nama_instansi'    => trim($_POST['nama_instansi']    ?? ''),
        'email_admin'      => trim($_POST['email_admin']      ?? ''),
        'telepon_admin'    => trim($_POST['telepon_admin']    ?? ''),
        'alamat_instansi'  => trim($_POST['alamat_instansi']  ?? ''),
        'batas_pengajuan'  => (int)($_POST['batas_pengajuan'] ?? 1),
        'mode_maintenance' => isset($_POST['mode_maintenance']) ? '1' : '0',
        'tahun_anggaran'   => (int)($_POST['tahun_anggaran']  ?? date('Y')),
    ];

    // Upsert key-value (asumsi kolom: nama_key, nilai, id)
    $ok = true;
    foreach ($fields as $key => $val) {
        $key_esc = mysqli_real_escape_string($conn, $key);
        $val_esc = mysqli_real_escape_string($conn, $val);
        // Coba INSERT ... ON DUPLICATE KEY UPDATE
        $q = mysqli_query($conn, "
            INSERT INTO pengaturan (nama_key, nilai)
            VALUES ('$key_esc', '$val_esc')
            ON DUPLICATE KEY UPDATE nilai='$val_esc'
        ");
        if (!$q) {
            // Fallback: UPDATE saja
            mysqli_query($conn, "UPDATE pengaturan SET nilai='$val_esc' WHERE nama_key='$key_esc'");
        }
    }

    // Reload
    $pengaturan = [];
    $res = mysqli_query($conn, "SELECT * FROM pengaturan");
    if ($res) while ($row = mysqli_fetch_assoc($res)) $pengaturan[$row['nama_key']] = $row['nilai'];

    $msg = "Pengaturan sistem berhasil disimpan.";
}

function pg($key, $default = '') {
    global $pengaturan;
    return htmlspecialchars($pengaturan[$key] ?? $default);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Super Admin — Pengaturan Sistem</title>
<link rel="stylesheet" href="sa_style.css">
<style>
.setting-section { margin-bottom: 32px; }
.setting-section-title {
  font-size: 13px; font-weight: 700; text-transform: uppercase;
  letter-spacing: 1px; color: var(--sa-gold);
  margin-bottom: 16px; padding-bottom: 10px;
  border-bottom: 1px solid rgba(232,184,75,0.2);
}
.toggle-wrap { display:flex;align-items:center;gap:16px;padding:14px 16px;background:var(--sa-bg);border:1.5px solid var(--sa-border);border-radius:10px; }
.toggle-label { flex:1 }
.toggle-label strong { display:block;font-size:14px;color:var(--sa-text) }
.toggle-label span { font-size:12px;color:var(--sa-muted) }
.toggle { position:relative;width:44px;height:24px;flex-shrink:0 }
.toggle input { opacity:0;width:0;height:0;position:absolute }
.slider { position:absolute;inset:0;background:var(--sa-border);border-radius:12px;cursor:pointer;transition:.3s }
.slider::before { content:'';position:absolute;height:18px;width:18px;bottom:3px;left:3px;background:#fff;border-radius:50%;transition:.3s }
.toggle input:checked + .slider { background:var(--sa-gold) }
.toggle input:checked + .slider::before { transform:translateX(20px);background:#0d1117 }
</style>
</head>
<body>
<?php include '_sidebar.php'; ?>
<main class="sa-main">

  <div class="sa-topbar">
    <div>
      <h1 class="sa-page-title">Pengaturan Sistem</h1>
      <p class="sa-page-sub">Konfigurasi global aplikasi SIBANSOS</p>
    </div>
    <div class="sa-breadcrumb">
      <a href="index.php">Dashboard</a> / Pengaturan
    </div>
  </div>

  <?php if ($msg): ?><div class="sa-alert sa-alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="sa-alert sa-alert-error">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <form method="POST">
    <?= csrf_field() ?>
    <div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start">

      <!-- Kiri: Setting utama -->
      <div>
        <!-- Info Aplikasi -->
        <div class="sa-card">
          <div class="setting-section-title">🏛️ Informasi Aplikasi</div>
          <div class="sa-form-grid" style="margin-bottom:20px">
            <div class="sa-field">
              <label for="nama_aplikasi">Nama Aplikasi</label>
              <input type="text" id="nama_aplikasi" name="nama_aplikasi" value="<?= pg('nama_aplikasi','SIBANSOS') ?>">
            </div>
            <div class="sa-field">
              <label for="tahun_anggaran">Tahun Anggaran</label>
              <input type="number" id="tahun_anggaran" name="tahun_anggaran" value="<?= pg('tahun_anggaran', date('Y')) ?>" min="2020" max="2099">
            </div>
          </div>
          <div class="sa-field" style="margin-bottom:20px">
            <label for="nama_instansi">Nama Instansi / Dinas</label>
            <input type="text" id="nama_instansi" name="nama_instansi" value="<?= pg('nama_instansi') ?>" placeholder="Dinas Sosial Kota/Kabupaten...">
          </div>
          <div class="sa-field">
            <label for="alamat_instansi">Alamat Instansi</label>
            <textarea name="alamat_instansi" id="alamat_instansi" rows="3"
              style="width:100%;padding:12px 14px;background:var(--sa-bg);border:1.5px solid var(--sa-border);border-radius:10px;font-size:14px;font-family:Sora,sans-serif;color:var(--sa-text);outline:none;resize:vertical;transition:border-color .2s"
              onfocus="this.style.borderColor='var(--sa-gold)'" onblur="this.style.borderColor='var(--sa-border)'"
              placeholder="Alamat lengkap instansi..."><?= pg('alamat_instansi') ?></textarea>
          </div>
        </div>

        <!-- Kontak -->
        <div class="sa-card">
          <div class="setting-section-title">📞 Kontak Administrator</div>
          <div class="sa-form-grid">
            <div class="sa-field">
              <label for="email_admin">Email Admin</label>
              <input type="email" id="email_admin" name="email_admin" value="<?= pg('email_admin') ?>" placeholder="admin@sibansos.go.id">
            </div>
            <div class="sa-field">
              <label for="telepon_admin">Telepon / WhatsApp</label>
              <input type="text" id="telepon_admin" name="telepon_admin" value="<?= pg('telepon_admin') ?>" placeholder="08xxxxxxxxxx">
            </div>
          </div>
        </div>

        <!-- Pengaturan Operasional -->
        <div class="sa-card">
          <div class="setting-section-title">⚙️ Pengaturan Operasional</div>
          <div class="sa-field" style="margin-bottom:20px">
            <label for="batas_pengajuan">Batas Pengajuan per Masyarakat (per tahun)</label>
            <input type="number" id="batas_pengajuan" name="batas_pengajuan" value="<?= pg('batas_pengajuan','1') ?>" min="1" max="10">
          </div>

          <!-- Toggle Maintenance -->
          <div class="toggle-wrap">
            <div class="toggle-label">
              <strong>Mode Maintenance</strong>
              <span>Nonaktifkan akses publik sementara untuk pemeliharaan sistem</span>
            </div>
            <label class="toggle">
              <input type="checkbox" name="mode_maintenance" id="mode_maintenance"
                <?= ($pengaturan['mode_maintenance'] ?? '0') === '1' ? 'checked' : '' ?>>
              <span class="slider"></span>
            </label>
          </div>
        </div>
      </div>

      <!-- Kanan: Info + Aksi -->
      <div>
        <div class="sa-card">
          <div class="sa-card-title" style="margin-bottom:16px">💾 Simpan Perubahan</div>
          <p style="font-size:13px;color:var(--sa-muted);margin-bottom:20px;line-height:1.7">
            Perubahan pengaturan langsung berlaku setelah disimpan. Pastikan data yang dimasukkan sudah benar.
          </p>
          <button type="submit" name="simpan" class="sa-btn sa-btn-gold" style="width:100%;justify-content:center;font-size:15px;padding:13px">
            💾 Simpan Pengaturan
          </button>
        </div>

        <!-- Info Sistem -->
        <div class="sa-card" style="background:rgba(59,130,246,0.05);border-color:rgba(59,130,246,0.2)">
          <div class="sa-card-title" style="color:#60a5fa;margin-bottom:16px">ℹ️ Info Sistem</div>
          <table class="sa-table" style="font-size:12px">
            <tr>
              <td style="color:var(--sa-muted);padding:8px 0;border:none">PHP</td>
              <td style="padding:8px 0;border:none;font-family:monospace;color:var(--sa-gold)"><?= phpversion() ?></td>
            </tr>
            <tr>
              <td style="color:var(--sa-muted);padding:8px 0;border:none">Server</td>
              <td style="padding:8px 0;border:none;font-family:monospace;font-size:11px;color:var(--sa-muted)"><?= $_SERVER['SERVER_SOFTWARE'] ?></td>
            </tr>
            <tr>
              <td style="color:var(--sa-muted);padding:8px 0;border:none">MySQL</td>
              <td style="padding:8px 0;border:none;font-family:monospace;color:var(--sa-gold)"><?= mysqli_get_server_info($conn) ?></td>
            </tr>
            <tr>
              <td style="color:var(--sa-muted);padding:8px 0;border:none">Tanggal</td>
              <td style="padding:8px 0;border:none;font-size:11px;color:var(--sa-muted)"><?= date('d/m/Y H:i:s') ?></td>
            </tr>
          </table>
        </div>

        <div class="sa-card" style="background:rgba(239,68,68,0.05);border-color:rgba(239,68,68,0.2)">
          <div class="sa-card-title" style="color:#f87171;margin-bottom:12px">⚠️ Zona Berbahaya</div>
          <p style="font-size:12px;color:var(--sa-muted);margin-bottom:14px;line-height:1.6">Aksi berikut bersifat permanen dan tidak dapat dibatalkan.</p>
          <a href="log_aktivitas.php" class="sa-btn sa-btn-red" style="width:100%;justify-content:center;margin-bottom:10px">
            🗑️ Kelola Log Aktivitas
          </a>
        </div>
      </div>
    </div>
  </form>

</main>
</body>
</html>