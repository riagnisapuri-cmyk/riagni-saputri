<?php

include 'config/koneksi.php';

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