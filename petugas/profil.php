<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

$uid = intval($_SESSION['user_id'] ?? 0);
$msg = '';
$err = '';

// ── Auto-tambah kolom jika belum ada ─────────────────────────
$cols_res  = mysqli_query($conn, "DESCRIBE users");
$col_names = [];
if ($cols_res) {
    while ($c = mysqli_fetch_assoc($cols_res)) $col_names[] = $c['Field'];
}
foreach (['email'=>'VARCHAR(100)', 'no_hp'=>'VARCHAR(20)', 'foto'=>'VARCHAR(255)'] as $col => $def) {
    if (!in_array($col, $col_names)) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN `$col` $def DEFAULT NULL");
        $col_names[] = $col;
    }
}

// ── Cari nama primary key ─────────────────────────────────────
$pk_col = 'id';
foreach (['id', 'user_id', 'id_user'] as $pk) {
    if (in_array($pk, $col_names)) { $pk_col = $pk; break; }
}

// ── Ambil data user — PAKSA jadi array ───────────────────────
$nama_user = strval($_SESSION['nama'] ?? 'Petugas');
$username  = strval($_SESSION['username'] ?? '');
$email     = '';
$no_hp     = '';
$foto_db   = '';
$password_db = '';

$q = mysqli_query($conn, "SELECT * FROM users WHERE `$pk_col` = $uid LIMIT 1");
if ($q) {
    $row = mysqli_fetch_assoc($q);
    if (is_array($row)) {
        $nama_user   = strval($row['nama']     ?? $nama_user);
        $username    = strval($row['username'] ?? $username);
        $email       = strval($row['email']    ?? '');
        $no_hp       = strval($row['no_hp']    ?? '');
        $foto_db     = strval($row['foto']     ?? '');
        $password_db = strval($row['password'] ?? '');
    }
}

// ── SIMPAN PROFIL ─────────────────────────────────────────────
if (isset($_POST['simpan_profil'])) {
    $nama_baru  = trim(strval($_POST['nama']  ?? ''));
    $email_baru = trim(strval($_POST['email'] ?? ''));
    $nohp_baru  = trim(strval($_POST['no_hp'] ?? ''));

    if ($nama_baru === '') {
        $err = 'Nama tidak boleh kosong.';
    } else {
        $foto_baru = $foto_db;

        if (!empty($_FILES['foto']['name'])) {
            $allowed = ['jpg','jpeg','png','webp'];
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $err = 'Format foto harus JPG, PNG, atau WEBP.';
            } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
                $err = 'Ukuran foto maksimal 2 MB.';
            } else {
                $dir = '../uploads/foto_profil/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                if ($foto_baru && file_exists($dir . $foto_baru)) unlink($dir . $foto_baru);
                $namaFile = 'petugas_' . $uid . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['foto']['tmp_name'], $dir . $namaFile);
                $foto_baru = $namaFile;
            }
        }

        if ($err === '') {
            $n  = mysqli_real_escape_string($conn, $nama_baru);
            $e  = mysqli_real_escape_string($conn, $email_baru);
            $hp = mysqli_real_escape_string($conn, $nohp_baru);
            $f  = mysqli_real_escape_string($conn, $foto_baru);
            mysqli_query($conn, "UPDATE users SET nama='$n', email='$e', no_hp='$hp', foto='$f' WHERE `$pk_col`=$uid");
            $_SESSION['nama'] = $nama_baru;
            $nama_user = $nama_baru;
            $email     = $email_baru;
            $no_hp     = $nohp_baru;
            $foto_db   = $foto_baru;
            $msg = 'Profil berhasil diperbarui.';
        }
    }
}

// ── UBAH PASSWORD ─────────────────────────────────────────────
if (isset($_POST['simpan_password'])) {
    $pass_lama  = strval($_POST['pass_lama']  ?? '');
    $pass_baru  = strval($_POST['pass_baru']  ?? '');
    $pass_ulang = strval($_POST['pass_ulang'] ?? '');

    $cocok = ($password_db !== '' && $password_db === $pass_lama)
          || ($password_db !== '' && $password_db === md5($pass_lama))
          || ($password_db !== '' && password_verify($pass_lama, $password_db));

    if (!$cocok) {
        $err = 'Password lama tidak sesuai.';
    } elseif (strlen($pass_baru) < 6) {
        $err = 'Password baru minimal 6 karakter.';
    } elseif ($pass_baru !== $pass_ulang) {
        $err = 'Konfirmasi password tidak cocok.';
    } else {
        $hash = password_hash($pass_baru, PASSWORD_DEFAULT);
        $hash_esc = mysqli_real_escape_string($conn, $hash);
        mysqli_query($conn, "UPDATE users SET password='$hash_esc' WHERE `$pk_col`=$uid");
        $msg = 'Password berhasil diubah.';
    }
}

// ── Helper foto ───────────────────────────────────────────────
$foto_src = ($foto_db !== '' && file_exists('../uploads/foto_profil/' . $foto_db))
    ? '../uploads/foto_profil/' . htmlspecialchars($foto_db)
    : null;
$inisial = strtoupper(substr($nama_user, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Profil Saya — SIBANSOS</title>
<link rel="stylesheet" href="petugas_style.css">
<style>
.profil-wrap{display:grid;grid-template-columns:300px 1fr;gap:24px;align-items:start}
.foto-card{background:white;border-radius:16px;padding:32px 24px;box-shadow:0 2px 12px rgba(0,0,0,.07);text-align:center;position:sticky;top:24px}
.foto-wrap{position:relative;display:inline-block;margin-bottom:16px}
.foto-avatar{width:110px;height:110px;border-radius:50%;object-fit:cover;border:4px solid #22c55e;display:block}
.foto-inisial{width:110px;height:110px;border-radius:50%;background:linear-gradient(135deg,#166534,#22c55e);display:flex;align-items:center;justify-content:center;font-size:42px;font-weight:700;color:white;border:4px solid #22c55e;margin:0 auto}
.foto-edit-btn{position:absolute;bottom:4px;right:4px;width:32px;height:32px;border-radius:50%;background:#166534;color:white;border:2px solid white;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:15px;transition:background .2s}
.foto-edit-btn:hover{background:#15803d}
#input-foto{display:none}
.profil-nama{font-size:18px;font-weight:700;color:#1f2937;margin-bottom:4px}
.profil-role{font-size:12px;background:#dcfce7;color:#166534;padding:3px 12px;border-radius:20px;display:inline-block;margin-bottom:20px;font-weight:600}
.info-item{display:flex;align-items:flex-start;gap:10px;padding:10px 0;border-bottom:1px solid #f1f5f9;text-align:left;font-size:13px}
.info-item:last-child{border-bottom:none}
.info-icon{font-size:17px;flex-shrink:0;margin-top:1px}
.info-label{color:#6b7280;font-size:11px}
.info-val{color:#1f2937;font-weight:500;word-break:break-all}
.tab-row{display:flex;gap:8px;margin-bottom:20px;border-bottom:2px solid #e5e7eb}
.tab-btn{padding:10px 20px;border:none;background:none;cursor:pointer;font-size:14px;font-weight:600;color:#6b7280;border-bottom:3px solid transparent;margin-bottom:-2px;transition:color .2s,border-color .2s;font-family:inherit}
.tab-btn.active{color:#166534;border-bottom-color:#166534}
.tab-pane{display:none}.tab-pane.active{display:block}
.strength-bar{height:5px;border-radius:20px;background:#e5e7eb;margin-top:6px;overflow:hidden}
.strength-fill{height:100%;border-radius:20px;transition:width .3s,background .3s;width:0%}
.field-hint{font-size:11px;color:#9ca3af;margin-top:4px}
.pass-wrap{position:relative}.pass-wrap input{padding-right:42px}
.pass-eye{position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;font-size:17px;user-select:none;color:#9ca3af}
@media(max-width:768px){.profil-wrap{grid-template-columns:1fr}.foto-card{position:static}}
</style>
</head>
<body>
<?php include '_sidebar.php'; ?>
<div class="main">

<div class="page-header">
    <div>
        <h1>👤 Profil Saya</h1>
        <p>Kelola informasi akun dan keamanan</p>
    </div>
    <div><?= date('d F Y') ?></div>
</div>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="profil-wrap">

    <!-- Kartu Foto -->
    <div class="foto-card">
        <div class="foto-wrap">
            <?php if ($foto_src): ?>
                <img id="foto-preview" class="foto-avatar" src="<?= $foto_src ?>">
            <?php else: ?>
                <div id="foto-inisial" class="foto-inisial"><?= htmlspecialchars($inisial) ?></div>
                <img id="foto-preview" class="foto-avatar" src="" style="display:none">
            <?php endif; ?>
            <label class="foto-edit-btn" for="input-foto" title="Ganti foto">✏️</label>
            <input type="file" id="input-foto" accept="image/*" onchange="previewFoto(this)">
        </div>
        <div class="profil-nama"><?= htmlspecialchars($nama_user) ?></div>
        <div class="profil-role">👮 Petugas</div>
        <div>
            <div class="info-item">
                <span class="info-icon">📧</span>
                <div>
                    <div class="info-label">Email</div>
                    <div class="info-val"><?= htmlspecialchars($email ?: '-') ?></div>
                </div>
            </div>
            <div class="info-item">
                <span class="info-icon">📱</span>
                <div>
                    <div class="info-label">No. HP</div>
                    <div class="info-val"><?= htmlspecialchars($no_hp ?: '-') ?></div>
                </div>
            </div>
            <div class="info-item">
                <span class="info-icon">🪪</span>
                <div>
                    <div class="info-label">Username</div>
                    <div class="info-val"><?= htmlspecialchars($username ?: '-') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Kanan -->
    <div>
        <div class="tab-row">
            <button class="tab-btn active" onclick="bukaTab('tab-profil',this)">📝 Edit Profil</button>
            <button class="tab-btn"        onclick="bukaTab('tab-password',this)">🔒 Ubah Password</button>
        </div>

        <!-- Tab Edit Profil -->
        <div class="tab-pane active" id="tab-profil">
            <div class="card">
                <div class="card-title">📝 Informasi Profil</div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="field">
                            <label>Nama Lengkap <span style="color:#dc2626">*</span></label>
                            <input type="text" name="nama" value="<?= htmlspecialchars($nama_user) ?>" required placeholder="Nama lengkap">
                        </div>
                        <div class="field">
                            <label>Username</label>
                            <input type="text" value="<?= htmlspecialchars($username ?: '-') ?>" disabled style="background:#f9fafb;color:#9ca3af;cursor:not-allowed">
                            <div class="field-hint">Username tidak dapat diubah.</div>
                        </div>
                        <div class="field">
                            <label>Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="contoh@email.com">
                        </div>
                        <div class="field">
                            <label>No. HP / WhatsApp</label>
                            <input type="tel" name="no_hp" value="<?= htmlspecialchars($no_hp) ?>" placeholder="08xxxxxxxxxx">
                        </div>
                        <div class="field" style="grid-column:1/-1">
                            <label>Foto Profil</label>
                            <input type="file" name="foto" accept="image/*"
                                   style="padding:8px;border:1.5px dashed #d1d5db;border-radius:10px;background:#fafafa">
                            <div class="field-hint">Format: JPG, PNG, WEBP · Maks. 2 MB</div>
                            <?php if ($foto_src): ?>
                            <div style="margin-top:10px;display:flex;align-items:center;gap:12px">
                                <img src="<?= $foto_src ?>" style="width:52px;height:52px;border-radius:50%;object-fit:cover;border:2px solid #22c55e">
                                <span style="font-size:13px;color:#6b7280">Foto saat ini</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="display:flex;gap:10px;margin-top:8px">
                        <button type="submit" name="simpan_profil" class="btn btn-green">💾 Simpan Perubahan</button>
                        <a href="index.php" class="btn" style="background:transparent;border:1px solid #d1d5db;color:#6b7280">Batal</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tab Ubah Password -->
        <div class="tab-pane" id="tab-password">
            <div class="card">
                <div class="card-title">🔒 Ubah Password</div>
                <form method="POST" style="max-width:460px">
                    <div style="display:flex;flex-direction:column;gap:18px">
                        <div class="field">
                            <label>Password Lama <span style="color:#dc2626">*</span></label>
                            <div class="pass-wrap">
                                <input type="password" name="pass_lama" id="pass_lama" required placeholder="Password lama">
                                <span class="pass-eye" onclick="togglePass('pass_lama',this)">👁️</span>
                            </div>
                        </div>
                        <div class="field">
                            <label>Password Baru <span style="color:#dc2626">*</span></label>
                            <div class="pass-wrap">
                                <input type="password" name="pass_baru" id="pass_baru" required placeholder="Min. 6 karakter" oninput="cekKekuatan(this.value)">
                                <span class="pass-eye" onclick="togglePass('pass_baru',this)">👁️</span>
                            </div>
                            <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
                            <div class="field-hint" id="strength-label">Masukkan password baru</div>
                        </div>
                        <div class="field">
                            <label>Konfirmasi Password <span style="color:#dc2626">*</span></label>
                            <div class="pass-wrap">
                                <input type="password" name="pass_ulang" id="pass_ulang" required placeholder="Ulangi password baru" oninput="cekUlang(this.value)">
                                <span class="pass-eye" onclick="togglePass('pass_ulang',this)">👁️</span>
                            </div>
                            <div class="field-hint" id="ulang-label"></div>
                        </div>
                        <div>
                            <button type="submit" name="simpan_password" class="btn btn-navy">🔐 Ubah Password</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card" style="border-left:4px solid #f59e0b;background:#fffbeb">
                <div style="font-size:14px;font-weight:700;margin-bottom:10px;color:#92400e">💡 Tips Password Aman</div>
                <ul style="font-size:13px;color:#78350f;padding-left:18px;line-height:2">
                    <li>Gunakan minimal 8 karakter</li>
                    <li>Kombinasikan huruf besar, kecil, angka &amp; simbol</li>
                    <li>Jangan gunakan tanggal lahir atau nama</li>
                    <li>Ganti password secara berkala</li>
                </ul>
            </div>
        </div>
    </div>
</div>
</div>

<script>
function bukaTab(id,btn){
    document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    btn.classList.add('active');
}
function previewFoto(input){
    const file=input.files[0];if(!file)return;
    const reader=new FileReader();
    reader.onload=e=>{
        const prev=document.getElementById('foto-preview');
        const init=document.getElementById('foto-inisial');
        prev.src=e.target.result;prev.style.display='block';
        if(init)init.style.display='none';
    };
    reader.readAsDataURL(file);
}
function togglePass(id,el){
    const inp=document.getElementById(id);
    inp.type=inp.type==='password'?'text':'password';
    el.textContent=inp.type==='password'?'👁️':'🙈';
}
function cekKekuatan(val){
    let s=0;
    if(val.length>=6)s++;if(val.length>=10)s++;
    if(/[A-Z]/.test(val))s++;if(/[0-9]/.test(val))s++;if(/[^A-Za-z0-9]/.test(val))s++;
    const fill=document.getElementById('strength-fill');
    const label=document.getElementById('strength-label');
    fill.style.width=(s/5*100)+'%';
    const lv=[{max:1,bg:'#ef4444',txt:'Sangat lemah'},{max:2,bg:'#f97316',txt:'Lemah'},
              {max:3,bg:'#f59e0b',txt:'Sedang'},{max:4,bg:'#22c55e',txt:'Kuat'},{max:5,bg:'#16a34a',txt:'Sangat kuat 💪'}];
    const l=lv.find(x=>s<=x.max)||lv[4];
    fill.style.background=l.bg;label.textContent=l.txt;label.style.color=l.bg;
}
function cekUlang(val){
    const baru=document.getElementById('pass_baru').value;
    const label=document.getElementById('ulang-label');
    if(!val){label.textContent='';return;}
    if(val===baru){label.textContent='✅ Password cocok';label.style.color='#16a34a';}
    else{label.textContent='❌ Tidak cocok';label.style.color='#dc2626';}
}
</script>
</body>
</html>