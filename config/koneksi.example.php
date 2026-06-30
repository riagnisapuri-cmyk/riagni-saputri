<?php
$host = "localhost";
$user = "username_database";
$pass = "password_database";
$db   = "nama_database";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
