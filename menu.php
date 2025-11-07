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

if (!isset($_SESSION['usuario_id'])) {
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

if (isset($_GET['id'])) {
    $inspectionId = htmlspecialchars($_GET['id']);
} else {
    echo "Error: ID de inspección no proporcionado.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .button-active {
            animation: button-press 0.3s forwards;
        }

        @keyframes button-press {
            0% {
                background-color: rgba(255, 255, 255, 0.1);
            }

            100% {
                background-color: rgba(255, 255, 255, 0);
            }
        }
    </style>
</head>

<body class="bg-gray-100 flex items-center justify-center">
    <div class="w-full h-screen max-w-md bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-4 bg-white flex items-center justify-between">
            <button id="closeButton" class="text-gray-400 hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <img src="img/logo.png" alt="Mi Logo" class="h-11 w-auto">
        </div>
        <div class="p-4 text-center bg-gray-900 text-white">
            <p class="text-xl font-semibold"><?php echo $nombre_usuario; ?></p>
            <p class="text-sm"><?php echo $correo_usuario; ?></p>
        </div>
        <div class="p-4">
            <div class="w-full p-4">
                <hr class="mb-6" />
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-muted-foreground">Account Settings</h3>
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center space-x-2 button" id="editProfileButton">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span>Edit Profile</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                            <path d="m9 18 6-6-6-6"></path>
                        </svg>
                    </div>
                    <div class="flex items-center justify-between w-full hidden">
                        <div class="flex items-center space-x-2 button" data-target="general">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                                <path d="M12 20a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z"></path>
                                <path d="M12 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"></path>
                                <path d="M12 2v2"></path>
                                <path d="M12 22v-2"></path>
                                <path d="m17 20.66-1-1.73"></path>
                                <path d="M11 10.27 7 3.34"></path>
                                <path d="m20.66 17-1.73-1"></path>
                                <path d="m3.34 7 1.73 1"></path>
                                <path d="M14 12h8"></path>
                                <path d="M2 12h2"></path>
                                <path d="m20.66 7-1.73 1"></path>
                                <path d="m3.34 17 1.73-1"></path>
                                <path d="m17 3.34-1 1.73"></path>
                                <path d="m11 13.73-4 6.93"></path>
                            </svg>
                            <span>General</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                            <path d="m9 18 6-6-6-6"></path>
                        </svg>
                    </div>
                    <div class="flex items-center justify-between w-full hidden">
                        <div class="flex items-center space-x-2 button" data-target="billing">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                                <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                                <line x1="2" x2="22" y1="10" y2="10"></line>
                            </svg>
                            <span>Billing</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                            <path d="m9 18 6-6-6-6"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-6 space-y-4">
                    <h3 class="text-sm font-semibold text-muted-foreground hidden">Support</h3>
                    <div class="flex items-center justify-between w-full hidden">
                        <div class="flex items-center space-x-2 button" data-target="support">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                                <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"></path>
                                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                <path d="M12 17h.01"></path>
                            </svg>
                            <span>Help &amp; Support</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                            <path d="m9 18 6-6-6-6"></path>
                        </svg>
                    </div>
                    <div class="flex items-center justify-between w-full hidden">
                        <div class="flex items-center space-x-2 button" data-target="terms">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                                <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                                <path d="M10 9H8"></path>
                                <path d="M16 13H8"></path>
                                <path d="M16 17H8"></path>
                            </svg>
                            <span>Terms and Conditions</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                            <path d="m9 18 6-6-6-6"></path>
                        </svg>
                    </div>
                    <div class="flex items-center justify-between w-full hidden">
                        <div class="flex items-center space-x-2 button" data-target="delete-account">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                                <path d="M3 6h18"></path>
                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                            </svg>
                            <span>Delete Account</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                            <path d="m9 18 6-6-6-6"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-6 w-full">
                    <button id="logoutButton" class="flex items-center space-x-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-300 w-full">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" x2="9" y1="12" y2="12"></line>
                        </svg>
                        <span class="ml-2">Log Out</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userId = "<?php echo urlencode($usuario_id); ?>";

            document.getElementById('logoutButton').addEventListener('click', function() {
                document.cookie.split(";").forEach(function(c) {
                    document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
                });
                window.location.href = 'login_index.html';
            });

            const closeButton = document.getElementById('closeButton');
            closeButton.addEventListener('click', function() {
                const inspectionId = "<?php echo $inspectionId; ?>";
                window.location.href = `main_user.php?id=${inspectionId}`;
            });

            document.getElementById('editProfileButton').addEventListener('click', function() {
                window.location.href = `edit_profile.php?usuario_id=${userId}`;
            });

            document.querySelectorAll('.button').forEach(button => {
                button.addEventListener('click', function() {
                    button.classList.add('button-active');
                    setTimeout(() => {
                        button.classList.remove('button-active');
                    }, 300);
                });
            });
        });
    </script>
</body>

</html>