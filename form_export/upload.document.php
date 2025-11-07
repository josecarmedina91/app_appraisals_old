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
    echo "Error: Inspection ID not provided.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uploading Documentation</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                window.location.href = 'pdf_generator.php?id=<?php echo $inspectionId; ?>';
            }, 2000);
        });
    </script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-6 rounded-lg shadow-lg text-center">
        <h1 class="text-2xl font-semibold text-gray-800">Uploading Documentation</h1>
        <p class="text-gray-600 mt-4">Please wait while the documentation is being uploaded to the server.</p>
        <div class="flex justify-center mt-6">
            <div class="loader border-t-4 border-blue-500 rounded-full w-16 h-16 animate-spin"></div>
        </div>
    </div>
</body>
</html>
