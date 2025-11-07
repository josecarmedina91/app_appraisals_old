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
    $nombre_usuario = $row['nombre_completo'];
    $correo_usuario = $row['correo_electronico'];
} else {
    die('Usuario no encontrado.');
}

if (isset($_GET['id'])) {
    $inspectionId = $_GET['id'];
} else {
    echo "Error: ID de inspección no proporcionado.";
    exit;
}

$buttonState = [];
$stmt = $pdo->prepare("SELECT heating_system FROM tb_interior_inspecciones WHERE id_inspeccion = ?");
$stmt->execute([$inspectionId]);
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $buttonState = array_unique(array_filter(explode(', ', $row['heating_system'])));
}

$typeButtons = [];
$fuelButtons = [];

foreach ($buttonState as $option) {
    $option = trim($option);
    if (strpos($option, 'Type-') === 1) {
        $typeButtons[] = str_replace(['Type-', '"'], '', $option);
    } elseif (strpos($option, 'Fuel-') === 1) {
        $fuelButtons[] = str_replace(['Fuel-', '"'], '', $option);
    } else {
    }
}

$buttonOptions = [];

foreach (array_merge($typeButtons, $fuelButtons) as $option) {
    if (!in_array($option, $buttonOptions)) {
        $buttonOptions[] = $option;
    }
}

$titulo = "Heating system";
$moduls = 31;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['photo'])) {
        $inspectionId = $_POST['inspectionId'];
        $folderName = "img/photo_gallery/{$inspectionId}/Heating_system";
        if (!file_exists($folderName)) {
            mkdir($folderName, 0777, true);
        }
        $filePath = $folderName . "/" . uniqid() . ".jpg";
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $filePath)) {
            echo "Foto guardada con éxito.";
        } else {
            echo "Error al guardar la foto.";
        }
        exit;
    }

    if (isset($_POST['activeButtons'])) {
        $activeButtons = $_POST['activeButtons'];
        $inspectionId = $_POST['id'];

        $activeButtons = implode(', ', array_map(function ($item) {
            return '"' . trim($item, '"') . '"';
        }, explode(', ', $activeButtons)));

        try {
            $stmt = $pdo->prepare("UPDATE tb_interior_inspecciones SET heating_system = ? WHERE id_inspeccion = ?");
            $stmt->execute([$activeButtons, $inspectionId]);
            echo "Botones actualizados: " . $activeButtons;
            exit;
        } catch (PDOException $e) {
            die("ERROR: No se pudo actualizar. " . $e->getMessage());
        }
    }

    if (isset($_POST['add_note'])) {
        $note = $_POST['note'];
        if (!empty($note)) {
            $stmt = $pdo->prepare("SELECT notas_int_heating_system FROM tb_interior_inspecciones WHERE id_inspeccion = ?");
            $stmt->execute([$inspectionId]);
            $existingNotes = $stmt->fetchColumn();

            $note = ucfirst(trim($note));
            if (substr($note, -1) !== '.') {
                $note .= '.';
            }

            $existingNotes = trim($existingNotes);
            $updatedNotes = empty($existingNotes) ? $note : $existingNotes . "\n\n" . $note;

            $stmt = $pdo->prepare("UPDATE tb_interior_inspecciones SET notas_int_heating_system = ? WHERE id_inspeccion = ?");
            $stmt->execute([$updatedNotes, $inspectionId]);
            $_SESSION['note_saved'] = true;

            header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $inspectionId . "&note_saved=true");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heating system</title>
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
    </style>
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
            <h1 id="super_titulo" class="text-xl font-semibold text-center">Heating system</h1>
            <div style="width:24px"></div>
        </div>
        <div class="grid grid-cols-3 mt-4 space-x-2">
            <button onclick="window.location.href='component/note.php?id=<?php echo $inspectionId; ?>&moduls=<?php echo $moduls; ?>'" class="flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-blue-500 hover:bg-blue-600 text-white h-10 px-4 py-2 flex-1">
                <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" />
                    <line x1="13" y1="20" x2="20" y2="13" />
                    <path d="M13 20v-6a1 1 0 0 1 1 -1h6v-7a2 2 0 0 0 -2 -2h-12a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7" />
                </svg>
                <span class="ml-2">All Notes</span>
            </button>

            <button onclick="redirectToGallery()" class="flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-green-500 hover:bg-green-600 text-white h-10 px-4 py-2 flex-1">
                <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
                    <circle cx="8.5" cy="8.5" r="1.5" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.4 14.5L16 10 4 20h16v-2.1z" />
                </svg>
                <span class="ml-2">Gallery</span>
            </button>

            <button onclick="window.location.href='overview_damage.php?id=<?php echo $inspectionId; ?>'" class="flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-red-500 hover:bg-red-600 text-white h-10 px-4 py-2 flex-1">

                <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" />
                    <rect x="4" y="4" width="16" height="16" rx="2" />
                    <path d="M8 11v-3h8v3m-2 4v.01M12 16v.01" />
                </svg>
                <span class="ml-2">Det. Factors</span>
            </button>
        </div>

        <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-secondary text-secondary-foreground hover:bg-secondary/80 h-10 px-4 py-2">
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
            <input type="hidden" name="inspectionId" value="<?php echo $inspectionId; ?>">
            <input type="submit" value="Upload Photo">
        </form>

        <div class="grid grid-cols-2 gap-4 mt-4">
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

            <div class="col-span-1">
                <h3 class="text-lg font-semibold text-center p-1">Type:</h3>
                <div class="flex flex-col items-center">
                    <?php
                    $numberOfPanes = ['Forced Air Furnace', 'Radiant', 'Baseboard', 'Stove'];
                    foreach ($numberOfPanes as $option) {
                        $activeClass = in_array($option, $typeButtons) ? 'bg-green-300' : 'bg-background';
                        echo '<button onclick="toggleButton(this)" class="' . $activeClass . ' w-full inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2 mb-2">';
                        echo htmlspecialchars($option);
                        echo '</button>';
                    }
                    foreach ($typeButtons as $option) {
                        if (!in_array($option, $numberOfPanes)) {
                            $activeClass = 'bg-green-300';
                            echo '<button onclick="toggleButton(this)" class="' . $activeClass . ' w-full inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2 mb-2">';
                            echo htmlspecialchars($option);
                            echo '</button>';
                        }
                    }
                    ?>
                    <input type="text" id="typeOtherInput" placeholder="Others" class="w-full inline-flex text-center items-center justify-center whitespace-nowrap rounded-md text-base font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input h-10 px-4 py-2">
                </div>
            </div>

            <div class="col-span-1">
                <h3 class="text-lg font-semibold text-center p-1">Fuel Type:</h3>
                <div class="flex flex-col items-center">
                    <?php
                    $frameTypes = ['Natural Gas', 'Propane', 'Electricity', 'Oil'];
                    foreach ($frameTypes as $option) {
                        $activeClass = in_array($option, $fuelButtons) ? 'bg-green-300' : 'bg-background';
                        echo '<button onclick="toggleButton(this)" class="' . $activeClass . ' w-full inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2 mb-2">';
                        echo htmlspecialchars($option);
                        echo '</button>';
                    }
                    foreach ($fuelButtons as $option) {
                        if (!in_array($option, $frameTypes)) {
                            $activeClass = 'bg-green-300';
                            echo '<button onclick="toggleButton(this)" class="' . $activeClass . ' w-full inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2 mb-2">';
                            echo htmlspecialchars($option);
                            echo '</button>';
                        }
                    }
                    ?>
                    <input type="text" id="otherInput" placeholder="Others" class="w-full inline-flex text-center items-center justify-center whitespace-nowrap rounded-md text-base font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input h-10 px-4 py-2">
                </div>
            </div>
        </div>
    </div>
    <div id="noteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center">
        <div class="bg-white p-4 rounded-lg shadow-lg">
            <h2 class="font-semibold text-lg mb-2">Add a Note</h2>
            <form action="" method="POST">
                <textarea id="noteInput" name="note" rows="4" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-accent" placeholder="Enter your note here..." autofocus></textarea>
                <div class="flex justify-end space-x-2 mt-4">
                    <button type="button" onclick="closeNoteModal()" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Cancel
                    </button>
                    <button type="submit" name="add_note" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="messageBox" class="hidden fixed inset-0 flex items-center justify-center px-4 py-2 border border-gray-300 shadow-lg z-50 font-medium text-lg bg-white text-black rounded-md" style="width: 60%; max-width: 600px; min-width: 300px;"></div>

    <script>
        const addNoteBtn = document.getElementById('addNoteBtn');
        const noteModal = document.getElementById('noteModal');
        const noteInput = document.getElementById('noteInput');
        const saveBtn = noteModal.querySelector('button[type="submit"]');
        const cancelBtn = noteModal.querySelector('button[type="button"]');

        function openNoteModal() {
            noteModal.classList.remove('hidden');
            noteInput.focus();
            checkInput();
        }

        function closeNoteModal() {
            noteModal.classList.add('hidden');
            window.location.href = window.location.pathname + '?id=<?php echo $inspectionId; ?>';
        }

        addNoteBtn.addEventListener('click', openNoteModal);
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

        var changesMade = false;

        function toggleButton(button) {
            const buttonText = button.textContent.trim();
            const buttons = document.querySelectorAll('button[onclick="toggleButton(this)"]');
            let isActive = button.classList.contains('bg-green-300');

            buttons.forEach(btn => {
                if (btn.textContent.trim() === buttonText) {
                    isActive ? btn.classList.remove('bg-green-300') : btn.classList.add('bg-green-300');
                    btn.classList.toggle('bg-background', !isActive);
                    btn.classList.toggle('hover:bg-accent', !isActive);
                }
            });

            changesMade = true;
            saveChanges();
        }

        function saveAndGoBack() {
            if (!changesMade) {
                window.location.href = 'interior_overview_menu.php?id=<?php echo $inspectionId; ?>';
                return;
            }
            const buttons = document.querySelectorAll('button[onclick="toggleButton(this)"]');
            let activeButtons = [];
            buttons.forEach(button => {
                if (button.classList.contains('bg-green-300')) {
                    activeButtons.push('"' + button.textContent.trim() + '"');
                }
            });

            const otherInputValue = document.getElementById('otherInput').value.trim();
            if (otherInputValue) {
                activeButtons.push('"' + capitalizeFirstLetter(otherInputValue) + '"');
            }

            const formData = new FormData();
            formData.append('activeButtons', activeButtons.join(', '));
            formData.append('id', '<?php echo $inspectionId; ?>');

            fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    window.location.href = 'interior_overview_menu.php?id=<?php echo $inspectionId; ?>';
                })
                .catch(error => console.error('Error:', error));
        }

        document.getElementById('otherInput').addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                const otherInputValue = this.value.trim();
                if (otherInputValue) {
                    addOtherOption(otherInputValue, 'otherInput', 'Fuel-');
                    this.value = '';
                }
            }
        });

        document.getElementById('typeOtherInput').addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                const otherInputValue = this.value.trim();
                if (otherInputValue) {
                    addOtherOption(otherInputValue, 'typeOtherInput', 'Type-');
                    this.value = '';
                }
            }
        });

        function capitalizeFirstLetter(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }

        function addOtherOption(value, inputId, prefix) {
            value = capitalizeFirstLetter(value);
            const buttons = document.querySelectorAll('button[onclick="toggleButton(this)"]');
            let exists = false;
            buttons.forEach(button => {
                if (button.textContent.trim() === value) {
                    exists = true;
                }
            });

            if (!exists) {
                const newButton = document.createElement('button');
                newButton.className = 'bg-green-300 w-full inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2 mb-2';
                newButton.textContent = value;
                newButton.setAttribute('onclick', 'toggleButton(this)');

                const otherInput = document.getElementById(inputId);
                otherInput.parentNode.insertBefore(newButton, otherInput);

                saveChanges(prefix + value);
            }
        }

        function saveChanges(customButton) {
            const buttons = document.querySelectorAll('button[onclick="toggleButton(this)"]');
            let typeButtons = [];
            let fuelButtons = [];
            buttons.forEach(button => {
                if (button.classList.contains('bg-green-300')) {
                    const buttonText = button.textContent.trim();
                    if (!typeButtons.includes(buttonText) && !fuelButtons.includes(buttonText)) {
                        if (button.parentElement.previousElementSibling.textContent.trim() === 'Type:') {
                            typeButtons.push('Type-' + buttonText);
                        } else if (button.parentElement.previousElementSibling.textContent.trim() === 'Fuel Type:') {
                            fuelButtons.push('Fuel-' + buttonText);
                        }
                    }
                }
            });

            if (customButton) {
                const customButtonPrefix = customButton.startsWith('Type-') ? 'Type-' : 'Fuel-';
                typeButtons = typeButtons.filter(button => !button.includes(customButton.replace(customButtonPrefix, '')));
                fuelButtons = fuelButtons.filter(button => !button.includes(customButton.replace(customButtonPrefix, '')));
                if (customButtonPrefix === 'Type-') {
                    typeButtons.push(customButton);
                } else {
                    fuelButtons.push(customButton);
                }
            }

            const allButtons = [...new Set([...typeButtons, ...fuelButtons])].map(button => '"' + button + '"');
            const formData = new FormData();
            formData.append('activeButtons', allButtons.join(', '));
            formData.append('id', '<?php echo $inspectionId; ?>');

            fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {})
                .catch(error => console.error('Error:', error));
        }

        function redirectToGallery() {
            var inspectionId = '<?php echo $inspectionId; ?>';
            var titulo = document.getElementById('super_titulo').textContent.trim();
            var url = 'component/gallery.php?id=' + encodeURIComponent(inspectionId) + '&titulo=' + encodeURIComponent(titulo);
            window.location.href = url;
        }

        document.getElementById('takePhotoButton').addEventListener('click', function() {
            fileInput.click();
        });

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

                        showUploadMessage('Uploading photo');

                        fetch(window.location.href, {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.text())
                            .then(result => {
                                showUploadMessage('Photo uploaded successfully!', 'success');
                                setTimeout(() => {
                                    hideUploadMessage();
                                }, 700);
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                showUploadMessage('Error uploading photo', 'error');
                                setTimeout(() => {
                                    hideUploadMessage();
                                }, 200);
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

        const otherInput = document.getElementById('otherInput');
        otherInput.addEventListener('blur', function() {
            window.scrollTo(0, 0);
        });
        document.addEventListener('gesturestart', function(e) {
            e.preventDefault();
        });
        document.addEventListener('dblclick', function(e) {
            e.preventDefault();
        });
    </script>
</body>

</html>