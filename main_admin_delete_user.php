<?php
session_start();

$host = 'localhost';
$dbname = 'db_community_appraisals';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ERROR: Could not connect. " . htmlspecialchars($e->getMessage()));
}

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_usuario'] !== 'admin') {
    header('Location: login_index.html');
    exit;
}

if (isset($_GET['id'])) {
    $id_usuario = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id_usuario === false) {
        die("ERROR: Invalid user ID.");
    }

    $query = "DELETE FROM usuarios WHERE id_usuario = ?";
    $stmt = $pdo->prepare($query);
    if ($stmt->execute([$id_usuario])) {
        header('Location: main_admin_createuser.php');
        exit;
    } else {
        die("ERROR: Could not execute the delete query.");
    }
} else {
    die("ERROR: User ID not set.");
}
?>
