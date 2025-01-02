<?php
$host = 'localhost'; 
$dbname = 'YourDBName'; 
$user = 'YourDBUser'; 
$pass = 'YourDBPass'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}
?>
