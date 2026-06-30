<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';
$uid  = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT foto, nama FROM users WHERE id='$uid'"));
$foto_src = !empty($user['foto']) ? "../uploads/profil/".htmlspecialchars($user['foto']) : null;
$initial  = strtoupper(substr($user['nama']??'U',0,1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>SIBANSOS — FAQ</title>
<link rel="stylesheet" href="ms_style.css">
<style>
.topbar { display:flex; align-items:center; justify-content:space-between; padding:20px 28px; background:#fff; border-bottom:1px solid #e2e8f0; margin-bottom:24px; }
.topbar h1 { font-size:22px; font-weight:700; color:#0f172a; margin:0 0 4px; }
.topbar p  { font-size:13px; color:#64748b; margin:0; }
.topbar-right { display:flex; align-items:center; gap:16px; }
.top-icon { width:38px; height:38px; border-radius:50%; background:#f1f5f9; display:flex; align-items:center; justify-content:center; font-size:18px; border:1px solid #e2e8f0; }
.profile-mini { display:flex; align-items:center; gap:10px; }
.profile-mini img, .profile-mini .avatar-inline { width:38px; height:38px; border-radius:50%; object-fit:cover; border:2px solid #e2e8f0; }
.avatar-inline { background:linear-gradient(135deg,#17375e,#2e5c92); display:flex; align-items:center; justify-content:center; font-size:16px; font-weight:700; color:#fff; }
.profile-mini .name { font-size:13px; font-weight:600; color:#0f172a; }
.profile-mini .role { font-size:11px; color:#94a3b8; }

/* FAQ Accordion */
.faq-item {
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:12px;
    margin-bottom:10px;
    overflow:hidden;
    transition:.2s;
}
.faq-item.open { border-color:#17375e; box-shadow:0 2px 12px rgba(23,55,94,.08); }
.faq-q {
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:16px 20px;
    cursor:pointer;
    font-size:14px;
    font-weight:600;
    color:#0f172a;
    gap:12px;
    user-select:none;
}
.faq-q:hover { background:#f8fafc; }
.faq-q .faq-icon { font-size:20px; flex-shrink:0; }
.faq-q .faq-chevron {
    font-size:12px;
    color:#94a3b8;
    transition:transform .3s;
    flex-shrink:0;
}
.faq-item.open .faq-chevron { transform:rotate(180deg); }
.faq-a {
    display:none;
    padding:0 20px 18px 52px;
    font-size:13px;
    color:#475569;
    line-height:1.7;
    border-top:1px solid #f1f5f9;
}
.faq-item.open .faq-a { display:block; }

.faq-category {
    font-size:11px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.08em;
    color:#94a3b8;
    margin:24px 0 10px;
}
.faq-category:first-child { margin-top:0; }

.faq-search {
    width:100%;
    padding:12px 16px;
    border:1px solid #e2e8f0;
    border-radius:10px;
    font-size:14px;
    margin-bottom:20px;
    outline:none;
    box-sizing:border-box;
}
.faq-search:focus { border-color:#17375e; box-shadow:0 0 0 3px rgba(23,55,94,.1); }
</style>
</head>
<body>
<?php include '_sidebar.php'; ?>
<div class="main">

  <div class="topbar">
    <div>
      <h1>❓ FAQ</h1>
      <p>Pertanyaan yang sering ditanyakan seputar SIBANSOS</p>
    </div>
    <div class="topbar-right">
      <div class="top-icon">🔔</div>
      <div class="profile-mini">
        <?php if($foto_src): ?><img src="<?= $foto_src ?>" alt="Foto">
        <?php else: ?><div class="avatar-inline"><?= $initial ?></div><?php endif; ?>
        <div>
          <div class="name"><?= htmlspecialchars($user['nama']) ?></div>
          <div class="role">Masyarakat</div>
        </div>
      </div>
    </div>
  </div>

  <div style="padding:0 28px 28px">
    <div class="card">

      <input class="faq-search" type="text" id="faqSearch" placeholder="🔍  Cari pertanyaan...">

      <div id="faqList">

        <div class="faq-category">📋 Pengajuan Bantuan</div>

        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaq(this)">
            <span class="faq-icon">📝</span>
            <span style="flex:1">Bagaimana cara mengajukan bantuan sosial?</span>
            <span class="faq-chevron">▼</span>
          </div>
          <div class="faq-a">
            Klik menu <b>Ajukan Bantuan</b> di sidebar, lalu pilih program bantuan yang sesuai dengan kondisi kamu. Isi semua data yang diminta dan klik tombol <b>Kirim Pengajuan</b>. Pastikan NIK sudah diisi di profil sebelum mengajukan.
          </div>
        </div>

        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaq(this)">
            <span class="faq-icon">📄</span>
            <span style="flex:1">Dokumen apa saja yang diperlukan untuk pengajuan?</span>
            <span class="faq-chevron">▼</span>
          </div>
          <div class="faq-a">
            Dokumen yang biasanya diperlukan antara lain: KTP/NIK, Kartu Keluarga (KK), dan foto rumah tinggal. Dokumen tambahan bisa berbeda-beda tergantung jenis program bantuan yang dipilih.
          </div>
        </div>

        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaq(this)">
            <span class="faq-icon">🔢</span>
            <span style="flex:1">Apakah bisa mengajukan lebih dari satu program bantuan?</span>
            <span class="faq-chevron">▼</span>
          </div>
          <div class="faq-a">
            Ya, kamu bisa mengajukan lebih dari satu program bantuan selama kamu memenuhi syarat masing-masing program. Setiap pengajuan akan diverifikasi secara terpisah oleh petugas.
          </div>
        </div>

        <div class="faq-category">🔍 Status & Proses</div>

        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaq(this)">
            <span class="faq-icon">⏳</span>
            <span style="flex:1">Berapa lama proses verifikasi pengajuan?</span>
            <span class="faq-chevron">▼</span>
          </div>
          <div class="faq-a">
            Proses verifikasi biasanya memakan waktu <b>7–14 hari kerja</b> sejak tanggal pengajuan. Kamu bisa memantau status pengajuan secara real-time melalui menu <b>Status Pengajuan</b>.
          </div>
        </div>

        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaq(this)">
            <span class="faq-icon">❌</span>
            <span style="flex:1">Kenapa pengajuan saya ditolak?</span>
            <span class="faq-chevron">▼</span>
          </div>
          <div class="faq-a">
            Pengajuan bisa ditolak karena beberapa alasan, seperti: data tidak lengkap, NIK tidak sesuai, tidak memenuhi kriteria program, atau sudah pernah menerima bantuan dari program yang sama. Lihat alasan penolakan di detail pengajuan.
          </div>
        </div>

        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaq(this)">
            <span class="faq-icon">🔄</span>
            <span style="flex:1">Apakah bisa mengajukan ulang setelah ditolak?</span>
            <span class="faq-chevron">▼</span>
          </div>
          <div class="faq-a">
            Bisa, kamu dapat mengajukan ulang setelah memperbaiki data atau dokumen yang kurang. Pastikan membaca alasan penolakan terlebih dahulu agar pengajuan berikutnya bisa diterima.
          </div>
        </div>

        <div class="faq-category">👤 Akun & Profil</div>

        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaq(this)">
            <span class="faq-icon">🪪</span>
            <span style="flex:1">Mengapa NIK harus diisi di profil?</span>
            <span class="faq-chevron">▼</span>
          </div>
          <div class="faq-a">
            NIK (Nomor Induk Kependudukan) digunakan untuk mencocokkan data kamu dengan data kependudukan. Tanpa NIK, kamu tidak bisa mengajukan bantuan. Isi NIK di menu <b>Profil Saya</b>.
          </div>
        </div>

        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaq(this)">
            <span class="faq-icon">🔒</span>
            <span style="flex:1">Bagaimana jika lupa password?</span>
            <span class="faq-chevron">▼</span>
          </div>
          <div class="faq-a">
            Hubungi petugas kelurahan atau admin SIBANSOS untuk mereset password akunmu. Saat ini fitur reset password mandiri masih dalam pengembangan.
          </div>
        </div>

        <div class="faq-category">💰 Penyaluran Bantuan</div>

        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaq(this)">
            <span class="faq-icon">💳</span>
            <span style="flex:1">Bagaimana cara penyaluran bantuan dilakukan?</span>
            <span class="faq-chevron">▼</span>
          </div>
          <div class="faq-a">
            Penyaluran bantuan dilakukan sesuai dengan jenis program: bisa berupa uang tunai, transfer ke rekening, atau barang langsung. Detail mekanisme penyaluran akan diinformasikan melalui menu Pengumuman.
          </div>
        </div>

        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaq(this)">
            <span class="faq-icon">📅</span>
            <span style="flex:1">Kapan bantuan akan diterima setelah pengajuan diterima?</span>
            <span class="faq-chevron">▼</span>
          </div>
          <div class="faq-a">
            Setelah status pengajuan berubah menjadi <b>Diterima</b>, penyaluran bantuan akan dijadwalkan sesuai periode program yang berjalan. Pantau terus menu Pengumuman untuk informasi jadwal penyaluran.
          </div>
        </div>

      </div><!-- end faqList -->

      <div id="faqEmpty" style="display:none;text-align:center;padding:32px;color:#94a3b8">
        <div style="font-size:36px;margin-bottom:8px">🔍</div>
        <p style="margin:0;font-size:14px">Tidak ada pertanyaan yang cocok.</p>
      </div>

    </div>
  </div>

</div>

<script>
function toggleFaq(el) {
  const item = el.closest('.faq-item');
  const isOpen = item.classList.contains('open');
  // Tutup semua
  document.querySelectorAll('.faq-item.open').forEach(i => i.classList.remove('open'));
  if (!isOpen) item.classList.add('open');
}

// Search FAQ
document.getElementById('faqSearch').addEventListener('input', function() {
  const q = this.value.toLowerCase().trim();
  const items = document.querySelectorAll('.faq-item');
  const cats  = document.querySelectorAll('.faq-category');
  let found = 0;

  items.forEach(item => {
    const text = item.textContent.toLowerCase();
    const show = !q || text.includes(q);
    item.style.display = show ? '' : 'none';
    if (show) found++;
  });

  // Sembunyikan kategori kalau semua item di bawahnya tersembunyi
  cats.forEach(cat => {
    let next = cat.nextElementSibling;
    let hasVisible = false;
    while (next && !next.classList.contains('faq-category')) {
      if (next.classList.contains('faq-item') && next.style.display !== 'none') hasVisible = true;
      next = next.nextElementSibling;
    }
    cat.style.display = hasVisible ? '' : 'none';
  });

  document.getElementById('faqEmpty').style.display = found === 0 ? 'block' : 'none';
});
</script>
</body>
</html>