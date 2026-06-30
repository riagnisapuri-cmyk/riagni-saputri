<?php
// =====================================
// PETUGAS AUTH GUARD
// File: petugas/auth_guard.php
// =====================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Belum login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Session rusak
if (!isset($_SESSION['role'])) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

// Hanya petugas yang boleh akses folder petugas
if ($_SESSION['role'] !== 'petugas') {
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: ../admin/index.php");
            exit;
        case 'superadmin':
            header("Location: ../superadmin/index.php");
            exit;
        case 'masyarakat':
            header("Location: ../masyarakat/index.php");
            exit;
        default:
            session_destroy();
            header("Location: ../login.php");
            exit;
    }
}

// Jika nama belum ada di session
if (!isset($_SESSION['nama'])) {
    $_SESSION['nama'] = 'Petugas';
}