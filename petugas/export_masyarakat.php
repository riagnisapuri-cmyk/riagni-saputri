<?php
require_once 'auth_guard.php';
include '../config/koneksi.php';

$cari   = trim($_GET['cari'] ?? '');
$status = $_GET['status'] ?? '';

$params = []; $types = ''; $where_parts = [];
if ($cari !== '') {
    $like = "%$cari%";
    $where_parts[] = "(nama LIKE ? OR nik LIKE ?)";
    $params[] = $like; $params[] = $like; $types .= 'ss';
}
if ($status !== '') {
    $where_parts[] = "status=?";
    $params[] = $status; $types .= 's';
}
$where = $where_parts ? 'WHERE '.implode(' AND ',$where_parts) : '';

if ($params) {
    $st = mysqli_prepare($conn, "SELECT nik,nama,alamat,no_hp,status FROM masyarakat $where ORDER BY nama");
    mysqli_stmt_bind_param($st, $types, ...$params);
    mysqli_stmt_execute($st);
    $data = mysqli_stmt_get_result($st);
} else {
    $data = mysqli_query($conn, "SELECT nik,nama,alamat,no_hp,status FROM masyarakat ORDER BY nama");
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="data_masyarakat_'.date('Ymd').'.csv"');

$out = fopen('php://output', 'w');
// BOM untuk Excel agar karakter Indonesia terbaca
fputs($out, "\xEF\xBB\xBF");

fputcsv($out, ['NIK','Nama','Alamat','No HP','Status'], ';');
while ($d = mysqli_fetch_assoc($data)) {
    fputcsv($out, [$d['nik'],$d['nama'],$d['alamat'],$d['no_hp'],$d['status']], ';');
}
fclose($out);
exit;