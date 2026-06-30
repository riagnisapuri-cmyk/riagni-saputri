<?php
session_start();
include 'config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Redirect sesuai role
$role = $_SESSION['role'] ?? 'user';

if ($role === 'superadmin') {
    header("Location: superadmin/index.php");
} elseif ($role === 'admin') {
    header("Location: admin/index.php");
} elseif ($role === 'petugas') {
    header("Location: petugas/index.php");
} else {
    header("Location: masyarakat/index.php");
}
exit;
?>