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
$stmt = $pdo->prepare("SELECT site_improvements FROM tb_site_inspecciones WHERE id_inspeccion = ?");
$stmt->execute([$inspectionId]);
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $buttonState = array_filter(explode(', ', $row['site_improvements']));
}

$buttonOptions = ['Deck', 'Patio', 'Gazebo', 'Balcony', 'Fence', 'Porch', 'Veranda', 'Pool hse', 'Workshop', 'Shed', 'Boat hse', 'Barn'];

foreach ($buttonState as $option) {
    $option = trim($option, '"');
    if (!in_array($option, $buttonOptions)) {
        $buttonOptions[] = $option;
    }
}

$titulo = "Site improvements";
$moduls = 18;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['photo'])) {
        $inspectionId = $_POST['inspectionId'];
        $buttonName = $_POST['buttonName'];
        $folderName = "img/photo_gallery/{$inspectionId}/Site_improvements";

        if (!file_exists($folderName)) {
            mkdir($folderName, 0777, true);
        }

        $fileIndex = 1;
        $filePath = "{$folderName}/{$buttonName}.jpg";
        while (file_exists($filePath)) {
            $fileIndex++;
            $filePath = "{$folderName}/{$buttonName}_{$fileIndex}.jpg";
        }

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

        try {
            $stmt = $pdo->prepare("UPDATE tb_site_inspecciones SET site_improvements = ? WHERE id_inspeccion = ?");
            $stmt->execute([$activeButtons, $inspectionId]);
            echo "Botones actualizados: " . $activeButtons;
            exit;
        } catch (PDOException $e) {
            die("ERROR: No se pudo actualizar. " . $e->getMessage());
        }
    }

    if (isset($_POST['add_note'])) {
        $note = $_POST['note'];
        $buttonName = $_POST['buttonName'];
        if (!empty($note)) {
            $stmt = $pdo->prepare("SELECT notas_site_site_improvements FROM tb_site_inspecciones WHERE id_inspeccion = ?");
            $stmt->execute([$inspectionId]);
            $existingNotes = $stmt->fetchColumn();

            $note = ucfirst(trim($note));
            if (substr($note, -1) !== '.') {
                $note .= '.';
            }

            $note = "$buttonName: $note"; // Aquí añadimos el nombre del botón

            $existingNotes = trim($existingNotes);
            $updatedNotes = empty($existingNotes) ? $note : $existingNotes . "\n\n" . $note;

            $stmt = $pdo->prepare("UPDATE tb_site_inspecciones SET notas_site_site_improvements = ? WHERE id_inspeccion = ?");
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
    <title>Site improvements</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body,
        html {
            height: 100%;
        }

        .hidden {
            display: none !important;
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

        #content {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        #buttonContainer {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            width: 100%;
        }

        button,
        input[type="text"] {
            width: 100%;
        }

        .gap-4 {
            gap: 1rem;
        }

        .button-group {
            display: flex;
            gap: 0.5rem;
            width: 100%;
        }
    </style>
</head>

<body>
    <div class="bg-white p-4">
        <div class="flex items-center justify-between border-b pb-3">
            <a href="#" onclick="saveAndGoBack()" class="text-gray-600 h-6 w-6">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
            </a>
            <h1 id="super_titulo" class="text-xl font-semibold text-center">Site improvements</h1>
            <div style="width:24px"></div>
        </div>
        <div class="grid grid-cols-3 mt-4">
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
        <div class="grid grid-cols-2 gap-4 mt-4">
            <div class="flex flex-col w-7/10 pr-2">
                <div id="buttonContainer" class="flex flex-col gap-4">
                    <?php
                    foreach ($buttonOptions as $index => $option) {
                        $activeClass = in_array('"' . $option . '"', $buttonState) ? 'bg-green-300' : 'bg-background';
                        $hiddenClass = ($option === 'No') ? 'hidden' : ''; // Añadir clase 'hidden' al botón "No"
                        echo '<button onclick="toggleButton(this, ' . $index . ')" class="' . $activeClass . ' ' . $hiddenClass . ' inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">';
                        echo htmlspecialchars($option);
                        echo '</button>';
                    }
                    ?>
                    <input type="text" id="otherInput" placeholder="Others" class="inline-flex text-center items-center justify-center whitespace-nowrap rounded-md text-base font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input h-10 px-4 py-2">
                </div>
            </div>
            <div class="flex flex-col w-3/10 pl-2 gap-4">
                <?php
                foreach ($buttonOptions as $index => $option) {
                    $hiddenClass = ($option === 'No') ? 'hidden' : '';
                    echo '<div class="button-group ' . $hiddenClass . '">';
                    echo '<button class="takePhotoButton whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input hover:text-accent-foreground h-10 px-4 flex items-center justify-center py-2 bg-gray-200 hover:bg-gray-300" disabled>';
                    echo '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-600 h-6 w-6">';
                    echo '<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"></path>';
                    echo '<circle cx="12" cy="13" r="3"></circle>';
                    echo '</svg>';
                    echo '</button>';
                    echo '<button class="addNoteBtn whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input hover:text-accent-foreground h-10 px-4 flex items-center justify-center py-2 bg-gray-200 hover:bg-gray-300" disabled>';
                    echo '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-gray-600 h-6 w-6">';
                    echo '<path d="M17 6.1H3"></path>';
                    echo '<path d="M21 12.1H3"></path>';
                    echo '<path d="M15.1 18H3"></path>';
                    echo '</svg>';
                    echo '</button>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <div id="noteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center">
        <div class="bg-white p-4 rounded-lg shadow-lg">
            <h2 class="font-semibold text-lg mb-2">Add a Note</h2>
            <form action="" method="POST">
                <input type="hidden" id="buttonNameInput" name="buttonName">
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
        const addNoteBtn = document.querySelectorAll('.addNoteBtn');
        const noteModal = document.getElementById('noteModal');
        const noteInput = document.getElementById('noteInput');
        const saveBtn = noteModal.querySelector('button[type="submit"]');
        const cancelBtn = noteModal.querySelector('button[type="button"]');
        const takePhotoButtons = document.querySelectorAll('.takePhotoButton');
        const otherInput = document.getElementById('otherInput');

        function openNoteModal(buttonName) {
            document.getElementById('buttonNameInput').value = buttonName;
            noteModal.classList.remove('hidden');
            noteInput.focus();
            checkInput();
        }

        function closeNoteModal() {
            noteModal.classList.add('hidden');
            window.location.href = window.location.pathname + '?id=<?php echo $inspectionId; ?>';
        }

        addNoteBtn.forEach((button, index) => {
            button.addEventListener('click', function() {
                const buttonName = document.querySelectorAll('button[onclick^="toggleButton"]')[index].textContent.trim();
                openNoteModal(buttonName);
            });
        });
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

        function toggleButton(button, index) {
            changesMade = true;
            const isActive = button.classList.contains('bg-green-300');
            const buttons = document.querySelectorAll('button[onclick^="toggleButton"]');

            if (isActive) {
                button.classList.remove('bg-green-300');
                button.classList.add('bg-background', 'hover:bg-accent');
                takePhotoButtons[index].disabled = true;
                addNoteBtn[index].disabled = true;
            } else {
                button.classList.add('bg-green-300');
                button.classList.remove('bg-background', 'hover:bg-accent');
                takePhotoButtons[index].disabled = false;
                addNoteBtn[index].disabled = false;
            }

            saveChanges();
        }

        function saveAndGoBack() {
            if (!changesMade) {
                window.location.href = 'site_overview_menu.php?id=<?php echo $inspectionId; ?>';
                return;
            }
            const buttons = document.querySelectorAll('button[onclick^="toggleButton"]');
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
                    console.log(data);
                    window.location.href = 'site_overview_menu.php?id=<?php echo $inspectionId; ?>';
                })
                .catch(error => console.error('Error:', error));
        }

        document.getElementById('otherInput').addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                const otherInputValue = this.value.trim();
                if (otherInputValue) {
                    addOtherOption(otherInputValue);
                    this.value = '';
                }
            }
        });

        function capitalizeFirstLetter(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }

        function addOtherOption(value) {
            value = capitalizeFirstLetter(value);
            const newButton = document.createElement('button');
            newButton.className = 'bg-green-300 inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2';
            newButton.textContent = value;
            newButton.setAttribute('onclick', 'toggleButton(this, ' + takePhotoButtons.length + ')');
            document.querySelector('#buttonContainer').insertBefore(newButton, otherInput);

            const newPhotoButton = takePhotoButtons[0].cloneNode(true);
            newPhotoButton.disabled = false;
            const newNoteButton = addNoteBtn[0].cloneNode(true);
            newNoteButton.disabled = false;

            const buttonGroup = document.createElement('div');
            buttonGroup.className = 'button-group';
            buttonGroup.appendChild(newPhotoButton);
            buttonGroup.appendChild(newNoteButton);

            document.querySelector('.grid.grid-cols-2.gap-4.mt-4 .flex.flex-col.w-3\\/10.pl-2.gap-4').appendChild(buttonGroup);

            newPhotoButton.addEventListener('click', function() {
                takePhoto(value.replace(/[^a-zA-Z0-9]/g, '_'));
            });

            newNoteButton.addEventListener('click', function() {
                openNoteModal(value);
            });

            saveChanges();
        }

        function saveChanges() {
            const buttons = document.querySelectorAll('button[onclick^="toggleButton"]');
            let activeButtons = [];
            buttons.forEach(button => {
                if (button.classList.contains('bg-green-300')) {
                    activeButtons.push('"' + button.textContent.trim() + '"');
                }
            });

            const formData = new FormData();
            formData.append('activeButtons', activeButtons.join(', '));
            formData.append('id', '<?php echo $inspectionId; ?>');

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

        function redirectToGallery() {
            var inspectionId = '<?php echo $inspectionId; ?>';
            var titulo = document.getElementById('super_titulo').textContent.trim();
            var url = 'component/gallery.php?id=' + encodeURIComponent(inspectionId) + '&titulo=' + encodeURIComponent(titulo);
            window.location.href = url;
        }

        document.querySelectorAll('.takePhotoButton').forEach((button, index) => {
            button.addEventListener('click', function() {
                const activeButton = document.querySelectorAll('button[onclick^="toggleButton"]')[index];
                const buttonName = activeButton ? activeButton.textContent.trim().replace(/[^a-zA-Z0-9]/g, '_') : 'photo';
                takePhoto(buttonName);
            });
        });

        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = 'image/*';
        fileInput.capture = 'camera';

        function takePhoto(buttonName) {
            fileInput.click();
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
                            formData.append('buttonName', buttonName); // Añadir nombre del botón

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
                                    }, 3000);
                                });
                        }, 'image/jpeg', 0.99);
                    };
                    img.src = event.target.result;
                };
                reader.readAsDataURL(file);
            };
        }

        function fileExists(filePath) {
            let xhr = new XMLHttpRequest();
            xhr.open('HEAD', filePath, false);
            xhr.send();

            return xhr.status != 404;
        }

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

        otherInput.addEventListener('blur', function() {
            window.scrollTo(0, 0);
        });

        document.addEventListener('DOMContentLoaded', function() {
            const buttonState = <?php echo json_encode($buttonState); ?>;
            const buttons = document.querySelectorAll('button[onclick^="toggleButton"]');

            buttons.forEach((button, index) => {
                const option = button.textContent.trim();
                if (buttonState.includes('"' + option + '"')) {
                    button.classList.add('bg-green-300');
                    button.classList.remove('bg-background', 'hover:bg-accent');
                    takePhotoButtons[index].disabled = false;
                    addNoteBtn[index].disabled = false;
                } else {
                    button.classList.remove('bg-green-300');
                    button.classList.add('bg-background', 'hover:bg-accent');
                    takePhotoButtons[index].disabled = true;
                    addNoteBtn[index].disabled = true;
                }
            });
        });
    </script>
</body>

</html>