<?php
session_start();

class Database {
    private $host = 'localhost';
    private $dbname = 'db_community_appraisals';
    private $username = 'root';
    private $password = '';
    public $pdo;

    public function __construct() {
        try {
            $this->pdo = new PDO("mysql:host=$this->host;dbname=$this->dbname", $this->username, $this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("ERROR: Could not connect. " . htmlspecialchars($e->getMessage()));
        }
    }
}

class User {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function createUser($data) {
        $query = "INSERT INTO usuarios (nombre_completo, compañía, rol_usuario, correo_electronico, teléfono, país, contraseña) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->pdo->prepare($query);
        
        try {
            $stmt->execute([
                $data['nombre_completo'],
                $data['compañía'],
                $data['rol_usuario'],
                $data['correo_electronico'],
                $data['teléfono'],
                $data['país'],
                $data['contraseña_hashed']
            ]);
            header('Location: main_admin_createuser.php');
            exit;
        } catch (PDOException $e) {
            die("ERROR: Could not execute query. " . htmlspecialchars($e->getMessage()));
        }
    }

    public function emailExists($email) {
        $query = "SELECT COUNT(*) FROM usuarios WHERE correo_electronico = ?";
        $stmt = $this->db->pdo->prepare($query);
        $stmt->execute([$email]);
        return $stmt->fetchColumn() > 0;
    }
}

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_usuario'] !== 'admin') {
    header('Location: login_index.html');
    exit;
}

function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        die("ERROR: Invalid CSRF token.");
    }

    $nombre_completo = sanitizeInput(filter_input(INPUT_POST, 'nombre_completo', FILTER_SANITIZE_STRING));
    $compañía = sanitizeInput(filter_input(INPUT_POST, 'compañía', FILTER_SANITIZE_STRING));
    $rol_usuario = sanitizeInput(filter_input(INPUT_POST, 'rol_usuario', FILTER_SANITIZE_STRING));
    $correo_electronico = filter_input(INPUT_POST, 'correo_electronico', FILTER_VALIDATE_EMAIL);
    $teléfono = sanitizeInput(filter_input(INPUT_POST, 'teléfono', FILTER_SANITIZE_STRING));
    $país = sanitizeInput(filter_input(INPUT_POST, 'país', FILTER_SANITIZE_STRING));
    $contraseña = $_POST['contraseña'];

    if (!$correo_electronico) {
        die("ERROR: Invalid email address.");
    }

    if (strlen($contraseña) < 8) {
        die("ERROR: Password must be at least 8 characters long.");
    }
    if (!preg_match('/[A-Z]/', $contraseña)) {
        die("ERROR: Password must include at least one uppercase letter.");
    }
    if (!preg_match('/[a-z]/', $contraseña)) {
        die("ERROR: Password must include at least one lowercase letter.");
    }
    if (!preg_match('/[0-9]/', $contraseña)) {
        die("ERROR: Password must include at least one number.");
    }
    if (!preg_match('/[\W_]/', $contraseña)) {
        die("ERROR: Password must include at least one special character.");
    }    

    $contraseña_hashed = password_hash($contraseña, PASSWORD_DEFAULT);

    $db = new Database();
    $user = new User($db);
    $user->createUser([
        'nombre_completo' => $nombre_completo,
        'compañía' => $compañía,
        'rol_usuario' => $rol_usuario,
        'correo_electronico' => $correo_electronico,
        'teléfono' => $teléfono,
        'país' => $país,
        'contraseña_hashed' => $contraseña_hashed
    ]);
}

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['correo_electronico'])) {
    $db = new Database();
    $user = new User($db);
    $emailExists = $user->emailExists($_GET['correo_electronico']);
    echo json_encode(['exists' => $emailExists]);
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" rel="stylesheet"></link>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('form');
            const emailInput = document.getElementById('correo_electronico');
            const emailError = document.getElementById('emailError');
            const phoneInput = document.getElementById('teléfono');
            const phoneError = document.getElementById('phoneError');
            const submitButton = form.querySelector('button[type="submit"]');

            const validatePhone = (phone) => {
                const phonePattern = /^[2-9]{1}[0-9]{9}$/;
                const allSameDigits = /^(.)\1{9}$/;
                return phonePattern.test(phone) && !allSameDigits.test(phone);
            };

            emailInput.addEventListener('input', () => {
                const email = emailInput.value;
                if (email) {
                    fetch(`main_admin_create_user.php?correo_electronico=${encodeURIComponent(email)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.exists) {
                                emailError.textContent = 'This email is already in use.';
                                submitButton.disabled = true;
                            } else {
                                emailError.textContent = '';
                                submitButton.disabled = false;
                            }
                        })
                        .catch(error => {
                            console.error('Error checking email:', error);
                            emailError.textContent = 'Could not check email. Please try again later.';
                            submitButton.disabled = true;
                        });
                } else {
                    emailError.textContent = '';
                    submitButton.disabled = false;
                }
            });

            phoneInput.addEventListener('input', () => {
                const phone = phoneInput.value;
                if (phone) {
                    if (!validatePhone(phone)) {
                        phoneError.textContent = 'The number must contain at least 10 digits.';
                        submitButton.disabled = true;
                    } else {
                        phoneError.textContent = '';
                        submitButton.disabled = false;
                    }
                } else {
                    phoneError.textContent = '';
                    submitButton.disabled = false;
                }
            });

            form.addEventListener('submit', (event) => {
                const password = document.getElementById('contraseña').value;
                const passwordError = document.getElementById('passwordError');
                const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;

                if (!passwordPattern.test(password)) {
                    alert('ERROR: Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.');
                    event.preventDefault();
                }
            });
        });
    </script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col items-center justify-center">
        <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
            <h1 class="text-3xl font-bold mb-6 text-center">Create User</h1>
            <form action="main_admin_create_user.php" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="flex items-center">
                    <i class="fas fa-user mr-2 text-gray-400"></i>
                    <input type="text" id="nombre_completo" name="nombre_completo" class="w-full p-2 border rounded" placeholder="Full Name" required>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-building mr-2 text-gray-400"></i>
                    <input type="text" id="compañía" name="compañía" class="w-full p-2 border rounded" placeholder="Company" required>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-user-tag mr-2 text-gray-400"></i>
                    <select id="rol_usuario" name="rol_usuario" class="w-full p-2 border rounded" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-envelope mr-2 text-gray-400"></i>
                    <input type="email" id="correo_electronico" name="correo_electronico" class="w-full p-2 border rounded" placeholder="Email" required>
                </div>
                <div id="emailError" class="text-red-500 text-sm"></div>
                <div class="flex items-center">
                    <i class="fas fa-phone mr-2 text-gray-400"></i>
                    <input type="text" id="teléfono" name="teléfono" class="w-full p-2 border rounded" placeholder="Phone" required>
                </div>
                <div id="phoneError" class="text-red-500 text-sm"></div>
                <div class="flex items-center">
                    <i class="fas fa-globe mr-2 text-gray-400"></i>
                    <input type="text" id="país" name="país" class="w-full p-2 border rounded" placeholder="Country" required>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-lock mr-2 text-gray-400"></i>
                    <input type="password" id="contraseña" name="contraseña" class="w-full p-2 border rounded" placeholder="Password" required>
                </div>
                <div id="passwordError" class="text-red-500 text-sm"></div>
                <div class="flex justify-between">
                    <button type="submit" class="w-1/2 p-2 bg-blue-500 text-white rounded hover:bg-blue-600 flex items-center justify-center">
                        <i class="fas fa-user-plus mr-2"></i>Create
                    </button>
                    <button type="button" onclick="history.back()" class="w-1/2 p-2 bg-red-500 text-white rounded hover:bg-red-600 ml-4 flex items-center justify-center">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
