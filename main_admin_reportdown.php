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
    die("ERROR: No se pudo conectar. " . htmlspecialchars($e->getMessage()));
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

$exportPath = 'export_cloud';
$folders = array_filter(glob($exportPath . '/*'), 'is_dir');

$foldersWithDates = [];
foreach ($folders as $folder) {
    $foldersWithDates[] = [
        'name' => htmlspecialchars(basename($folder)),
        'date' => htmlspecialchars(date("F d Y", filectime($folder)))
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $folder = $_POST['folder'] ?? '';
    if (!empty($folder)) {
        $folder = basename($folder); // Sanitiza el nombre del folder
        $folderPath = "$exportPath/$folder";
        if (is_dir($folderPath)) {
            if (isset($_POST['download'])) {
                $zip = new ZipArchive();
                $zipFile = tempnam(sys_get_temp_dir(), 'zip');
                if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folderPath));
                    foreach ($files as $file) {
                        if (!$file->isDir()) {
                            $filePath = $file->getRealPath();
                            $relativePath = substr($filePath, strlen($folderPath) + 1);
                            $zip->addFile($filePath, $relativePath);
                        }
                    }
                    $zip->close();

                    if (file_exists($zipFile)) {
                        header('Content-Type: application/zip');
                        header('Content-Description: File Transfer');
                        header('Content-Disposition: attachment; filename="' . basename($folder) . '.zip"');
                        header('Content-Transfer-Encoding: binary');
                        header('Expires: 0');
                        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                        header('Pragma: public');
                        header('Content-Length: ' . filesize($zipFile));
                        readfile($zipFile);
                        unlink($zipFile);
                        exit;
                    } else {
                        die('No se pudo crear el archivo zip.');
                    }
                } else {
                    die('No se pudo abrir el archivo zip.');
                }
            } elseif (isset($_POST['delete'])) {
                $it = new RecursiveDirectoryIterator($folderPath, RecursiveDirectoryIterator::SKIP_DOTS);
                $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
                foreach ($files as $file) {
                    if ($file->isDir()) {
                        rmdir($file->getRealPath());
                    } else {
                        unlink($file->getRealPath());
                    }
                }
                rmdir($folderPath);
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        } else {
            die('El folder especificado no existe.');
        }
    } else {
        die('No se ha especificado un folder.');
    }
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
        function confirmDelete() {
            return confirm('Are you sure you want to delete this folder? This action cannot be undone.');
        }
    </script>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <header class="bg-gray-200 text-gray-700 p-4 shadow">
            <div class="flex items-center justify-between">
                <button type="button" onclick="window.location.href='main_admin.php'" class="inline-block mb-4 p-2 bg-blue-500 text-white rounded-full hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <h1 class="text-lg font-semibold">Download Inspection Report</h1>
                <div></div>
            </div>
        </header>
        <main class="flex-grow p-4">
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-2xl font-semibold mb-4">Available Folders</h2>
                <?php if (count($foldersWithDates) > 0) : ?>
                    <ul class="space-y-4">
                        <?php foreach ($foldersWithDates as $folder) : ?>
                            <li class="flex items-center justify-between p-4 bg-gray-100 rounded-lg shadow">
                                <div>
                                    <span class="block"><?php echo $folder['name']; ?></span>
                                    <span class="block text-gray-500 text-sm"><?php echo $folder['date']; ?></span>
                                </div>
                                <div class="flex space-x-2">
                                    <form method="post" class="inline">
                                        <input type="hidden" name="folder" value="<?php echo $folder['name']; ?>">
                                        <button name="download" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring focus:border-blue-300">
                                        <i class="fas fa-download mr-2"></i>Download
                                        </button>
                                    </form>
                                    <form method="post" class="inline" onsubmit="return confirmDelete();">
                                        <input type="hidden" name="folder" value="<?php echo $folder['name']; ?>">
                                        <button name="delete" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 focus:outline-none focus:ring focus:border-red-300">
                                        <i class="fas fa-trash-alt mr-2"></i>Delete
                                        </button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p>No folders found.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>