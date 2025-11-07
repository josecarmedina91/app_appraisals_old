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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload Complete</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Add a new history state
            window.history.pushState(null, "", window.location.href);
            window.history.replaceState(null, "", window.location.href);
            
            window.addEventListener("popstate", function() {
                // Redirect the user to the home page
                window.location.href = "../main_user.php";
            });
        });
    </script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md text-center">
        <h1 class="text-3xl font-semibold mb-4 text-green-600">File Upload Complete</h1>
        <p class="mb-6 text-gray-700">The file upload process has been successfully completed. The created inspection process will now move to the review stage.</p>
        <a href="../main_user.php" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg shadow-md hover:bg-blue-700 transition duration-300">Return to Home</a>
    </div>
</body>
</html>