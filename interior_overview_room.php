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
    die('Usuario no encontrado.');
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $inspectionId = (int)$_GET['id'];
} else {
    echo "Error: Inspection ID not provided or invalid.";
    exit;
}

// Verificar si num_pisos tiene datos para el inspectionId proporcionado
$query = "SELECT num_pisos FROM vt_num_pisos WHERE id_inspeccion = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$inspectionId]);

$num_pisos_valido = false;
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (!empty($row['num_pisos'])) {
        $num_pisos_valido = true;
    }
}

// Verificar si basement contiene datos para el inspectionId proporcionado
$query = "SELECT basement FROM tb_interior_inspecciones WHERE id_inspeccion = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$inspectionId]);

$basement_valido = false;
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (!empty($row['basement'])) {
        $basement_valido = true;
    }
}

// Verificar ambas condiciones
if (!$num_pisos_valido || !$basement_valido) {
    echo "<script>
        alert('To modify this section, you must first complete the Style and Basement sections.');
        window.history.back();
    </script>";
    exit;
}

$num_pisos = 0;
$query = "SELECT num_pisos FROM vt_num_pisos WHERE id_inspeccion = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$inspectionId]);
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $num_pisos = (int)$row['num_pisos'];
}

$titulo = "Room allocation";
$moduls = 39;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['photo_add']) && $_FILES['photo_add']['error'] == UPLOAD_ERR_OK) {
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $fileExtension = pathinfo($_FILES['photo_add']['name'], PATHINFO_EXTENSION);

        if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
            echo "Error: File extension not allowed.";
            exit;
        }

        $inspectionId = (int)$_POST['inspectionId'];
        $room = htmlspecialchars(trim($_POST['room']), ENT_QUOTES, 'UTF-8');
        $photoName = htmlspecialchars(trim($_POST['photoName']), ENT_QUOTES, 'UTF-8');
        $folderName = "img/photo_gallery/{$inspectionId}/Room_allocation/{$room}{$photoName}";

        if (!file_exists($folderName)) {
            mkdir($folderName, 0777, true);
        }

        $filePath = $folderName . "/" . uniqid() . ".jpg";
        if (move_uploaded_file($_FILES['photo_add']['tmp_name'], $filePath)) {
            echo "Photo saved successfully.";
        } else {
            echo "Error saving the photo.";
        }
        exit;
    }

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $fileExtension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);

        if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
            echo "Error: Extensión de archivo no permitida.";
            exit;
        }

        $inspectionId = (int)$_POST['inspectionId'];
        $room = htmlspecialchars(trim($_POST['room']), ENT_QUOTES, 'UTF-8');
        $photoName = htmlspecialchars(trim($_POST['photoName']), ENT_QUOTES, 'UTF-8');
        $folderName = "img/photo_gallery/{$inspectionId}/Room_allocation/{$room}_{$photoName}";

        if (!file_exists($folderName)) {
            mkdir($folderName, 0777, true);
        }

        $filePath = $folderName . "/" . htmlspecialchars(trim($_POST['selectedLevel']), ENT_QUOTES, 'UTF-8') . "_" . uniqid() . ".jpg";
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $filePath)) {
            $stmt = $pdo->prepare("INSERT INTO tbl_direccion_room_allocation (id_inspeccion, photo_address) VALUES (?, ?)");
            $stmt->execute([$inspectionId, $filePath]);
            echo "Foto guardada con éxito.";
        } else {
            echo "Error al guardar la foto.";
        }
        exit;
    }

    if (isset($_POST['add_note'])) {
        try {
            $note = trim($_POST['note']);
            if (!empty($note)) {
                $stmt = $pdo->prepare("SELECT notas_int_room_allocation FROM tb_interior_inspecciones WHERE id_inspeccion = ?");
                $stmt->execute([$inspectionId]);
                $existingNotes = $stmt->fetchColumn();

                $note = ucfirst(htmlspecialchars($note, ENT_QUOTES, 'UTF-8'));
                if (substr($note, -1) !== '.') {
                    $note .= '.';
                }

                $existingNotes = trim($existingNotes);
                $updatedNotes = empty($existingNotes) ? $note : $existingNotes . "\n\n" . $note;

                $stmt = $pdo->prepare("UPDATE tb_interior_inspecciones SET notas_int_room_allocation = ? WHERE id_inspeccion = ?");
                $stmt->execute([$updatedNotes, $inspectionId]);
                $_SESSION['note_saved'] = true;

                header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $inspectionId . "&note_saved=true");
                exit;
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    if (isset($_POST['delete_photo'])) {
        $photoAddress = htmlspecialchars($_POST['photo_address'], ENT_QUOTES, 'UTF-8');
        $fromModal = isset($_POST['fromModal']) ? (bool)$_POST['fromModal'] : false;

        if ($fromModal) {
            if (unlink($photoAddress)) {

                echo "Photo deleted successfully.";
            } else {
                echo "Error deleting the photo.";
            }
        } else {
            $folderPath = dirname($photoAddress);

            function deleteDirectory($dir)
            {
                if (!file_exists($dir)) {
                    return false;
                }
                if (!is_dir($dir)) {
                    return unlink($dir);
                }
                foreach (scandir($dir) as $item) {
                    if ($item == '.' || $item == '..') {
                        continue;
                    }
                    if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                        return false;
                    }
                }
                return rmdir($dir);
            }

            if (deleteDirectory($folderPath)) {
                $stmt = $pdo->prepare("DELETE FROM tbl_direccion_room_allocation WHERE photo_address = ?");
                $stmt->execute([$photoAddress]);
                echo "Photo and folder deleted successfully.";
            } else {
                echo "Error deleting the folder.";
            }
        }
        exit;
    }

    if (isset($_POST['quick_note'])) {
        try {
            $note = trim($_POST['note']);
            $room = htmlspecialchars(trim($_POST['room_for_note']), ENT_QUOTES, 'UTF-8');
            if (!empty($note) && !empty($room)) {
                $stmt = $pdo->prepare("SELECT notas_int_room_allocation FROM tb_interior_inspecciones WHERE id_inspeccion = ?");
                $stmt->execute([$inspectionId]);
                $existingNotes = $stmt->fetchColumn();
    
                $note = ucfirst(htmlspecialchars($note, ENT_QUOTES, 'UTF-8'));
                if (substr($note, -1) !== '.') {
                    $note .= '.';
                }
    
                $roomParts = explode(' ', $room);
                $shortRoomName = implode(' ', array_slice($roomParts, 1));
    
                $note = $shortRoomName . ': ' . $note;
    
                $existingNotes = trim($existingNotes);
                $updatedNotes = empty($existingNotes) ? $note : $existingNotes . "\n\n" . $note;
    
                $stmt = $pdo->prepare("UPDATE tb_interior_inspecciones SET notas_int_room_allocation = ? WHERE id_inspeccion = ?");
                $stmt->execute([$updatedNotes, $inspectionId]);
                $_SESSION['note_saved'] = true;
    
                header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $inspectionId . "&note_saved=true");
                exit;
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }    
}

function getPhotoAddresses($pdo, $inspectionId, $selectedLevel)
{
    $stmt = $pdo->prepare("SELECT photo_address FROM tbl_direccion_room_allocation WHERE id_inspeccion = ? AND piso = ?");
    $stmt->execute([$inspectionId, $selectedLevel]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$photoAddresses = getPhotoAddresses($pdo, $inspectionId, isset($_GET['level']) ? htmlspecialchars($_GET['level'], ENT_QUOTES, 'UTF-8') : 'Above Grade Level 1');

if (isset($_GET['get_photos']) && isset($_GET['folder_name'])) {
    $folderName = htmlspecialchars($_GET['folder_name'], ENT_QUOTES, 'UTF-8');
    $directory = "img/photo_gallery/{$inspectionId}/Room_allocation/{$folderName}";
    $photos = glob($directory . "/*.{jpg,jpeg,png}", GLOB_BRACE);
    echo json_encode($photos);
    exit;
}

function getLastFolderName($path, $room)
{
    $pathParts = explode('/', $path);
    $lastPart = isset($pathParts[count($pathParts) - 2]) ? $pathParts[count($pathParts) - 2] : $path;

    if (strpos($lastPart, $room) === 0) {
        $lastPart = substr($lastPart, strlen($room));
        if (strpos($lastPart, '_') === 0) {
            $lastPart = substr($lastPart, 1);
        }
    }

    return $lastPart;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Allocation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body,
        html {
            height: 100%;
        }

        #messageBox {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 20px;
            border-radius: 10px;
            color: white;
            border: 1px solid #ccc;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5);
            z-index: 1000;
            font-size: 20px;
            width: auto;
            max-width: 80%;
            text-align: center;
        }

        .info {
            background-color: #B79891;
            color: white;
        }

        .success {
            background-color: #2fa84d;
            color: white;
        }

        .error {
            background-color: #DC3545;
            color: white;
        }

        .custom-select {
            background-color: #1f2937;
            color: white;
        }

        .custom-select option {
            background-color: #1f2937;
            color: white;
        }

        .modified-span {
            padding-left: 10px;
        }

        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .icon-group {
            display: flex;
            gap: 5px;
            align-items: center;
            margin-left: 50px;
        }

        /* Estilos para la galería de fotos */
        .gallery-photo {
            position: relative;
        }

        .gallery-photo img {
            display: block;
            width: 100%;
            height: auto;
        }

        .delete-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.5);
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body>
    <?php if (isset($_SESSION['note_saved']) && $_SESSION['note_saved']) : ?>
        <div id="successMessage" class="fixed bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-2 rounded-md bg-green-100 px-4 py-3 text-green-800">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span class="font-medium">Note Saved</span>
        </div>
        <script>
            setTimeout(function() {
                document.getElementById('successMessage').style.display = 'none';
            }, 3000);
        </script>
        <?php unset($_SESSION['note_saved']); ?>
    <?php endif; ?>

    <div class="bg-white p-4">
        <div class="flex items-center justify-between border-b pb-3">
            <a href="#" onclick="saveAndGoBack()" class="text-gray-600 h-6 w-6">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
            </a>
            <h1 id="super_titulo" class="text-xl font-semibold text-center">Room allocation</h1>
            <div style="width:24px"></div>
        </div>
        <div class="flex justify-between mt-4">
            <button onclick="window.location.href='component/note.php?id=<?php echo $inspectionId; ?>&moduls=<?php echo $moduls; ?>'" class="flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-blue-500 hover:bg-blue-600 text-white h-10 px-4 py-2 flex-1 mr-2">
                <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" />
                    <line x1="13" y1="20" x2="20" y2="13" />
                    <path d="M13 20v-6a1 1 0 0 1 1 -1h6v-7a2 2 0 0 0 -2 -2h-12a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7" />
                </svg>
                <span class="ml-2">All Notes</span>
            </button>

            <button onclick="redirectToGallery()" class="hidden flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-green-500 hover:bg-green-600 text-white h-10 px-4 py-2 flex-1 ml-2">
                <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
                    <circle cx="8.5" cy="8.5" r="1.5" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.4 14.5L16 10 4 20h16v-2.1z" />
                </svg>
                <span class="ml-2">Gallery</span>
            </button>
        </div>

        <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-secondary text-secondary-foreground hover:bg-secondary/80 h-10 px-4 py-2" id="addPhotoBtn">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white h-6 w-6">
                <path d="M5 12h14"></path>
                <path d="M12 5v14"></path>
            </svg>
        </button>
        <div class="mt-4 border-b pb-3">
            <h2 class="text-lg font-semibold">Choose one or several options</h2>
        </div>
        <form id="photoUploadForm" method="POST" enctype="multipart/form-data" style="display: none;">
            <input type="file" accept="image/*" capture="camera" id="photoInput" name="photo">
            <input type="file" accept="image/*" capture="camera" id="photoInputAdd" name="photo_add" style="display:none;">
            <input type="hidden" name="inspectionId" value="<?php echo $inspectionId; ?>">
            <input type="hidden" name="room" id="roomInput">
            <input type="hidden" name="photoName" id="photoNameInput">
            <input type="hidden" name="photoAddress" id="photoAddressInput">
            <input type="hidden" name="fromGreenButton" id="fromGreenButton" value="0">
            <input type="submit" value="Upload Photo">
            <input type="hidden" name="fromModal" id="fromModal" value="0">
            <input type="hidden" name="selectedLevel" id="selectedLevelInput">
        </form>



        <div class="hidden grid grid-cols-2 gap-4 mt-4">
            <button id="takePhotoButton" class="whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input hover:text-accent-foreground h-10 px-4 flex items-center justify-center py-2 bg-gray-200 hover:bg-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-600 h-6 w-6">
                    <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"></path>
                    <circle cx="12" cy="13" r="3"></circle>
                </svg>
                <span class="ml-2">Take Photos</span>
            </button>

            <button id="addNoteBtn" class="whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input hover:text-accent-foreground h-10 px-4 flex items-center justify-center py-2 bg-gray-200 hover:bg-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-600 h-6 w-6">
                    <path d="M17 6.1H3"></path>
                    <path d="M21 12.1H3"></path>
                    <path d="M15.1 18H3"></path>
                </svg>
                <span class="ml-2">Add Notes</span>
            </button>
        </div>

        <div class="w-full flex items-center bg-gray-300 p-2 rounded-lg shadow-md mt-8">
            <div class="relative w-full">
                <select id="level" class="block appearance-none w-full bg-gray-200 text-black border border-white hover:border-gray-500 px-4 py-2 pr-8 rounded leading-tight focus:outline-none focus:shadow-outline custom-select" onchange="filterPhotosByLevel()">
                    <?php for ($i = 1; $i <= $num_pisos; $i++) :
                        $levelOption = "Above Grade Level $i"; ?>
                        <option <?php echo (isset($_GET['level']) && $_GET['level'] == $levelOption) ? 'selected' : ''; ?>>
                            <?php echo $levelOption; ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-white">
                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path d="M7 10l5 5 5-5z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="mt-4 space-y-2" id="roomsContainer">
            <?php
            $rooms = ['ENTRANCE', 'LIVING ROOM', 'DINING ROOM', 'KITCHEN', 'BEDROOMS', 'BATHROOM', 'FAMILY ROOM', 'LAUNDRY ROOM', 'DEN', 'OFFICE', 'LIBRARY', 'MUD ROOM', 'COLD ROOM', 'RECREATIONAL ROOM'];

            $displayedFolders = [];

            foreach ($rooms as $room) {
                echo '<div class="flex justify-between items-center border-b py-2 px-4">';
                echo "<span class='font-bold'>{$room}</span>";
                echo '<button class="text-stone-500 hover:text-gray-700 add-photo-btn">';
                echo '<svg class="h-8 w-8 text-stone-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">';
                echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>';
                echo '</svg>';
                echo '</button>';
                echo '</div>';

                foreach ($photoAddresses as $photo) {
                    if (strpos($photo['photo_address'], $room) !== false) {
                        $lastFolder = getLastFolderName($photo['photo_address'], $room);
                        $fullFolder = basename(dirname($photo['photo_address']));
                        if (!in_array($lastFolder, $displayedFolders)) {
                            $displayedFolders[] = $lastFolder;
                            echo "<div class='text-sm text-gray-600 flex items-center w-full'>";
                            echo "<span class='text-base modified-span w-1/2' data-db-value='{$photo['photo_address']}'>* {$lastFolder}</span>";
                            echo "<div class='icon-group w-1/2'>";
                            echo '<button class="text-green-500 hover:text-green-700 add-photo-btn" data-photo-address="' . $photo['photo_address'] . '">';
                            echo '<svg class="h-7 w-7 text-green-500" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">';
                            echo '<path stroke="none" d="M0 0h24H0z"/>';
                            echo '<circle cx="12" cy="13" r="3"/>';
                            echo '<path d="M5 7h1a2 2 0 0 0 2 -2a1 1 0 0 1 1 -1h2m9 7v7a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-9a2 2 0 0 1 2 -2"/>';
                            echo '<line x1="15" y1="6" x2="21" y2="6"/>';
                            echo '<line x1="18" y1="3" x2="18" y2="9"/>';
                            echo '</svg>';
                            echo '</button>';
                            echo '<button class="ml-2 text-stone-500 hover:text-gray-700 gallery-btn" data-folder-name="' . $fullFolder . '">';
                            echo '<svg class="h-7 w-7 text-stone-500" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">';
                            echo '<path stroke="none" d="M0 0h24V0z"/>';
                            echo '<line x1="15" y1="8" x2="15.01" y2="8"/>';
                            echo '<rect x="4" y="4" width="16" height="16" rx="3"/>';
                            echo '<path d="M4 15l4 -4a3 5 0 0 1 3 0l 5 5"/>';
                            echo '<path d="M14 14l1 -1a3 5 0 0 1 3 0l 2 2"/>';
                            echo '</svg>';
                            echo '</button>';
                            echo '<button class="ml-2 text-stone-500 hover:text-gray-700 quick-note-btn" data-room="' . $room . '" data-db-value="' . $lastFolder . '">';
                            echo '<svg class="h-7 w-7 text-stone-500" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">';
                            echo '<path stroke="none" d="M0 0h24H0z"/>';
                            echo '<line x1="13" y1="20" x2="20" y2="13"/>';
                            echo '<path d="M13 20v-6a1 1 0 0 1 1 -1h6v-7a2 2 0 0 0 -2 -2h-12a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7"/>';
                            echo '</svg>';
                            echo '</button>';
                            echo '<button class="ml-1 text-red-500 hover:text-red-700 delete-photo-btn" data-photo-address="' . $photo['photo_address'] . '">';
                            echo '<svg class="h-7 w-7 text-red-500" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">';
                            echo '<path stroke="none" d="M0 0h24H0z"/>';
                            echo '<path d="M4 7h16M10 11v6m4-6v6M5 7l1 14a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-14M9 7V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v3"/>';
                            echo '</svg>';
                            echo '</button>';
                            echo '</div>';
                            echo '</div>';
                        }
                    }
                }
            }
            ?>
        </div>
    </div>
    <div id="noteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center">
        <div class="bg-white p-4 rounded-lg shadow-lg">
            <h2 class="font-semibold text-lg mb-2">Add a Note</h2>
            <form action="" method="POST">
                <textarea id="noteInput" name="note" rows="4" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-accent" placeholder="Enter your note here..." autofocus></textarea>
                <input type="hidden" id="roomForNote" name="room_for_note">
                <div class="flex justify-end space-x-2 mt-4">
                    <button type="button" onclick="closeNoteModal()" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Cancel
                    </button>
                    <button type="submit" name="quick_note" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Save
                    </button>

                </div>
            </form>
        </div>
    </div>

    <div id="messageBox" class="hidden fixed inset-0 flex items-center justify-center px-4 py-2 border border-gray-300 shadow-lg z-50 font-medium text-lg bg-white text-black rounded-md" style="width: 60%; max-width: 600px; min-width: 300px;"></div>

    <div id="galleryModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden gallery-modal-overlay">
        <div class="bg-white p-4 rounded-lg shadow-lg relative gallery-modal-content">
            <button id="closeGalleryModal" class="hidden absolute top-2 right-2 bg-red-500 text-white rounded-full p-1">
                X
            </button>
            <div id="galleryContent" class="grid grid-cols-4 gap-2">

            </div>
        </div>
    </div>

    <script>
        const addNoteBtn = document.getElementById('addNoteBtn');
        const noteModal = document.getElementById('noteModal');
        const noteInput = document.getElementById('noteInput');
        const roomForNote = document.getElementById('roomForNote');
        const saveBtn = noteModal.querySelector('button[type="submit"]');
        const cancelBtn = noteModal.querySelector('button[type="button"]');

        var selectedLevel = document.getElementById('level').value;

        function openNoteModal(room, dbValue) {
            roomForNote.value = room + ' ' + dbValue;
            noteModal.classList.remove('hidden');
            noteInput.focus();
            checkInput();
        }

        function closeNoteModal() {
            noteModal.classList.add('hidden');
            window.location.href = window.location.pathname + '?id=<?php echo $inspectionId; ?>';
        }

        addNoteBtn.addEventListener('click', () => openNoteModal(''));
        cancelBtn.addEventListener('click', closeNoteModal);

        window.addEventListener('click', function(event) {
            if (event.target === noteModal) {
                closeNoteModal();
            }
        });

        noteInput.addEventListener('input', checkInput);

        function checkInput() {
            if (noteInput.value.trim().length > 0) {
                saveBtn.disabled = false;
            } else {
                saveBtn.disabled = true;
            }
        }

        function saveAndGoBack() {
            window.location.href = 'interior_overview_menu.php?id=<?php echo $inspectionId; ?>';
        }

        function redirectToGallery(folderName) {
            var inspectionId = '<?php echo $inspectionId; ?>';
            var url = 'component/gallery.php?id=' + encodeURIComponent(inspectionId) + '&titulo=' + encodeURIComponent(folderName);
            window.location.href = url;
        }

        document.querySelectorAll('.gallery-btn').forEach(button => {
            button.addEventListener('click', function() {
                var folderName = this.getAttribute('data-folder-name');
                showGalleryModal(folderName);
            });
        });

        document.getElementById('takePhotoButton').addEventListener('click', function() {
            fileInput.click();
        });

        document.getElementById('addPhotoBtn').addEventListener('click', function() {
            fileInput.click();
        });

        const addPhotoButtons = document.querySelectorAll('.add-photo-btn');

        function createStyledPrompt(message) {
            const overlay = document.createElement('div');
            overlay.style.position = 'fixed';
            overlay.style.top = '0';
            overlay.style.left = '0';
            overlay.style.width = '100%';
            overlay.style.height = '100%';
            overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
            overlay.style.display = 'flex';
            overlay.style.justifyContent = 'center';
            overlay.style.alignItems = 'center';
            overlay.style.zIndex = '1000';

            const promptBox = document.createElement('div');
            promptBox.style.backgroundColor = 'white';
            promptBox.style.padding = '20px';
            promptBox.style.borderRadius = '10px';
            promptBox.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.1)';
            promptBox.style.textAlign = 'center';
            promptBox.style.width = '300px';

            const messageText = document.createElement('p');
            messageText.textContent = message;
            messageText.style.marginBottom = '20px';
            messageText.style.fontSize = '16px';
            messageText.style.color = '#333';

            const input = document.createElement('input');
            input.type = 'text';
            input.placeholder = 'Enter photo name';
            input.style.padding = '10px';
            input.style.border = '1px solid #ccc';
            input.style.borderRadius = '5px';
            input.style.width = '100%';
            input.style.marginBottom = '20px';
            input.style.boxSizing = 'border-box';

            const buttonContainer = document.createElement('div');
            buttonContainer.style.display = 'flex';
            buttonContainer.style.justifyContent = 'space-between';

            const cancelButton = document.createElement('button');
            cancelButton.textContent = 'Cancel';
            cancelButton.style.padding = '10px 20px';
            cancelButton.style.border = 'none';
            cancelButton.style.borderRadius = '5px';
            cancelButton.style.backgroundColor = '#dc3545';
            cancelButton.style.color = 'white';
            cancelButton.style.cursor = 'pointer';
            cancelButton.style.flex = '1';
            cancelButton.style.marginRight = '10px';

            const confirmButton = document.createElement('button');
            confirmButton.textContent = 'OK';
            confirmButton.style.padding = '10px 20px';
            confirmButton.style.border = 'none';
            confirmButton.style.borderRadius = '5px';
            confirmButton.style.backgroundColor = '#28a745';
            confirmButton.style.color = 'white';
            confirmButton.style.cursor = 'pointer';
            confirmButton.style.flex = '1';

            promptBox.appendChild(messageText);
            promptBox.appendChild(input);
            buttonContainer.appendChild(cancelButton);
            buttonContainer.appendChild(confirmButton);
            promptBox.appendChild(buttonContainer);
            overlay.appendChild(promptBox);
            document.body.appendChild(overlay);

            input.focus();

            return new Promise((resolve) => {
                confirmButton.addEventListener('click', () => {
                    resolve(input.value);
                    document.body.removeChild(overlay);
                });

                cancelButton.addEventListener('click', () => {
                    resolve(null);
                    document.body.removeChild(overlay);
                });

                overlay.addEventListener('click', (event) => {
                    if (event.target === overlay) {
                        resolve(null);
                        document.body.removeChild(overlay);
                    }
                });

                input.addEventListener('keypress', (event) => {
                    if (event.key === 'Enter') {
                        resolve(input.value);
                        document.body.removeChild(overlay);
                    }
                });
            });
        }
        addPhotoButtons.forEach(button => {
            button.addEventListener('click', async function() {
                const room = this.parentElement.querySelector('span').textContent.trim();
                document.getElementById('roomInput').value = room;
                document.getElementById('selectedLevelInput').value = document.getElementById('level').value;

                const existingFolders = document.querySelectorAll(`span.modified-span[data-db-value*="${room}"]`);
                const nextNumber = existingFolders.length + 1;

                document.getElementById('photoNameInput').value = `${room} ${nextNumber}`;
                fileInput.click();
            });
        });

        function prompt(message) {
            const input = document.createElement('input');
            input.type = 'text';
            input.placeholder = message;
            input.style.display = 'block';
            input.style.margin = '20px auto';
            input.style.padding = '10px';
            input.style.border = '1px solid #ccc';
            input.style.borderRadius = '5px';
            input.style.width = '80%';

            const overlay = document.createElement('div');
            overlay.style.position = 'fixed';
            overlay.style.top = '0';
            overlay.style.left = '0';
            overlay.style.width = '100%';
            overlay.style.height = '100%';
            overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
            overlay.style.display = 'flex';
            overlay.style.justifyContent = 'center';
            overlay.style.alignItems = 'center';
            overlay.appendChild(input);

            document.body.appendChild(overlay);
            input.focus();

            return new Promise((resolve) => {
                input.addEventListener('keypress', (event) => {
                    if (event.key === 'Enter') {
                        resolve(input.value);
                        document.body.removeChild(overlay);
                    }
                });

                overlay.addEventListener('click', (event) => {
                    if (event.target === overlay) {
                        resolve(null);
                        document.body.removeChild(overlay);
                    }
                });
            });
        }

        const greenPhotoButtons = document.querySelectorAll('.text-green-500.hover\\:text-green-700.add-photo-btn');
        greenPhotoButtons.forEach(button => {
            button.addEventListener('click', function() {
                const photoAddress = this.getAttribute('data-photo-address');
                if (photoAddress) {
                    const room = getLastFolderName(photoAddress);
                    document.getElementById('roomInput').value = room;
                    document.getElementById('photoAddressInput').value = photoAddress;
                    document.getElementById('fromGreenButton').value = "1";
                    document.getElementById('photoInputAdd').click();
                }
            });
        });

        document.querySelectorAll('.delete-photo-btn').forEach(button => {
            button.addEventListener('click', function() {
                const photoAddress = this.getAttribute('data-photo-address');
                if (confirm('Are you sure you want to delete this photo and its folder?')) {
                    const formData = new FormData();
                    formData.append('delete_photo', true);
                    formData.append('photo_address', photoAddress);

                    fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(result => {
                            window.location.reload();
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                }
            });
        });

        document.querySelectorAll('.quick-note-btn').forEach(button => {
            button.addEventListener('click', function() {
                const room = this.getAttribute('data-room');
                const dbValue = this.getAttribute('data-db-value');
                openNoteModal(room, dbValue);
            });
        });

        document.getElementById('photoInputAdd').onchange = e => {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = event => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');

                    const maxWidth = 3840;
                    const maxHeight = 2160;
                    let width = img.width;
                    let height = img.height;

                    if (width > height) {
                        if (width > maxWidth) {
                            height = Math.round((height *= maxWidth / width));
                            width = maxWidth;
                        }
                    } else {
                        if (height > maxHeight) {
                            width = Math.round((width *= maxHeight / height));
                            height = maxHeight;
                        }
                    }

                    canvas.width = width;
                    canvas.height = height;
                    ctx.drawImage(img, 0, 0, width, height);

                    canvas.toBlob(blob => {
                        const formData = new FormData();
                        formData.append('photo_add', blob, file.name);
                        formData.append('inspectionId', '<?php echo $inspectionId; ?>');
                        formData.append('room', document.getElementById('roomInput').value);
                        formData.append('photoName', document.getElementById('photoNameInput').value);

                        showUploadMessage('Uploading photo...', 'info');

                        fetch(window.location.href, {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.text())
                            .then(result => {
                                hideUploadMessage();
                                showUploadMessage('Photo uploaded successfully.', 'success');
                                setTimeout(hideUploadMessage, 3000);
                                window.location.reload();
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                hideUploadMessage();
                                showUploadMessage('Error uploading photo.', 'error');
                                setTimeout(hideUploadMessage, 3000);
                            });
                    }, 'image/jpeg', 0.99);
                };
                img.src = event.target.result;
            };
            reader.readAsDataURL(file);
        };

        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = 'image/*';
        fileInput.capture = 'camera';

        fileInput.onchange = e => {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = event => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');

                    const maxWidth = 3840;
                    const maxHeight = 2160;
                    let width = img.width;
                    let height = img.height;

                    if (width > height) {
                        if (width > maxWidth) {
                            height = Math.round((height *= maxWidth / width));
                            width = maxWidth;
                        }
                    } else {
                        if (height > maxHeight) {
                            width = Math.round((width *= maxHeight / height));
                            height = maxHeight;
                        }
                    }

                    canvas.width = width;
                    canvas.height = height;
                    ctx.drawImage(img, 0, 0, width, height);

                    canvas.toBlob(blob => {
                        const formData = new FormData();
                        formData.append('photo', blob, file.name);
                        formData.append('inspectionId', '<?php echo $inspectionId; ?>');
                        formData.append('room', document.getElementById('roomInput').value);
                        formData.append('photoName', document.getElementById('selectedLevelInput').value + '_' + document.getElementById('photoNameInput').value);

                        showUploadMessage('Uploading photo...', 'info');

                        fetch(window.location.href, {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.text())
                            .then(result => {
                                hideUploadMessage();
                                showUploadMessage('Photo uploaded successfully.', 'success');
                                setTimeout(hideUploadMessage, 3000);
                                window.location.reload();
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                hideUploadMessage();
                                showUploadMessage('Error uploading photo.', 'error');
                                setTimeout(hideUploadMessage, 3000);
                            });
                    }, 'image/jpeg', 0.99);
                };
                img.src = event.target.result;
            };
            reader.readAsDataURL(file);
        };

        function showUploadMessage(message, type = 'info') {
            const messageBox = document.getElementById('messageBox');
            messageBox.textContent = message;
            messageBox.className = `message ${type}`;
            messageBox.style.display = 'block';
        }

        function hideUploadMessage() {
            const messageBox = document.getElementById('messageBox');
            messageBox.style.display = 'none';
        }

        function getLastFolderName(path) {
            const pathParts = path.split('/');
            return pathParts[pathParts.length - 2];
        }

        let photoList = [];
        let currentPhotoIndex = 0;

        function showGalleryModal(folderName) {
            const galleryModal = document.querySelector('.gallery-modal-overlay');
            const galleryContent = document.getElementById('galleryContent');
            galleryContent.innerHTML = '';

            fetch(`<?php echo $_SERVER['PHP_SELF']; ?>?id=<?php echo $inspectionId; ?>&get_photos=true&folder_name=${folderName}`)
                .then(response => response.json())
                .then(photos => {
                    photoList = photos;
                    photos.forEach((photo, index) => {
                        const photoContainer = document.createElement('div');
                        photoContainer.classList.add('gallery-photo');

                        const imgElement = document.createElement('img');
                        imgElement.src = photo;
                        imgElement.classList.add('thumbnail');
                        imgElement.onclick = () => {
                            currentPhotoIndex = index;
                            showLargeImage(photo);
                        };

                        const deleteButton = document.createElement('button');
                        deleteButton.innerHTML = '<svg class="h-5 w-5 text-red-500" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">  <path stroke="none" d="M0 0h24V0z"/><line x1="4" y1="7" x2="20" y2="7" /><line x1="10" y1="11" x2="10" y2="17" /><line x1="14" y1="11" x2="14" y2="17" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>';
                        deleteButton.classList.add('delete-icon');
                        deleteButton.onclick = (e) => {
                            e.stopPropagation();
                            deletePhoto(photo);
                        };

                        photoContainer.appendChild(imgElement);
                        photoContainer.appendChild(deleteButton);
                        galleryContent.appendChild(photoContainer);
                    });
                })
                .catch(error => console.error('Error fetching photos:', error));

            galleryModal.classList.remove('hidden');
        }

        function hideGalleryModal() {
            const galleryModal = document.querySelector('.gallery-modal-overlay');
            galleryModal.classList.add('hidden');
        }

        function showLargeImage(src) {
            const overlay = document.createElement('div');
            overlay.classList.add('fixed', 'inset-0', 'bg-black', 'bg-opacity-75', 'flex', 'items-center', 'justify-center');
            overlay.id = 'largeImageOverlay';

            const imgContainer = document.createElement('div');
            imgContainer.classList.add('relative');

            const imgElement = document.createElement('img');
            imgElement.src = src;
            imgElement.classList.add('large-image');

            const deleteButton = document.createElement('button');
            deleteButton.classList.add('delete-icon', 'absolute', 'top-2', 'right-2', 'bg-red-500', 'text-white', 'rounded-full', 'p-1');
            deleteButton.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" />
            <line x1="4" y1="7" x2="20" y2="7" />
            <line x1="10" y1="11" x2="10" y2="17" />
            <line x1="14" y1="11" x2="14" y2="17" />
            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
            <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
        </svg>
    `;
            deleteButton.onclick = (e) => {
                e.stopPropagation();
                deletePhoto(src);
            };

            const nextButton = document.createElement('button');
            nextButton.classList.add('absolute', 'top-1/2', 'right-2', 'bg-gray-500', 'text-white', 'rounded-full', 'p-4');
            nextButton.innerHTML = '>';
            nextButton.onclick = (e) => {
                e.stopPropagation();
                showNextImage();
            };

            const prevButton = document.createElement('button');
            prevButton.classList.add('absolute', 'top-1/2', 'left-2', 'bg-gray-500', 'text-white', 'rounded-full', 'p-4');
            prevButton.innerHTML = '<';
            prevButton.onclick = (e) => {
                e.stopPropagation();
                showPrevImage();
            };

            imgContainer.appendChild(imgElement);
            imgContainer.appendChild(deleteButton);
            imgContainer.appendChild(nextButton);
            imgContainer.appendChild(prevButton);
            overlay.appendChild(imgContainer);

            overlay.onclick = () => {
                document.body.removeChild(overlay);
            };

            document.body.appendChild(overlay);
        }

        function showNextImage() {
            if (photoList.length === 0) return;
            currentPhotoIndex = (currentPhotoIndex + 1) % photoList.length;
            const nextPhotoSrc = photoList[currentPhotoIndex];
            document.querySelector('.large-image').src = nextPhotoSrc;
        }

        function showPrevImage() {
            if (photoList.length === 0) return;
            currentPhotoIndex = (currentPhotoIndex - 1 + photoList.length) % photoList.length;
            const prevPhotoSrc = photoList[currentPhotoIndex];
            document.querySelector('.large-image').src = prevPhotoSrc;
        }

        function deletePhoto(src) {
            if (confirm('Are you sure you want to delete this photo?')) {
                const formData = new FormData();
                formData.append('delete_photo', true);
                formData.append('photo_address', src);
                formData.append('fromModal', true);

                fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(result => {
                        const photoIndex = photoList.indexOf(src);
                        if (photoIndex > -1) {
                            photoList.splice(photoIndex, 1);

                            const thumbnailToRemove = document.querySelector(`.gallery-photo img[src="${src}"]`).parentNode;
                            if (thumbnailToRemove) {
                                thumbnailToRemove.parentNode.removeChild(thumbnailToRemove);
                            }

                            if (document.getElementById('largeImageOverlay')) {
                                if (photoList.length > 0) {
                                    currentPhotoIndex = (photoIndex < photoList.length) ? photoIndex : photoIndex - 1;
                                    const nextPhotoSrc = photoList[currentPhotoIndex];
                                    document.querySelector('.large-image').src = nextPhotoSrc;
                                } else {
                                    document.body.removeChild(document.getElementById('largeImageOverlay'));
                                }
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }
        }

        document.getElementById('closeGalleryModal').onclick = hideGalleryModal;

        document.querySelector('.gallery-modal-overlay').addEventListener('click', function(event) {
            if (event.target.classList.contains('gallery-modal-overlay')) {
                hideGalleryModal();
            }
        });

        function filterPhotosByLevel() {
            const level = document.getElementById('level').value;
            window.location.href = "<?php echo $_SERVER['PHP_SELF']; ?>?id=<?php echo $inspectionId; ?>&level=" + encodeURIComponent(level);
        }
    </script>
</body>

</html>