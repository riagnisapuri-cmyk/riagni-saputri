<?php
// superadmin/csrf_helper.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Ambil atau buat CSRF token untuk session ini.
 * Token TIDAK di-rotate agar halaman dengan banyak form (kelola_akun)
 * bisa submit berkali-kali tanpa token kedaluwarsa.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Kembalikan hidden input berisi CSRF token.
 * Pakai di setiap <form> POST:  <?= csrf_field() ?>
 */
function csrf_field(): string {
    $token = csrf_token();
    return '<input type="hidden" name="_csrf_token" value="'
         . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verifikasi CSRF token dari POST.
 * Jika tidak cocok, redirect kembali dan stop eksekusi.
 */
function csrf_verify(): void {
    $submitted = $_POST['_csrf_token'] ?? '';
    $expected  = $_SESSION['csrf_token'] ?? '';

    if (!$submitted || !$expected || !hash_equals($expected, $submitted)) {
        error_log('[CSRF] Token mismatch dari IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        http_response_code(403);
        $ref = $_SERVER['HTTP_REFERER'] ?? '../login.php';
        header('Location: ' . $ref);
        exit;
    }
    // Tidak di-rotate agar form ganda dalam 1 halaman tetap bisa submit
}