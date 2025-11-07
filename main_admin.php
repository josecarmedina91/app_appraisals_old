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
    die("ERROR: No se pudo conectar. " . $e->getMessage());
}

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_usuario'] !== 'admin') {
    header('Location: login_index.html');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

$query = "SELECT nombre_completo, correo_electronico FROM usuarios WHERE id_usuario = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$usuario_id]);

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $nombre_usuario = htmlspecialchars($row['nombre_completo']);
    $correo_usuario = htmlspecialchars($row['correo_electronico']);
} else {
    die('Usuario no encontrado.');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
        function redirectToReportDownload() {
            window.location.href = 'main_admin_reportdown.php';
        }

        function redirectToCreateUser() {
            window.location.href = 'main_admin_createuser.php';
        }

        function redirectToInspectionAdmin() {
            window.location.href = 'main_admin_inspectionadmin.php';
        }

        function logout() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    window.location.href = 'login_index.html';
                } else {
                    alert('Error al cerrar sesión. Inténtalo de nuevo.');
                }
            };
            xhr.send('action=logout');
        }
    </script>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-gray-200 text-gray-700 p-4 shadow">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold mx-auto">Management Console</h1>
                <button onclick="logout()" class="flex items-center p-2 bg-black text-white rounded-md shadow-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    Log Out
                </button>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-grow p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div onclick="redirectToCreateUser()" class="cursor-pointer flex flex-col items-center p-6 bg-blue-500 text-white rounded-lg shadow-lg hover:bg-blue-600 transition duration-300">
                    <i class="fas fa-user-plus text-4xl mb-4"></i>
                    <h2 class="text-xl font-semibold">User and Password Management</h2>
                </div>
                <div onclick="redirectToInspectionAdmin()" class="cursor-pointer flex flex-col items-center p-6 bg-red-500 text-white rounded-lg shadow-lg hover:bg-red-600 transition duration-300">
                    <i class="fas fa-clipboard-list text-4xl mb-4"></i>
                    <h2 class="text-xl font-semibold">Administration of Inspections</h2>
                </div>
                <div onclick="redirectToReportDownload()" class="cursor-pointer flex flex-col items-center p-6 bg-green-500 text-white rounded-lg shadow-lg hover:bg-green-600 transition duration-300">
                    <i class="fas fa-download text-4xl mb-4"></i>
                    <h2 class="text-xl font-semibold">Download Inspections</h2>
                </div>
            </div>
        </main>
    </div>
</body>

</html>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    session_unset();
    session_destroy();
    exit;
}
?>
