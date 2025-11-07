<?php
// db_connect.sample.php
// Copia este archivo a db_connect.php y reemplaza con tus credenciales reales.

$host = 'localhost';
$dbname = 'TU_BASE_DE_DATOS';
$username = 'TU_USUARIO';
$password = 'TU_CONTRASEÑA';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ERROR: No se pudo conectar. " . $e->getMessage());
}
?>