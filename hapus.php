<?php

include 'config/koneksi.php';

// menangkap data yang dikirim dari url
$nik = $_GET['nik'];
$query = mysqli_query($conn,"
    DELETE FROM masyarakat
    WHERE nik='$nik'
");

if($query){
    header("Location: masyarakat.php");
}else{
    echo mysqli_error($conn);
}

?>