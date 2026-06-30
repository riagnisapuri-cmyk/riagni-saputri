<?php
session_start();
include 'config/koneksi.php';

// Catat log logout sebelum session dihapus
if (isset($_SESSION['user_id'])) {
    $uid   = $_SESSION['user_id'];
    $uname = mysqli_real_escape_string($conn, $_SESSION['username'] ?? '');
    $nama  = mysqli_real_escape_string($conn, $_SESSION['nama'] ?? '');
    $role  = mysqli_real_escape_string($conn, $_SESSION['role'] ?? '');
    $ip    = $_SERVER['REMOTE_ADDR'];

    mysqli_query($conn, "
        INSERT INTO log_aktivitas (user_id, username, nama, role, aksi, ip_address)
        VALUES ('$uid', '$uname', '$nama', '$role', 'logout', '$ip')
    ");
}

session_unset();
session_destroy();
header("Location: login.php");
exit;
?>