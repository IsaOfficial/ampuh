<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "sistem_laporan_harian_pegawai";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h3 style='color:green'>✔ Koneksi database berhasil!</h3>";

    $query = $pdo->query("SELECT COUNT(*) AS total FROM pegawai");
    $result = $query->fetch();

    echo "<p>Total data pegawai: <b>{$result['total']}</b></p>";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>✘ Error:</h3>";
    echo $e->getMessage();
    exit;
}
