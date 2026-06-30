<?php
session_start();
include '../config/koneksi.php';

if(isset($_POST['tambah'])){

    $nama = mysqli_real_escape_string($conn,$_POST['nama_jenis']);

    mysqli_query($conn,"INSERT INTO jenis_bantuan(nama_jenis)
    VALUES('$nama')");

    header("Location: jenis_bantuan.php");
    exit;
}

if(isset($_GET['hapus'])){

    $id = $_GET['hapus'];

    mysqli_query($conn,"DELETE FROM jenis_bantuan WHERE id='$id'");

    header("Location: jenis_bantuan.php");
    exit;
}

$data = mysqli_query($conn,"SELECT * FROM jenis_bantuan ORDER BY id ASC");
$total = mysqli_num_rows($data);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Jenis Bantuan</title>
<link rel="stylesheet" href="admin_style.css">
</head>
<body>

<?php include '_sidebar.php'; ?>

<div class="main">

    <div class="page-header">
        <div>
            <h1>📂 Jenis Bantuan</h1>
            <p>Kelola kategori bantuan sosial</p>
        </div>
    </div>

    <div class="card">

        <div class="card-title">
         ➕ Tambah Jenis Bantuan
        </div>
    <div class="alert alert-info">
    📂 Total Jenis Bantuan :
    <b><?= $total ?></b>
</div>
        <form method="POST" style="display:flex;gap:12px;margin-bottom:25px;">
            <input
                type="text"
                name="nama_jenis"
                placeholder="Masukkan jenis bantuan..."
                required
                style="flex:1;padding:12px;border:1px solid #ddd;border-radius:10px;">

            <button type="submit" name="tambah" class="btn btn-green">
                + Tambah
            </button>
        </form>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th width="70">No</th>
                        <th>Jenis Bantuan</th>
                       <th width="220">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php
                $no = 1;
                while($d = mysqli_fetch_assoc($data)){
                ?>

                    <tr>

                        <td><?= $no++ ?></td>

                        <td>
                            <span class="badge badge-navy">
                                <?= htmlspecialchars($d['nama_jenis']) ?>
                            </span>
                        </td>

                        <td>
                            <div class="action-btns">
                                <a href="edit_jenis_bantuan.php?id=<?= $d['id'] ?>"
                                class="btn btn-yellow btn-sm">
                                    ✏ Edit
                                </a>

                                <a href="?hapus=<?= $d['id'] ?>"
                                class="btn btn-red btn-sm"
                                onclick="return confirm('Yakin ingin menghapus?')">
                                    🗑 Hapus
                                </a>
                            </div>
                        </td>
                                        </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>
</html>