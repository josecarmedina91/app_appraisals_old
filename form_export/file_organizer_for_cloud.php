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
    die("ERROR: Could not connect. " . $e->getMessage());
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login_index.html');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

$query = "SELECT nombre_completo, correo_electronico FROM usuarios WHERE id_usuario = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$usuario_id]);

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $nombre_usuario = $row['nombre_completo'];
    $correo_usuario = $row['correo_electronico'];
} else {
    die('User not found.');
}

if (isset($_GET['id'])) {
    $inspectionId = $_GET['id'];
} else {
    die("Error: Inspection ID not provided.");
}

$exportDir = "../export_cloud/$inspectionId";
$sourceDir = "../img/photo_gallery/$inspectionId";

if (!file_exists($exportDir)) {
    mkdir($exportDir, 0777, true);
}

if (file_exists($sourceDir)) {
    $destinationDir = "$exportDir/Photo Gallery";
    if (!file_exists($destinationDir)) {
        mkdir($destinationDir, 0777, true);
    }
    copyDirectory($sourceDir, $destinationDir);
} else {
    die("Error: Source directory not found.");
}

$query = "SELECT direccion_propiedad FROM tb_inspecciones WHERE id_inspeccion = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$inspectionId]);

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $direccionPropiedad = $row['direccion_propiedad'];
} else {
    die('Inspection not found.');
}

$pdfDir = "../export_pdf";
$pdfPattern = "Inspection Report - $direccionPropiedad.pdf";

$found = false;
foreach (glob("$pdfDir/*") as $file) {
    if (strpos($file, $pdfPattern) !== false) {
        $destinationPath = "$exportDir/" . basename($file);
        if (!rename($file, $destinationPath)) {
            die("Error moving PDF.");
        }
        $found = true;
        break;
    }
}

if (!$found) {
    die("PDF not found.");
}

$newExportDir = "../export_cloud/$direccionPropiedad";
if (!rename($exportDir, $newExportDir)) {
    die("Error renaming directory.");
}

// Eliminar la carpeta de origen solo si todo el proceso ha sido exitoso
deleteDirectory($sourceDir);

try {
    $updateQuery = "UPDATE tb_inspecciones SET status_inspeccion = 'Completed' WHERE id_inspeccion = ?";
    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->execute([$inspectionId]);
} catch (PDOException $e) {
    die("ERROR: Could not update status. " . $e->getMessage());
}


header("Location: finish.php?id=$inspectionId");
exit;

function copyDirectory($src, $dst)
{
    $dir = opendir($src);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                mkdir($dst . '/' . $file);
                copyDirectory($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

function deleteDirectory($dir)
{
    if (!is_dir($dir)) {
        return false;
    }
    $items = array_diff(scandir($dir), ['.', '..']);
    foreach ($items as $item) {
        $path = "$dir/$item";
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }
    return rmdir($dir);
}
