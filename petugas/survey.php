<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

$msg = '';
$err = '';

/*
|--------------------------------------------------------------------------
| TAMBAH JADWAL SURVEY
|--------------------------------------------------------------------------
*/
if (isset($_POST['simpan'])) {
    $nik     = isset($_POST['id_masyarakat'])
               ? mysqli_real_escape_string($conn, $_POST['id_masyarakat'])
               : '';
    $tanggal = $_POST['tanggal_survey'];
    $petugas = mysqli_real_escape_string($conn, $_POST['petugas']);

    if ($nik === '') {
        $err = "Pilih masyarakat terlebih dahulu";
    } else {
        mysqli_query($conn, "
            INSERT INTO survey
                (id_masyarakat, tanggal_survey, petugas, hasil_survey, status)
            VALUES
                ('$nik', '$tanggal', '$petugas', '', 'jadwal')
        ");
        $msg = "Jadwal survey berhasil ditambahkan";
    }
}

/*
|--------------------------------------------------------------------------
| SIMPAN HASIL SURVEY + FOTO RUMAH
|--------------------------------------------------------------------------
*/
if (isset($_POST['selesai_hasil'])) {
    $id    = (int)$_POST['id_survey'];
    $hasil = mysqli_real_escape_string($conn, $_POST['hasil_survey']);
    $rek   = mysqli_real_escape_string($conn, $_POST['rekomendasi'] ?? '');
    $layak = mysqli_real_escape_string($conn, $_POST['kelayakan'] ?? 'tidak_layak');

    // Upload foto rumah (bisa lebih dari 1, max 3)
    $foto_names = [];
    if (!empty($_FILES['foto_rumah']['name'][0])) {
        $upload_dir = '../uploads/foto_survey/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $allowed = ['jpg','jpeg','png','webp'];
        foreach ($_FILES['foto_rumah']['tmp_name'] as $i => $tmp) {
            if ($_FILES['foto_rumah']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $ext = strtolower(pathinfo($_FILES['foto_rumah']['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) continue;
            if ($_FILES['foto_rumah']['size'][$i] > 5 * 1024 * 1024) continue; // max 5MB

            $fname = 'survey_' . $id . '_' . time() . '_' . $i . '.' . $ext;
            if (move_uploaded_file($tmp, $upload_dir . $fname)) {
                $foto_names[] = $fname;
            }
        }
    }
    $foto_json = !empty($foto_names) ? mysqli_real_escape_string($conn, json_encode($foto_names)) : '';

    mysqli_query($conn, "
        UPDATE survey
        SET    status       = 'selesai',
               hasil_survey = '$hasil',
               rekomendasi  = '$rek',
               kelayakan    = '$layak',
               foto_rumah   = '$foto_json'
        WHERE  id_survey    = $id
    ");

    $msg = "Hasil survey berhasil disimpan";
    header("Location: survey.php?msg=selesai");
    exit;
}

/*
|--------------------------------------------------------------------------
| HAPUS FOTO SATU PER SATU
|--------------------------------------------------------------------------
*/
if (isset($_GET['hapus_foto'])) {
    $id    = (int)$_GET['hapus_foto'];
    $fname = basename($_GET['file'] ?? '');
    if ($id && $fname) {
        $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT foto_rumah FROM survey WHERE id_survey=$id"));
        $fotos = json_decode($row['foto_rumah'] ?? '[]', true);
        $fotos = array_values(array_filter($fotos, fn($f) => $f !== $fname));
        $new   = mysqli_real_escape_string($conn, json_encode($fotos));
        mysqli_query($conn, "UPDATE survey SET foto_rumah='$new' WHERE id_survey=$id");
        @unlink('../uploads/foto_survey/' . $fname);
    }
    header("Location: survey.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| UPDATE STATUS MULAI PROSES
|--------------------------------------------------------------------------
*/
if (isset($_GET['proses'])) {
    $id = (int)$_GET['proses'];
    mysqli_query($conn, "UPDATE survey SET status='proses' WHERE id_survey=$id");
    header("Location: survey.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| HAPUS SURVEY
|--------------------------------------------------------------------------
*/
if (isset($_GET['hapus'])) {
    $id  = (int)$_GET['hapus'];
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT foto_rumah FROM survey WHERE id_survey=$id"));
    // Hapus file foto dulu
    if (!empty($row['foto_rumah'])) {
        foreach (json_decode($row['foto_rumah'], true) as $f) {
            @unlink('../uploads/foto_survey/' . $f);
        }
    }
    mysqli_query($conn, "DELETE FROM survey WHERE id_survey=$id");
    header("Location: survey.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
*/
$filterStatus = $_GET['status'] ?? '';
$whereFilter  = $filterStatus
    ? "WHERE s.status='".mysqli_real_escape_string($conn,$filterStatus)."'"
    : '';

$data = mysqli_query($conn, "
    SELECT s.*, m.nama, m.alamat, m.no_hp
    FROM   survey s
    LEFT JOIN masyarakat m ON s.id_masyarakat = m.nik
    $whereFilter
    ORDER BY s.id_survey DESC
");

$jadwal  = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM survey WHERE status='jadwal'"))['total']  ?? 0);
$proses  = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM survey WHERE status='proses'"))['total']  ?? 0);
$selesai = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM survey WHERE status='selesai'"))['total'] ?? 0);

if (isset($_GET['msg']) && $_GET['msg'] === 'selesai') $msg = "Hasil survey berhasil disimpan";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Survey Lapangan</title>
    <link rel="stylesheet" href="petugas_style.css">
    <style>
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:999; justify-content:center; align-items:center; overflow-y:auto; padding:20px 0; }
        .modal-overlay.open { display:flex; }
        .modal { background:#fff; border-radius:16px; padding:28px 32px; max-width:560px; width:95%; box-shadow:0 10px 40px rgba(0,0,0,.25); margin:auto; }
        .modal h3 { margin:0 0 18px; font-size:18px; }
        .modal .field { margin-bottom:14px; }
        .modal .field label { display:block; font-size:13px; color:#374151; font-weight:600; margin-bottom:6px; }
        .modal textarea { width:100%; border:1.5px solid #d1d5db; border-radius:8px; padding:10px; font-size:14px; resize:vertical; min-height:80px; box-sizing:border-box; }
        .modal textarea:focus { outline:none; border-color:#22c55e; }
        .modal select { width:100%; padding:10px; border:1.5px solid #d1d5db; border-radius:8px; font-size:14px; }
        .modal select:focus { outline:none; border-color:#22c55e; }
        .btn-close { background:#f3f4f6; color:#374151; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; font-size:14px; }
        .btn-ghost { background:#f3f4f6; color:#374151; border:1px solid #d1d5db; padding:6px 10px; border-radius:8px; cursor:pointer; font-size:12px; text-decoration:none; display:inline-block; }
        /* Kelayakan badge */
        .badge-layak    { background:#dcfce7; color:#166534; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; }
        .badge-tdk-layak{ background:#fee2e2; color:#991b1b; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; }
        /* Foto preview upload */
        .foto-preview-wrap { display:flex; gap:8px; flex-wrap:wrap; margin-top:8px; }
        .foto-preview-wrap img { width:80px; height:80px; object-fit:cover; border-radius:8px; border:2px solid #e5e7eb; }
        /* Foto grid di detail */
        .foto-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(110px,1fr)); gap:8px; margin-top:8px; }
        .foto-grid img { width:100%; height:100px; object-fit:cover; border-radius:8px; border:2px solid #e5e7eb; cursor:zoom-in; }
        /* Lightbox */
        #lightbox { display:none; position:fixed; inset:0; background:rgba(0,0,0,.85); z-index:9999; justify-content:center; align-items:center; }
        #lightbox.open { display:flex; }
        #lightbox img { max-width:90vw; max-height:90vh; border-radius:8px; }
        #lightbox span { position:absolute; top:16px; right:24px; color:#fff; font-size:30px; cursor:pointer; }
        /* Filter pills */
        .filter-pill { display:inline-flex; gap:6px; flex-wrap:wrap; }
        .filter-pill a { padding:6px 14px; border-radius:20px; font-size:13px; font-weight:500; text-decoration:none; background:#f3f4f6; color:#374151; border:1.5px solid transparent; }
        .filter-pill a.active { background:#0f172a; color:#fff; }
        /* Detail rows */
        .detail-row { display:flex; gap:8px; margin-bottom:8px; font-size:13px; }
        .detail-lbl { color:#6b7280; min-width:110px; flex-shrink:0; }
        .detail-val { color:#111827; font-weight:600; }
        /* File input */
        input[type=file] { padding:8px; border:1.5px dashed #d1d5db; border-radius:8px; width:100%; font-size:13px; cursor:pointer; }
        input[type=file]:hover { border-color:#22c55e; }
    </style>
</head>
<body>

<?php include '_sidebar.php'; ?>

<div class="main">

    <div class="page-header">
        <div>
            <h1>Survey Lapangan</h1>
            <p>Kelola jadwal dan hasil survey rumah tangga</p>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($err): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <!-- STATISTIK -->
    <div class="stats-grid">
        <div class="stat-card yellow">
            <div class="stat-icon">📅</div>
            <div class="stat-num"><?= $jadwal ?></div>
            <div class="stat-label">Jadwal Survey</div>
        </div>
        <div class="stat-card navy">
            <div class="stat-icon">🏠</div>
            <div class="stat-num"><?= $proses ?></div>
            <div class="stat-label">Sedang Proses</div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon">✅</div>
            <div class="stat-num"><?= $selesai ?></div>
            <div class="stat-label">Selesai</div>
        </div>
    </div>

    <!-- FORM TAMBAH JADWAL -->
    <div class="card">
        <div class="card-title">➕ Tambah Jadwal Survey</div>
        <form method="POST">
            <div class="form-grid">
                <div class="field">
                    <label>Nama Masyarakat</label>
                    <select name="id_masyarakat" required>
                        <option value="">-- Pilih Masyarakat --</option>
                        <?php
                        $q = mysqli_query($conn, "SELECT nik, nama FROM masyarakat ORDER BY nama");
                        while ($m = mysqli_fetch_assoc($q)):
                        ?>
                        <option value="<?= htmlspecialchars($m['nik']) ?>">
                            <?= htmlspecialchars($m['nama']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Tanggal Survey</label>
                    <input type="date" name="tanggal_survey" min="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="field">
                    <label>Petugas</label>
                    <input type="text" name="petugas"
                           value="<?= htmlspecialchars($_SESSION['nama']) ?>" readonly>
                </div>
            </div>
            <button type="submit" name="simpan" class="btn btn-green">💾 Simpan Jadwal</button>
        </form>
    </div>

    <!-- FILTER STATUS -->
    <div class="card" style="padding:14px 20px">
        <div class="filter-pill">
            <a href="survey.php"      class="<?= $filterStatus===''        ? 'active':'' ?>">Semua</a>
            <a href="?status=jadwal"  class="<?= $filterStatus==='jadwal'  ? 'active':'' ?>">📅 Jadwal</a>
            <a href="?status=proses"  class="<?= $filterStatus==='proses'  ? 'active':'' ?>">🏠 Proses</a>
            <a href="?status=selesai" class="<?= $filterStatus==='selesai' ? 'active':'' ?>">✅ Selesai</a>
        </div>
    </div>

    <!-- TABEL -->
    <div class="card">
        <div class="card-title">📋 Daftar Survey</div>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Alamat</th>
                    <th>Tanggal</th>
                    <th>Petugas</th>
                    <th>Kelayakan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>

                <?php
                $rows = [];
                while ($d = mysqli_fetch_assoc($data)) $rows[] = $d;
                if (empty($rows)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;padding:24px;color:#9ca3af">
                        Tidak ada data survey
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($rows as $d): $sid = (int)$d['id_survey']; ?>
                <tr>
                    <td><?= $sid ?></td>
                    <td><b><?= htmlspecialchars($d['nama'] ?? '-') ?></b></td>
                    <td style="font-size:12px;color:#6b7280;max-width:160px;white-space:normal">
                        <?= htmlspecialchars($d['alamat'] ?? '-') ?>
                    </td>
                    <td><?= $d['tanggal_survey'] ?></td>
                    <td><?= htmlspecialchars($d['petugas']) ?></td>
                    <td>
                        <?php if ($d['status'] === 'selesai'): ?>
                            <?php if (($d['kelayakan'] ?? '') === 'layak'): ?>
                                <span class="badge-layak">✅ Layak</span>
                            <?php elseif (($d['kelayakan'] ?? '') === 'tidak_layak'): ?>
                                <span class="badge-tdk-layak">❌ Tdk Layak</span>
                            <?php else: ?>
                                <span style="color:#9ca3af">—</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color:#9ca3af">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($d['status'] === 'jadwal'): ?>
                            <span class="badge badge-pending">Jadwal</span>
                        <?php elseif ($d['status'] === 'proses'): ?>
                            <span class="badge badge-navy">Proses</span>
                        <?php else: ?>
                            <span class="badge badge-diterima">Selesai</span>
                        <?php endif; ?>
                    </td>
                    <td style="white-space:nowrap">

                        <?php if ($d['status'] === 'jadwal'): ?>
                            <a href="?proses=<?= $sid ?>"
                               class="btn btn-yellow btn-sm"
                               onclick="return confirm('Mulai survey ini?')">▶ Mulai</a>
                        <?php endif; ?>

                        <?php if ($d['status'] === 'proses'): ?>
                            <button class="btn btn-green btn-sm"
                                    onclick="bukaModalHasil(<?= $sid ?>)">
                                ✔ Selesai
                            </button>
                        <?php endif; ?>

                        <?php if ($d['status'] === 'selesai'): ?>
                            <button class="btn-ghost"
                                    onclick='bukaDetail(<?= json_encode([
                                        "id"      => $sid,
                                        "nama"    => $d['nama'] ?? '-',
                                        "alamat"  => $d['alamat'] ?? '-',
                                        "tgl"     => $d['tanggal_survey'],
                                        "petugas" => $d['petugas'],
                                        "hasil"   => $d['hasil_survey'] ?? '',
                                        "rek"     => $d['rekomendasi'] ?? '',
                                        "layak"   => $d['kelayakan'] ?? '',
                                        "fotos"   => json_decode($d['foto_rumah'] ?? '[]', true),
                                    ]) ?>)'>
                                📋 Detail
                            </button>
                        <?php endif; ?>

                        <a href="?hapus=<?= $sid ?>"
                           class="btn btn-red btn-sm"
                           onclick="return confirm('Hapus survey ini? Semua foto akan ikut terhapus.')">🗑️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </table>
        </div>
    </div>

</div><!-- /main -->

<!-- ===== MODAL ISI HASIL SURVEY ===== -->
<div class="modal-overlay" id="modalHasil">
    <div class="modal">
        <h3>📝 Isi Hasil Survey</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_survey" id="inputIdSurvey">

            <div class="field">
                <label>Hasil Survey / Temuan Lapangan</label>
                <textarea name="hasil_survey" required
                          placeholder="Tuliskan kondisi rumah, perabot, ekonomi keluarga, dll..."></textarea>
            </div>

            <div class="field">
                <label>Rekomendasi</label>
                <textarea name="rekomendasi"
                          placeholder="Catatan tambahan petugas..."></textarea>
            </div>

            <div class="field">
                <label>Kelayakan Penerima Bantuan</label>
                <select name="kelayakan" required>
                    <option value="">-- Pilih Kelayakan --</option>
                    <option value="layak">✅ Layak Menerima Bantuan</option>
                    <option value="tidak_layak">❌ Tidak Layak Menerima Bantuan</option>
                </select>
            </div>

            <div class="field">
                <label>📷 Foto Rumah (max 3 foto, jpg/png, masing-masing max 5MB)</label>
                <input type="file" name="foto_rumah[]" accept="image/*" multiple
                       onchange="previewFoto(this)">
                <div class="foto-preview-wrap" id="previewWrap"></div>
            </div>

            <div style="display:flex;gap:10px;margin-top:12px">
                <button type="submit" name="selesai_hasil" class="btn btn-green">💾 Simpan & Selesai</button>
                <button type="button" class="btn-close" onclick="tutupModalHasil()">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL DETAIL HASIL ===== -->
<div class="modal-overlay" id="modalDetail" onclick="if(event.target===this)tutupModalDetail()">
    <div class="modal" style="max-width:600px">
        <h3>📋 Detail Hasil Survey</h3>
        <div class="detail-row"><span class="detail-lbl">Nama</span>    <span class="detail-val" id="dNama"></span></div>
        <div class="detail-row"><span class="detail-lbl">Alamat</span>  <span class="detail-val" id="dAlamat"></span></div>
        <div class="detail-row"><span class="detail-lbl">Tanggal</span> <span class="detail-val" id="dTgl"></span></div>
        <div class="detail-row"><span class="detail-lbl">Petugas</span> <span class="detail-val" id="dPetugas"></span></div>
        <div class="detail-row">
            <span class="detail-lbl">Kelayakan</span>
            <span class="detail-val" id="dLayak"></span>
        </div>

        <div style="margin-top:12px">
            <div style="font-size:13px;color:#6b7280;font-weight:600;margin-bottom:6px">Hasil Survey</div>
            <div id="dHasil" style="background:#f8fafc;border-radius:8px;padding:10px;font-size:13px;line-height:1.7;white-space:pre-wrap"></div>
        </div>

        <div style="margin-top:12px">
            <div style="font-size:13px;color:#6b7280;font-weight:600;margin-bottom:6px">Rekomendasi</div>
            <div id="dRek" style="background:#f0fdf4;border-radius:8px;padding:10px;font-size:13px;line-height:1.7;color:#166534;white-space:pre-wrap"></div>
        </div>

        <div style="margin-top:14px">
            <div style="font-size:13px;color:#6b7280;font-weight:600;margin-bottom:6px">📷 Foto Rumah</div>
            <div class="foto-grid" id="dFotoGrid">
                <span style="color:#9ca3af;font-size:13px">Tidak ada foto</span>
            </div>
        </div>

        <div style="margin-top:18px">
            <button class="btn-close" onclick="tutupModalDetail()">Tutup</button>
        </div>
    </div>
</div>

<!-- ===== LIGHTBOX ===== -->
<div id="lightbox" onclick="closeLightbox()">
    <span onclick="closeLightbox()">✕</span>
    <img id="lightboxImg" src="" alt="Foto Rumah">
</div>

<script>
const BASE_FOTO = '../uploads/foto_survey/';

/* ---- Modal Hasil ---- */
function bukaModalHasil(id) {
    document.getElementById('inputIdSurvey').value = id;
    document.getElementById('previewWrap').innerHTML = '';
    document.getElementById('modalHasil').classList.add('open');
}
function tutupModalHasil() {
    document.getElementById('modalHasil').classList.remove('open');
}

/* ---- Preview foto sebelum upload ---- */
function previewFoto(input) {
    const wrap = document.getElementById('previewWrap');
    wrap.innerHTML = '';
    const files = Array.from(input.files).slice(0, 3); // max 3
    files.forEach(file => {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        wrap.appendChild(img);
    });
}

/* ---- Modal Detail ---- */
function bukaDetail(d) {
    document.getElementById('dNama').textContent    = d.nama;
    document.getElementById('dAlamat').textContent  = d.alamat;
    document.getElementById('dTgl').textContent     = d.tgl;
    document.getElementById('dPetugas').textContent = d.petugas;
    document.getElementById('dHasil').textContent   = d.hasil || '-';
    document.getElementById('dRek').textContent     = d.rek   || '-';

    // Kelayakan
    const layakEl = document.getElementById('dLayak');
    if (d.layak === 'layak') {
        layakEl.innerHTML = '<span style="background:#dcfce7;color:#166534;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600">✅ Layak</span>';
    } else if (d.layak === 'tidak_layak') {
        layakEl.innerHTML = '<span style="background:#fee2e2;color:#991b1b;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600">❌ Tidak Layak</span>';
    } else {
        layakEl.textContent = '—';
    }

    // Foto
    const grid = document.getElementById('dFotoGrid');
    grid.innerHTML = '';
    if (d.fotos && d.fotos.length > 0) {
        d.fotos.forEach(fname => {
            const img = document.createElement('img');
            img.src   = BASE_FOTO + fname;
            img.title = 'Klik untuk perbesar';
            img.onclick = () => bukaLightbox(BASE_FOTO + fname);
            grid.appendChild(img);
        });
    } else {
        grid.innerHTML = '<span style="color:#9ca3af;font-size:13px">Tidak ada foto</span>';
    }

    document.getElementById('modalDetail').classList.add('open');
}
function tutupModalDetail() {
    document.getElementById('modalDetail').classList.remove('open');
}

/* ---- Lightbox ---- */
function bukaLightbox(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightbox').classList.add('open');
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('open');
}
</script>
</body>
</html>