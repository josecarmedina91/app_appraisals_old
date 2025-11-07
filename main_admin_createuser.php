<?php
session_start();

$host = 'localhost';
$dbname = 'db_community_appraisals';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
    ]);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("ERROR: Could not connect. " . htmlspecialchars($e->getMessage()));
}

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_usuario'] !== 'admin') {
    header('Location: login_index.html');
    exit;
}

session_regenerate_id(true);

$query = "SELECT id_usuario, nombre_completo, compañía, rol_usuario, correo_electronico, teléfono, país, fecha_ultima_conexion FROM usuarios";
$stmt = $pdo->prepare($query);
$stmt->execute();
$usuarios = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body class="bg-gray-100 text-sm">
    <div class="min-h-screen flex flex-col">
        <header class="bg-gray-200 text-gray-700 p-4 shadow">
            <div class="flex items-center justify-between">
                <button type="button" onclick="window.location.href='main_admin.php'" class="inline-block mb-4 p-2 bg-blue-500 text-white rounded-full hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-lg">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <h1 class="text-lg font-semibold">User Account Control</h1>
                <button type="button" onclick="window.location.href='main_admin_create_user.php'" class="inline-block mb-4 p-2 bg-green-500 text-white rounded-full hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 shadow-lg">
                    <i class="fas fa-user-plus"></i> Add User
                </button>
            </div>
        </header>
        <main class="flex-grow p-4">
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-2 px-4 border-b text-left">ID</th>
                            <th class="py-2 px-4 border-b text-left">Full Name</th>
                            <th class="py-2 px-4 border-b text-left">Company</th>
                            <th class="py-2 px-4 border-b text-left">Role</th>
                            <th class="py-2 px-4 border-b text-left">Email</th>
                            <th class="py-2 px-4 border-b text-left">Phone</th>
                            <th class="py-2 px-4 border-b text-left">Country</th>
                            <th class="py-2 px-4 border-b text-left">Last Connection</th>
                            <th class="py-2 px-4 border-b text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario) : ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($usuario['id_usuario']); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($usuario['nombre_completo']); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($usuario['compañía']); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($usuario['rol_usuario']); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($usuario['correo_electronico']); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($usuario['teléfono']); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($usuario['país']); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($usuario['fecha_ultima_conexion']); ?></td>
                                <td class="py-2 px-4 border-b flex space-x-2">
                                    <button onclick="window.location.href='main_admin_edit_user.php?id=<?php echo $usuario['id_usuario']; ?>'" class="bg-yellow-500 text-white px-2 py-1 rounded hover:bg-yellow-600 focus:outline-none">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button onclick="if(confirm('Are you sure you want to delete this user?')) window.location.href='main_admin_delete_user.php?id=<?php echo $usuario['id_usuario']; ?>'" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 focus:outline-none">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>

</html>
