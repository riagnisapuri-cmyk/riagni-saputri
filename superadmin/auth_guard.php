<?php
// superadmin/auth_guard.php
// Include file ini di setiap halaman superadmin

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load CSRF helper — wajib ada sebelum halaman render
require_once __DIR__ . '/csrf_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION['role'] !== 'superadmin') {
    header("Location: ../index.php");
    exit;
}