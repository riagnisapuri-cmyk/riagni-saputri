<?php
// masyarakat/auth_guard.php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (!in_array($_SESSION['role'], ['user', 'masyarakat'])) {
    // Role lain redirect ke halaman masing-masing
    if ($_SESSION['role'] === 'superadmin') header("Location: ../superadmin/index.php");
    elseif ($_SESSION['role'] === 'admin')  header("Location: ../admin/index.php");
    elseif ($_SESSION['role'] === 'petugas') header("Location: ../petugas/index.php");
    else header("Location: ../login.php");
    exit;
}
?>
