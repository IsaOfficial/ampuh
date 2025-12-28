<?php
// TEST KONEKSI DATABASE

$host = "localhost";
$user = "root";
$pass = "";
$db   = "sistem_laporan_harian_pegawai";   // sesuaikan nama database kamu

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h3 style='color:green'>✔ Koneksi database berhasil!</h3>";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>✘ Koneksi gagal:</h3>";
    echo $e->getMessage();
    exit;
}
