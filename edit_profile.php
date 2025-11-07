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

$allowed_fields = ['nombre_completo', 'correo_electronico', 'compañía', 'teléfono'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $field = $_POST['field'];
    $value = $_POST['value'];

    if (in_array($field, $allowed_fields)) {
        $query = "UPDATE usuarios SET $field = :value WHERE id_usuario = :usuario_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':value', $value);
        $stmt->bindParam(':usuario_id', $usuario_id);

        if (!$stmt->execute()) {
            echo "Error updating";
        }
    } else {
        echo "Field not allowed.";
    }
    exit;
}

$query = "SELECT nombre_completo, correo_electronico, compañía, teléfono FROM usuarios WHERE id_usuario = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$usuario_id]);

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $nombre_usuario = htmlspecialchars($row['nombre_completo']);
    $correo_usuario = htmlspecialchars($row['correo_electronico']);
    $compania_usuario = htmlspecialchars($row['compañía']);
    $telefono_usuario = htmlspecialchars($row['teléfono']);
} else {
    die('User not found.');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
        function updateField(field, value) {
            if (!validateField(field, value)) {
                alert('Please enter a valid value for ' + field);
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    if (xhr.responseText) {
                        alert(xhr.responseText);
                    }
                }
            };
            xhr.send(`field=${field}&value=${encodeURIComponent(value)}`);
        }

        function validateField(field, value) {
            switch (field) {
                case 'nombre_completo':
                    return value.trim() !== '';
                case 'correo_electronico':
                    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                case 'compañía':
                    return value.trim() !== '';
                case 'teléfono':
                    return /^[0-9]{10,15}$/.test(value);
                default:
                    return false;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('input').forEach(input => {
                input.addEventListener('input', function() {
                    updateField(this.id, this.value);
                });
            });
        });
    </script>
</head>

<body class="bg-gray-100 flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md min-h-screen">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center">
                <a href="#" onclick="GoBack()" class="inline-block p-2 bg-blue-500 text-white rounded-full hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <h1 class="text-xl font-bold text-gray-700 ml-4">Edit Profile</h1>
            </div>
        </div>
        <form>
            <div class="mb-4">
                <label for="nombre_completo" class="block text-base font-medium text-gray-700">Full Name <span class="text-red-500"></span></label>
                <input type="text" id="nombre_completo" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500" value="<?php echo $nombre_usuario; ?>">
            </div>
            <div class="mb-4">
                <label for="correo_electronico" class="block text-base font-medium text-gray-700">Email <span class="text-red-500"></span></label>
                <input type="email" id="correo_electronico" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500" value="<?php echo $correo_usuario; ?>">
            </div>
            <div class="mb-4">
                <label for="compañía" class="block text-base font-medium text-gray-700">Company <span class="text-red-500"></span></label>
                <input type="text" id="compañía" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500" value="<?php echo $compania_usuario; ?>">
            </div>
            <div class="mb-4">
                <label for="teléfono" class="block text-base font-medium text-gray-700">Phone Number <span class="text-red-500"></span></label>
                <input type="text" id="teléfono" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500" value="<?php echo $telefono_usuario; ?>">
            </div>
            <div class="mb-6">
                <label for="country" class="block text-base font-medium text-gray-700">Country <span class="text-red-500"></span></label>
                <select id="country" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500 bg-gray-50" disabled>
                    <option value="canada">Canada</option>
                </select>
            </div>
        </form>
    </div>
    <script>
        function GoBack() {
            const usuarioId = '<?php echo $usuario_id; ?>';
            window.location.href = `menu.php?id=${usuarioId}`;
        }
    </script>
</body>

</html>