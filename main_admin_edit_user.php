<?php
session_start();

class UserHandler {
    private $pdo;
    private $cache;

    public function __construct($host, $dbname, $username, $password) {
        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->cache = [];
        } catch (PDOException $e) {
            die("ERROR: Could not connect. " . htmlspecialchars($e->getMessage()));
        }
    }

    public function getUserById($id) {
        if (isset($this->cache[$id])) {
            return $this->cache[$id];
        }
        $query = "SELECT * FROM usuarios WHERE id_usuario = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->cache[$id] = $user;
        return $user;
    }

    public function updateUser($data, $id) {
        $query = "UPDATE usuarios SET nombre_completo = ?, compañía = ?, rol_usuario = ?, correo_electronico = ?, teléfono = ?, país = ? WHERE id_usuario = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$data['nombre_completo'], $data['compañía'], $data['rol_usuario'], $data['correo_electronico'], $data['teléfono'], $data['país'], $id]);

        if (!empty($data['contraseña'])) {
            if (!$this->isPasswordComplex($data['contraseña'])) {
                echo "<script>alert('The password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.'); window.history.back();</script>";
                exit;
            }
            $contraseña = password_hash($data['contraseña'], PASSWORD_DEFAULT);
            $query = "UPDATE usuarios SET contraseña = ? WHERE id_usuario = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$contraseña, $id]);
        }
    }

    public function emailExists($email, $userId = null) {
        $query = "SELECT COUNT(*) FROM usuarios WHERE correo_electronico = ?";
        $params = [$email];
        if ($userId) {
            $query .= " AND id_usuario != ?";
            $params[] = $userId;
        }
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    private function isPasswordComplex($password) {
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password);
    }
}

$host = 'localhost';
$dbname = 'db_community_appraisals';
$username = 'root';
$password = '';

$userHandler = new UserHandler($host, $dbname, $username, $password);

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_usuario'] !== 'admin') {
    header('Location: login_index.html');
    exit;
}

$id_usuario = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_usuario) {
    header('Location: main_admin_createuser.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $csrf_token = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_STRING);
    if (!$csrf_token || $csrf_token !== $_SESSION['csrf_token'] || time() > $_SESSION['csrf_token_expiry']) {
        die('CSRF token validation failed');
    }

    $userData = [
        'nombre_completo' => filter_input(INPUT_POST, 'nombre_completo', FILTER_SANITIZE_STRING),
        'compañía' => filter_input(INPUT_POST, 'compañía', FILTER_SANITIZE_STRING),
        'rol_usuario' => filter_input(INPUT_POST, 'rol_usuario', FILTER_SANITIZE_STRING),
        'correo_electronico' => filter_input(INPUT_POST, 'correo_electronico', FILTER_VALIDATE_EMAIL),
        'teléfono' => filter_input(INPUT_POST, 'teléfono', FILTER_SANITIZE_STRING),
        'país' => filter_input(INPUT_POST, 'país', FILTER_SANITIZE_STRING),
        'contraseña' => $_POST['contraseña']
    ];

    if ($userHandler->emailExists($userData['correo_electronico'], $id_usuario)) {
        echo "<script>alert('This email is already in use.'); window.history.back();</script>";
        exit;
    }

    $userHandler->updateUser($userData, $id_usuario);

    header('Location: main_admin_createuser.php');
    exit;
}

$usuario = $userHandler->getUserById($id_usuario);

if (!$usuario) {
    header('Location: main_admin_createuser.php');
    exit;
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$_SESSION['csrf_token_expiry'] = time() + 3600; // Token expires in 1 hour
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-10 rounded-lg shadow-md w-full max-w-lg">
            <h1 class="text-3xl font-semibold mb-8 text-center">Edit User</h1>
            <form action="main_admin_edit_user.php?id=<?php echo htmlspecialchars($id_usuario); ?>" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="relative">
                    <i class="fas fa-user absolute left-3 top-4 text-gray-400"></i>
                    <input type="text" id="nombre_completo" name="nombre_completo" class="w-full pl-10 p-3 border rounded-md" placeholder="Full Name" value="<?php echo htmlspecialchars($usuario['nombre_completo'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="relative">
                    <i class="fas fa-building absolute left-3 top-4 text-gray-400"></i>
                    <input type="text" id="compañía" name="compañía" class="w-full pl-10 p-3 border rounded-md" placeholder="Company" value="<?php echo htmlspecialchars($usuario['compañía'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="relative">
                    <i class="fas fa-user-tag absolute left-3 top-4 text-gray-400"></i>
                    <select id="rol_usuario" name="rol_usuario" class="w-full pl-10 p-3 border rounded-md" required>
                        <option value="user" <?php if ($usuario['rol_usuario'] == 'user') echo 'selected'; ?>>User</option>
                        <option value="admin" <?php if ($usuario['rol_usuario'] == 'admin') echo 'selected'; ?>>Admin</option>
                    </select>
                </div>
                <div class="relative">
                    <i class="fas fa-envelope absolute left-3 top-4 text-gray-400"></i>
                    <input type="email" id="correo_electronico" name="correo_electronico" class="w-full pl-10 p-3 border rounded-md" placeholder="Email" value="<?php echo htmlspecialchars($usuario['correo_electronico'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    <p id="email-error" class="text-red-500 text-sm hidden">This email is already in use.</p>
                </div>
                <div class="relative">
                    <i class="fas fa-phone absolute left-3 top-4 text-gray-400"></i>
                    <input type="tel" id="teléfono" name="teléfono" class="w-full pl-10 p-3 border rounded-md" placeholder="Phone" value="<?php echo htmlspecialchars($usuario['teléfono'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    <p id="phone-error" class="text-red-500 text-sm hidden">Invalid phone number.</p>
                </div>
                <div class="relative">
                    <i class="fas fa-globe absolute left-3 top-4 text-gray-400"></i>
                    <input type="text" id="país" name="país" class="w-full pl-10 p-3 border rounded-md" placeholder="Country" value="<?php echo htmlspecialchars($usuario['país'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="relative">
                    <i class="fas fa-lock absolute left-3 top-4 text-gray-400"></i>
                    <input type="password" id="contraseña" name="contraseña" class="w-full pl-10 p-3 border rounded-md" placeholder="New Password (leave blank to keep current password)">
                </div>
                <div class="flex justify-between">
                    <button type="submit" class="w-1/2 mr-2 p-3 bg-blue-500 text-white rounded-md hover:bg-blue-600"><i class="fas fa-save mr-2"></i>Save Changes</button>
                    <button type="button" class="w-1/2 ml-2 p-3 bg-red-500 text-white rounded-md hover:bg-red-600" onclick="history.back()"><i class="fas fa-times mr-2"></i>Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            const validatePhone = (phone) => {
                const phonePattern = /^[2-9]{1}[0-9]{9}$/;
                const allSameDigits = /^(.)\1{9}$/;
                return phonePattern.test(phone) && !allSameDigits.test(phone);
            };

            $('#correo_electronico').on('input', function() {
                var email = $(this).val();
                var userId = '<?php echo $id_usuario; ?>';

                $.ajax({
                    url: 'main_admin_edit_user.php',
                    method: 'POST',
                    data: {
                        action: 'check_email',
                        email: email,
                        user_id: userId
                    },
                    success: function(response) {
                        if (response === 'exists') {
                            $('#email-error').removeClass('hidden');
                            $('button[type="submit"]').attr('disabled', 'disabled');
                        } else {
                            $('#email-error').addClass('hidden');
                            $('button[type="submit"]').removeAttr('disabled');
                        }
                    }
                });
            });

            $('#teléfono').on('input', function() {
                $(this).val($(this).val().replace(/\D/g, '')); // Remove non-digit characters
                const phone = $(this).val();
                if (phone) {
                    if (!validatePhone(phone)) {
                        $('#phone-error').removeClass('hidden');
                        $('button[type="submit"]').attr('disabled', 'disabled');
                    } else {
                        $('#phone-error').addClass('hidden');
                        $('button[type="submit"]').removeAttr('disabled');
                    }
                } else {
                    $('#phone-error').addClass('hidden');
                    $('button[type="submit"]').removeAttr('disabled');
                }
            });
        });
    </script>
</body>

</html>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'check_email') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    if ($userHandler->emailExists($email, $userId)) {
        echo 'exists';
    } else {
        echo 'available';
    }
    exit;
}
?>
