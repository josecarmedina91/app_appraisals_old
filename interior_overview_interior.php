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

$query = "SELECT interior_finish FROM tb_interior_inspecciones WHERE id_inspeccion = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$inspectionId]);
$selectedOptions = $stmt->fetchColumn();

$selectedOptionsArray = array_map('trim', explode(',', $selectedOptions));

// Opciones estáticas
$staticOptions = ['"Drywall-Walls"', '"Drywall-Ceilings"', '"Plaster-Walls"', '"Plaster-Ceilings"', '"Panelling-Walls"', '"Panelling-Ceilings"'];

// Opciones adicionales (otros)
$additionalOptions = [];
foreach ($selectedOptionsArray as $option) {
    if (!in_array($option, $staticOptions) && preg_match('/"(.+?)-(Walls|Ceilings)"/', $option, $matches)) {
        $additionalOptions[$matches[1]][] = $matches[2];
    }
}

$titulo = "Interior finish";
$moduls = 22;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_checkbox'])) {
    $selectedOptions = $_POST['selectedOptions'];
    try {
        $stmt = $pdo->prepare("UPDATE tb_interior_inspecciones SET interior_finish = ? WHERE id_inspeccion = ?");
        $stmt->execute([$selectedOptions, $inspectionId]);
        echo "Datos guardados con éxito.";
    } catch (PDOException $e) {
        die("ERROR: No se pudo actualizar. " . $e->getMessage());
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    handlePostRequest($pdo, $inspectionId);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['options'])) {
    $selectedOptions = $_POST['options'];
    $otherText = trim($_POST['otherText'] ?? '');

    if (!empty($otherText)) {
        if (isset($_POST['otherWallsCheckbox'])) {
            $selectedOptions[] = '"' . $otherText . '-Walls"';
        }
        if (isset($_POST['otherCeilingsCheckbox'])) {
            $selectedOptions[] = '"' . $otherText . '-Ceilings"';
        }
    }

    $selectedOptions = implode(', ', $selectedOptions);

    try {
        $stmt = $pdo->prepare("UPDATE tb_interior_inspecciones SET interior_finish = ? WHERE id_inspeccion = ?");
        $stmt->execute([$selectedOptions, $inspectionId]);
        echo "Datos guardados con éxito.";
    } catch (PDOException $e) {
        die("ERROR: No se pudo actualizar. " . $e->getMessage());
    }
}

function handlePostRequest($pdo, $inspectionId)
{
    if (isset($_FILES['photo'])) {
        handlePhotoUpload($inspectionId);
    } elseif (isset($_POST['activeButtons'])) {
        updateActiveButtons($pdo, $inspectionId);
    } elseif (isset($_POST['add_note'])) {
        addNoteToInspection($pdo, $inspectionId);
    }
}

function handlePhotoUpload($inspectionId)
{
    $folderName = "img/photo_gallery/{$inspectionId}/Interior_finish";
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

function updateActiveButtons($pdo, $inspectionId)
{
    $activeButtons = $_POST['activeButtons'];
    try {
        $stmt = $pdo->prepare("UPDATE tb_interior_inspecciones SET interior_finish = ? WHERE id_inspeccion = ?");
        $stmt->execute([$activeButtons, $inspectionId]);
        echo "Botones actualizados: " . $activeButtons;
        exit;
    } catch (PDOException $e) {
        die("ERROR: No se pudo actualizar. " . $e->getMessage());
    }
}

function addNoteToInspection($pdo, $inspectionId)
{
    $note = ucfirst(trim($_POST['note']));
    $note .= substr($note, -1) !== '.' ? '.' : '';
    $stmt = $pdo->prepare("SELECT notas_int_interior_finish FROM tb_interior_inspecciones WHERE id_inspeccion = ?");
    $stmt->execute([$inspectionId]);
    $existingNotes = trim($stmt->fetchColumn());
    $updatedNotes = empty($existingNotes) ? $note : $existingNotes . "\n\n" . $note;
    $stmt = $pdo->prepare("UPDATE tb_interior_inspecciones SET notas_int_interior_finish = ? WHERE id_inspeccion = ?");
    $stmt->execute([$updatedNotes, $inspectionId]);
    $_SESSION['note_saved'] = true;
    header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $inspectionId . "&note_saved=true");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interior finish</title>
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

        .checkbox-large {
            width: 20px;
            height: 20px;
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
            <h1 id="super_titulo" class="text-xl font-semibold text-center">Interior finish</h1>
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
        <div class="border-b pb-3">
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
        </div>

        <div class="w-full max-w-3xl mx-auto p-6">
            <form id="optionsForm" method="POST">
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-1"></div>
                    <div class="col-span-1 text-center font-semibold text-gray-600">Walls</div>
                    <div class="col-span-1 text-center font-semibold text-gray-600">Ceilings</div>

                    <div class="col-span-1 text-gray-700 mt-4 border-b pb-3">Drywall</div>
                    <div class="col-span-1 text-center mt-4 border-b pb-3">
                        <input type="checkbox" class="form-checkbox h-5 w-5 text-blue-600 checkbox-large" name="options[]" value='"Drywall-Walls"' <?php echo in_array('"Drywall-Walls"', $selectedOptionsArray) ? 'checked' : ''; ?> onclick="updateCheckbox(this)">
                    </div>
                    <div class="col-span-1 text-center mt-4 border-b pb-3">
                        <input type="checkbox" class="form-checkbox h-5 w-5 text-blue-600 checkbox-large" name="options[]" value='"Drywall-Ceilings"' <?php echo in_array('"Drywall-Ceilings"', $selectedOptionsArray) ? 'checked' : ''; ?> onclick="updateCheckbox(this)">
                    </div>

                    <div class="col-span-1 text-gray-700 mt-4 border-b pb-3">Plaster</div>
                    <div class="col-span-1 text-center mt-4 border-b pb-3">
                        <input type="checkbox" class="form-checkbox h-5 w-5 text-blue-600 checkbox-large" name="options[]" value='"Plaster-Walls"' <?php echo in_array('"Plaster-Walls"', $selectedOptionsArray) ? 'checked' : ''; ?> onclick="updateCheckbox(this)">
                    </div>
                    <div class="col-span-1 text-center mt-4 border-b pb-3">
                        <input type="checkbox" class="form-checkbox h-5 w-5 text-blue-600 checkbox-large" name="options[]" value='"Plaster-Ceilings"' <?php echo in_array('"Plaster-Ceilings"', $selectedOptionsArray) ? 'checked' : ''; ?> onclick="updateCheckbox(this)">
                    </div>

                    <div class="col-span-1 text-gray-700 mt-4 border-b pb-3">Panelling</div>
                    <div class="col-span-1 text-center mt-4 border-b pb-3">
                        <input type="checkbox" class="form-checkbox h-5 w-5 text-blue-600 checkbox-large" name="options[]" value='"Panelling-Walls"' <?php echo in_array('"Panelling-Walls"', $selectedOptionsArray) ? 'checked' : ''; ?> onclick="updateCheckbox(this)">
                    </div>
                    <div class="col-span-1 text-center mt-4 border-b pb-3">
                        <input type="checkbox" class="form-checkbox h-5 w-5 text-blue-600 checkbox-large" name="options[]" value='"Panelling-Ceilings"' <?php echo in_array('"Panelling-Ceilings"', $selectedOptionsArray) ? 'checked' : ''; ?> onclick="updateCheckbox(this)">
                    </div>

                    <?php
                    foreach ($additionalOptions as $optionName => $optionTypes) {
                        echo '<div class="col-span-1 text-gray-700 mt-4 border-b pb-3">' . htmlspecialchars($optionName) . '</div>';
                        echo '<div class="col-span-1 text-center mt-4 border-b pb-3"><input type="checkbox" class="form-checkbox h-5 w-5 text-blue-600 checkbox-large" name="options[]" value="' . htmlspecialchars('"' . $optionName . '-Walls"') . '" ' . (in_array('"' . $optionName . '-Walls"', $selectedOptionsArray) ? 'checked' : '') . ' onclick="updateCheckbox(this)"></div>';
                        echo '<div class="col-span-1 text-center mt-4 border-b pb-3"><input type="checkbox" class="form-checkbox h-5 w-5 text-blue-600 checkbox-large" name="options[]" value="' . htmlspecialchars('"' . $optionName . '-Ceilings"') . '" ' . (in_array('"' . $optionName . '-Ceilings"', $selectedOptionsArray) ? 'checked' : '') . ' onclick="updateCheckbox(this)"></div>';
                    }
                    ?>

                    <div class="col-span-3 mt-4">
                        <input type="text" placeholder="Other" id="otherInput" class="form-input w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                <input type="hidden" name="inspectionId" value="<?php echo $inspectionId; ?>">
                <input type="hidden" id="selectedOptions" name="selectedOptions" value="<?php echo htmlspecialchars($selectedOptions); ?>">
                <input type="hidden" id="otherText" name="otherText" value="">
            </form>
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
        let otherText = '';

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
            saveBtn.disabled = !noteInput.value.trim().length;
        }

        var changesMade = false;

        function toggleButton(button) {
            const buttonText = button.textContent.trim();
            const buttons = document.querySelectorAll('button[onclick="toggleButton(this)"]');
            let isActive = button.classList.contains('bg-green-300');

            buttons.forEach(btn => {
                if (btn.textContent.trim() === buttonText) {
                    btn.classList.toggle('bg-green-300', !isActive);
                    btn.classList.toggle('bg-background', isActive);
                    btn.classList.toggle('hover:bg-accent', isActive);
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
                .then(() => {
                    window.location.href = 'interior_overview_menu.php?id=<?php echo $inspectionId; ?>';
                })
                .catch(error => console.error('Error:', error));
        }

        document.getElementById('otherInput').addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                const otherInputValue = this.value.trim();
                if (otherInputValue) {
                    createOtherOption(otherInputValue);
                    this.value = '';
                    document.getElementById('otherText').value = otherInputValue;
                }
            }
        });

        function capitalizeFirstLetter(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }

        function createOtherOption(value) {
            value = capitalizeFirstLetter(value);
            const container = document.createElement('div');
            container.className = 'col-span-3 grid grid-cols-3 gap-4 mt-4 border-b pb-3';

            const labelDiv = document.createElement('div');
            labelDiv.className = 'col-span-1 text-gray-700';
            labelDiv.textContent = value;

            const wallsCheckboxDiv = document.createElement('div');
            wallsCheckboxDiv.className = 'col-span-1 text-center';
            const wallsCheckbox = document.createElement('input');
            wallsCheckbox.type = 'checkbox';
            wallsCheckbox.className = 'form-checkbox h-5 w-5 text-blue-600 checkbox-large';
            wallsCheckbox.value = `"${value}-Walls"`;
            wallsCheckbox.onclick = () => updateCheckbox(wallsCheckbox);
            wallsCheckboxDiv.appendChild(wallsCheckbox);

            const ceilingsCheckboxDiv = document.createElement('div');
            ceilingsCheckboxDiv.className = 'col-span-1 text-center';
            const ceilingsCheckbox = document.createElement('input');
            ceilingsCheckbox.type = 'checkbox';
            ceilingsCheckbox.className = 'form-checkbox h-5 w-5 text-blue-600 checkbox-large';
            ceilingsCheckbox.value = `"${value}-Ceilings"`;
            ceilingsCheckbox.onclick = () => updateCheckbox(ceilingsCheckbox);
            ceilingsCheckboxDiv.appendChild(ceilingsCheckbox);

            container.appendChild(labelDiv);
            container.appendChild(wallsCheckboxDiv);
            container.appendChild(ceilingsCheckboxDiv);

            const panellingSection = document.querySelectorAll('.text-gray-700.mt-4.border-b.pb-3');
            let insertAfterElement;
            panellingSection.forEach(element => {
                if (element.textContent.trim() === 'Panelling') {
                    insertAfterElement = element.nextElementSibling.nextElementSibling;
                }
            });
            insertAfterElement.parentNode.insertBefore(container, insertAfterElement.nextSibling);
        }

        function saveChanges() {
            const buttons = document.querySelectorAll('button[onclick="toggleButton(this)"]');
            let activeButtons = Array.from(buttons).filter(button => button.classList.contains('bg-green-300')).map(button => '"' + button.textContent.trim() + '"');

            activeButtons = [...new Set(activeButtons)];

            const formData = new FormData();
            formData.append('activeButtons', activeButtons.join(', '));
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
                            .then(() => {
                                showUploadMessage('Photo uploaded successfully!', 'success');
                                setTimeout(hideUploadMessage, 700);
                            })
                            .catch(() => {
                                showUploadMessage('Error uploading photo', 'error');
                                setTimeout(hideUploadMessage, 200);
                            });
                    }, 'image/jpeg', 0.99);
                };
                img.src = event.target.result;
            };
            reader.readAsDataURL(file);
        };

        function updateCheckbox(checkbox) {
            const selectedOptions = document.getElementById('selectedOptions').value;
            const value = checkbox.value;
            let selectedOptionsArray = selectedOptions.split(', ').filter(item => item);

            if (checkbox.checked) {
                if (!selectedOptionsArray.includes(value)) {
                    selectedOptionsArray.push(value);
                }
            } else {
                selectedOptionsArray = selectedOptionsArray.filter(item => item !== value);
            }

            const newSelectedOptions = selectedOptionsArray.join(', ');
            document.getElementById('selectedOptions').value = newSelectedOptions;

            const formData = new FormData();
            formData.append('selectedOptions', newSelectedOptions);
            formData.append('update_checkbox', true);

            fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    console.log(data);
                })
                .catch(error => console.error('Error:', error));
        }

        function updateOtherCheckbox(checkbox) {
            const otherInputValue = document.getElementById('otherInput').value.trim();
            if (!otherInputValue) return;

            const selectedOptions = document.getElementById('selectedOptions').value;
            let selectedOptionsArray = selectedOptions.split(', ').filter(item => item);

            const checkboxValue = checkbox.id === 'otherWallsCheckbox' ? `"${otherInputValue}-Walls"` : `"${otherInputValue}-Ceilings"`;

            if (checkbox.checked) {
                if (!selectedOptionsArray.includes(checkboxValue)) {
                    selectedOptionsArray.push(checkboxValue);
                }
            } else {
                selectedOptionsArray = selectedOptionsArray.filter(item => item !== checkboxValue);
            }

            const newSelectedOptions = selectedOptionsArray.join(', ');
            document.getElementById('selectedOptions').value = newSelectedOptions;

            const formData = new FormData();
            formData.append('selectedOptions', newSelectedOptions);
            formData.append('update_checkbox', true);

            fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    console.log(data);
                })
                .catch(error => console.error('Error:', error));
        }

        function showUploadMessage(message, type = 'info') {
            const messageBox = document.getElementById('messageBox');
            messageBox.textContent = message;
            messageBox.className = `message ${type}`;
            messageBox.style.display = 'block';
        }

        function hideUploadMessage() {
            document.getElementById('messageBox').style.display = 'none';
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
