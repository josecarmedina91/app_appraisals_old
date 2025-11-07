<?php
session_start();

$host = 'localhost';
$dbname = 'db_community_appraisals';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
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
    $nombre_usuario = htmlspecialchars($row['nombre_completo']);
    $correo_usuario = htmlspecialchars($row['correo_electronico']);
} else {
    die('User not found.');
}

if (isset($_GET['id'])) {
    $inspectionId = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
    if (!$inspectionId) {
        echo "Error: Invalid inspection ID.";
        exit;
    }
} else {
    echo "Error: Inspection ID not provided.";
    exit;
}

$consentData = null;
$query = "SELECT * FROM tbl_consents WHERE id_inspeccion = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$inspectionId]);

$defaultDate = '0000-00-00';
$consentData = $stmt->fetch(PDO::FETCH_ASSOC);

if ($consentData) {
    $consentData = array_map('htmlspecialchars', $consentData);
    $signaturePath = "img/photo_gallery/$inspectionId/Photo_Consent_Form/Signature_of_" . preg_replace("/[^a-zA-Z0-9]+/", "_", $consentData['occupant_name']) . ".jpg";
    if ($consentData['date'] == $defaultDate || $consentData['date'] == null) {
        $setDefaultDate = true;
    } else {
        $setDefaultDate = false;
    }
} else {
    $setDefaultDate = true;
    $signaturePath = null;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['signature'])) {
        $signature = $_POST['signature'];
        $signature = str_replace('data:image/png;base64,', '', $signature);
        $signature = str_replace(' ', '+', $signature);
        $data = base64_decode($signature);
        $occupantName = $consentData ? $consentData['occupant_name'] : 'unknown';
        $signaturePath = "img/photo_gallery/$inspectionId/Photo_Consent_Form/Signature_of_" . preg_replace("/[^a-zA-Z0-9]+/", "_", $occupantName) . ".jpg";

        if (!is_dir(dirname($signaturePath))) {
            mkdir(dirname($signaturePath), 0777, true);
        }

        file_put_contents($signaturePath, $data);

        $query = "UPDATE tbl_consents SET signature_requested = 1 WHERE id_inspeccion = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$inspectionId]);

        $_SESSION['signature_requested'] = true;

        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $inspectionId);
        exit;
    }

    if (isset($_POST['delete_signature'])) {
        $signaturePathToDelete = $_POST['delete_signature'];
        if (file_exists($signaturePathToDelete)) {
            unlink($signaturePathToDelete);

            $query = "UPDATE tbl_consents SET signature_requested = 0 WHERE id_inspeccion = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$inspectionId]);
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $inspectionId);
        exit;
    }

    if (isset($_POST['vacant_property_update'])) {
        $vacantProperty = filter_var($_POST['vacant_property_update'], FILTER_SANITIZE_NUMBER_INT);
        $query = "UPDATE tbl_consents SET vacant_property = ? WHERE id_inspeccion = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$vacantProperty, $inspectionId]);
    }

    if (isset($_POST['delete_folder']) && isset($_POST['inspection_id'])) {
        $inspectionId = filter_var($_POST['inspection_id'], FILTER_SANITIZE_NUMBER_INT);
        $folderPath = "img/photo_gallery/$inspectionId/Photo_Consent_Form";
        if (is_dir($folderPath)) {
            array_map('unlink', glob("$folderPath/*.*"));
            rmdir($folderPath);
            echo "Folder deleted.";
        } else {
            echo "The folder does not exist.";
        }
        exit;
    }

    $vacantProperty = isset($_POST['vacant_property']) ? 1 : 0;
    $consentPhotos = isset($_POST['consent_photos']) ? 1 : 0;
    $consentPhotosException = isset($_POST['consent_photos_exception']) ? 1 : 0;
    $exceptionDetails = $consentPhotosException ? filter_var($_POST['exception_details'], FILTER_SANITIZE_STRING) : null;
    $occupantName = filter_var($_POST['occupant_name'], FILTER_SANITIZE_STRING);
    $occupantType = filter_var($_POST['occupant_type'], FILTER_SANITIZE_STRING);
    $date = filter_var($_POST['date'], FILTER_SANITIZE_STRING);
    $signatureRequested = isset($_SESSION['signature_requested']) ? 1 : 0;

    if (isset($_POST['update_date'])) {
        $date = filter_var($_POST['update_date'], FILTER_SANITIZE_STRING);
        $query = "UPDATE tbl_consents SET date = ? WHERE id_inspeccion = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$date, $inspectionId]);
    }

    $query = "INSERT INTO tbl_consents (id_inspeccion, vacant_property, consent_photos, consent_photos_exception, exception_details, occupant_name, occupant_type, date, signature_requested, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
              ON DUPLICATE KEY UPDATE 
                vacant_property = VALUES(vacant_property),
                consent_photos = VALUES(consent_photos),
                consent_photos_exception = VALUES(consent_photos_exception),
                exception_details = VALUES(exception_details),
                occupant_name = VALUES(occupant_name),
                occupant_type = VALUES(occupant_type),
                date = VALUES(date),
                signature_requested = VALUES(signature_requested),
                created_at = NOW()";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$inspectionId, $vacantProperty, $consentPhotos, $consentPhotosException, $exceptionDetails, $occupantName, $occupantType, $date, $signatureRequested]);

    if (isset($_SESSION['signature_requested'])) {
        unset($_SESSION['signature_requested']);
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $inspectionId);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photo Consent Form</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <style>
        body,
        html {
            height: 100%;
        }
    </style>
</head>

<body class="bg-gray-100 p-6">
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-lg">
        <button type="button" onclick="validateForm()" class="inline-block mb-4 p-2 bg-blue-500 text-white rounded-full hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-lg">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>
        <h2 class="text-2xl font-bold mb-4">Photo Consent (to be completed by occupant):</h2>
        <form id="consent-form" method="POST" action="">
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" class="mr-2 rounded" id="vacant-property" name="vacant_property" <?= $consentData && $consentData['vacant_property'] ? 'checked' : '' ?>>
                    The property is vacant
                </label>
            </div>
            <p class="mb-4">
                In accordance with the Personal Information Protection and Electronic Documents Act (PIPEDA) and the
                Appraisal Institute of Canada’s (AIC) Canadian Uniform Standards of Professional Appraisal Practice
                (CUSPAP), your consent to allow photographs of the interior and exterior of the subject property and
                acknowledgement of the same is required before photographs may be taken. <br><br>
                In signing below, you confirm understanding of this and that these photographs could include personal
                information and personal private property.
            </p>
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" class="mr-2 rounded consent-checkbox" id="consent-photos" name="consent_photos" <?= $consentData && $consentData['consent_photos'] ? 'checked' : '' ?>>
                    I consent to the taking of photographs of the interior and exterior of the subject property
                </label>
            </div>
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" class="mr-2 rounded consent-checkbox" id="consent-photos-exception" name="consent_photos_exception" <?= $consentData && $consentData['consent_photos_exception'] ? 'checked' : '' ?>>
                    I consent to the taking of photographs of the interior and exterior of the subject property with the
                    exception of the following:
                </label>
                <input type="text" class="w-full mt-2 p-2 border rounded-lg" id="exception-details" name="exception_details" placeholder="Enter exceptions here" value="<?= $consentData ? $consentData['exception_details'] : '' ?>">
            </div>
            <div class="mb-4">
                <label class="block mb-2" for="occupant-name">Occupant Name:</label>
                <input type="text" class="w-full p-2 border rounded-lg" id="occupant-name" name="occupant_name" placeholder="Enter occupant name" value="<?= $consentData ? $consentData['occupant_name'] : '' ?>">
            </div>
            <div class="mb-4">
                <label class="block mb-2">I am the:</label>
                <div class="flex items-center space-x-4">
                    <label class="flex items-center">
                        <input type="radio" name="occupant_type" value="owner" class="mr-2 rounded" <?= $consentData && $consentData['occupant_type'] == 'owner' ? 'checked' : '' ?>>
                        Owner
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="occupant_type" value="tenant" class="mr-2 rounded" <?= $consentData && $consentData['occupant_type'] == 'tenant' ? 'checked' : '' ?>>
                        Tenant
                    </label>
                </div>
            </div>
            <div class="mb-4">
                <label class="block mb-2" for="date">Date:</label>
                <input type="date" class="w-full p-2 border rounded-lg" id="date" name="date" value="<?= $setDefaultDate ? '' : ($consentData ? $consentData['date'] : '') ?>">
            </div>
            <div class="mb-4">
                <label class="block mb-2" for="occupant-signature">Occupant Signature:</label>
                <?php if ($signaturePath && file_exists($signaturePath)) : ?>
                    <div class="relative">
                        <img src="<?= $signaturePath ?>?t=<?= time() ?>" alt="Signature" class="w-full h-auto border rounded-lg cursor-pointer" onclick="openSignatureModal('<?= $signaturePath ?>?t=<?= time() ?>')">
                        <button type="button" class="absolute top-0 right-0 p-1 bg-red-500 text-white rounded-full hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 shadow-lg" onclick="deleteSignature('<?= $signaturePath ?>')">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                <?php else : ?>
                    <button type="button" class="w-full p-2 border rounded-lg bg-black text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-black shadow-lg" onclick="openSignatureModal()">Request Signature</button>
                <?php endif; ?>
                <input type="hidden" name="signature_requested" id="signature-requested" value="<?= $consentData ? $consentData['signature_requested'] : 0 ?>">
            </div>
            <button type="button" onclick="validateForm()" class="w-full p-2 bg-green-500 text-white rounded-lg hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 shadow-lg">Close</button>
        </form>
    </div>

    <div id="signature-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-4 rounded-lg shadow-lg max-w-lg w-full max-h-screen h-5/6 flex flex-col">
            <h2 class="text-xl font-bold mb-4">Draw your signature</h2>
            <canvas id="signature-pad" class="border w-full h-64 mb-4 flex-grow"></canvas>
            <div class="mt-auto flex justify-end space-x-4 w-full">
                <button type="button" class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 shadow-lg w-1/2" onclick="closeSignatureModal()">Cancel</button>
                <button type="button" class="p-2 bg-green-500 text-white rounded-lg hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 shadow-lg w-1/2" onclick="saveSignature()">Save</button>
            </div>
        </div>
    </div>

    <div id="view-signature-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-4 rounded-lg shadow-lg max-w-lg w-full max-h-screen h-5/6 flex flex-col">
            <div class="relative">
                <img id="view-signature-image" src="" alt="Signature" class="w-full h-auto">
                <button type="button" class="absolute top-0 right-0 p-1 bg-red-500 text-white rounded-full hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 shadow-lg" onclick="closeViewSignatureModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
    <script>
        let signaturePad;

        function openSignatureModal(imageSrc) {
            if (imageSrc) {
                document.getElementById('view-signature-image').src = imageSrc;
                document.getElementById('view-signature-modal').classList.remove('hidden');
            } else {
                document.getElementById('signature-modal').classList.remove('hidden');
                const canvas = document.getElementById('signature-pad');
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext('2d').scale(ratio, ratio);
                signaturePad = new SignaturePad(canvas, {
                    backgroundColor: 'rgba(255, 255, 255, 0)',
                    penColor: 'rgb(0, 0, 0)'
                });
            }
        }

        function closeSignatureModal() {
            document.getElementById('signature-modal').classList.add('hidden');
            signaturePad.clear();
        }

        function closeViewSignatureModal() {
            document.getElementById('view-signature-modal').classList.add('hidden');
        }

        function saveSignature() {
    if (signaturePad.isEmpty()) {
        alert('Please provide a signature first.');
        return;
    }

    const canvas = document.getElementById('signature-pad');
    const ctx = canvas.getContext('2d');

    const scale = 2;
    const width = canvas.width;
    const height = canvas.height;

    const tempCanvas = document.createElement('canvas');
    const tempCtx = tempCanvas.getContext('2d');
    tempCanvas.width = width * scale;
    tempCanvas.height = height * scale;
    tempCtx.scale(scale, scale);

    tempCtx.fillStyle = 'white';
    tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);

    tempCtx.drawImage(canvas, 0, 0);

    const dataURL = tempCanvas.toDataURL('image/png');

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            closeSignatureModal();
            document.getElementById('signature-requested').value = 1;
            document.getElementById('consent-form').submit();
        } else {
            alert('Failed to save the signature.');
        }
    };

    xhr.onerror = function() {
        alert('Failed to save the signature.');
    };

    xhr.send('signature=' + encodeURIComponent(dataURL));
}

function deleteSignature(signaturePath) {
    if (confirm('Are you sure you want to delete this signature?')) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                location.reload();
            } else {
                alert('Failed to delete the signature.');
            }
        };

        xhr.onerror = function() {
            alert('Failed to delete the signature.');
        };

        xhr.send('delete_signature=' + encodeURIComponent(signaturePath));
    }
}

        document.getElementById('vacant-property').addEventListener('change', function() {
            const isVacant = this.checked;
            const checkboxes = document.querySelectorAll('.consent-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.disabled = isVacant;
                checkbox.checked = false;
            });
            const fieldsToToggle = ['occupant-name', 'date'];
            fieldsToToggle.forEach(field => {
                document.getElementById(field).disabled = isVacant;
                document.getElementById(field).value = '';
            });
            document.querySelectorAll('input[name="occupant_type"]').forEach(radio => {
                radio.disabled = isVacant;
                radio.checked = false;
            });
            document.getElementById('exception-details').disabled = isVacant;
            document.getElementById('exception-details').value = '';

            if (isVacant) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        console.log('Vacant property value and folder deletion handled successfully.');
                        window.location.href = 'overview_inspection.php?id=<?= $inspectionId ?>';
                    } else {
                        console.log('Failed to handle vacant property value and folder deletion.');
                    }
                };

                xhr.onerror = function() {
                    console.log('Failed to handle vacant property value and folder deletion.');
                };

                xhr.send('vacant_property_update=1&delete_folder=1&inspection_id=<?= $inspectionId ?>');
            }
        });

        document.querySelectorAll('.consent-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    document.getElementById('vacant-property').checked = false;
                    document.getElementById('vacant-property').disabled = false;
                }
                if (this.id === 'consent-photos') {
                    document.getElementById('consent-photos-exception').checked = false;
                    document.getElementById('exception-details').disabled = true;
                    document.getElementById('exception-details').value = '';
                } else if (this.id === 'consent-photos-exception') {
                    document.getElementById('consent-photos').checked = false;
                    document.getElementById('exception-details').disabled = !this.checked;
                }
            });
        });

        document.querySelectorAll('input, checkbox').forEach(input => {
            input.addEventListener('change', function() {
                const formData = new FormData(document.getElementById('consent-form'));
                formData.append('ajax', '1');

                const xhr = new XMLHttpRequest();
                xhr.open('POST', '', true);
                xhr.onload = function() {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        console.log('Data saved successfully');
                    } else {
                        console.log('Failed to save data');
                    }
                };

                xhr.onerror = function() {
                    console.log('Failed to save data');
                };

                xhr.send(formData);
            });
        });

        function setFormValues() {
            const vacantProperty = <?= $consentData && $consentData['vacant_property'] ? 'true' : 'false' ?>;
            document.getElementById('vacant-property').checked = vacantProperty;
            document.getElementById('consent-photos').checked = <?= $consentData && $consentData['consent_photos'] ? 'true' : 'false' ?>;
            document.getElementById('consent-photos-exception').checked = <?= $consentData && $consentData['consent_photos_exception'] ? 'true' : 'false' ?>;
            document.getElementById('exception-details').value = "<?= $consentData ? htmlspecialchars($consentData['exception_details']) : '' ?>";
            document.getElementById('occupant-name').value = "<?= $consentData ? htmlspecialchars($consentData['occupant_name']) : '' ?>";
            if (<?= $setDefaultDate ? 'true' : 'false' ?>) {
                const today = new Date().toISOString().split('T')[0];
                document.getElementById('date').value = today;
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.send('update_date=' + encodeURIComponent(today) + '&inspection_id=<?= $inspectionId ?>');
            } else {
                document.getElementById('date').value = "<?= $consentData ? htmlspecialchars($consentData['date']) : '' ?>";
            }
            const occupantType = document.querySelector('input[name="occupant_type"][value="<?= $consentData ? htmlspecialchars($consentData['occupant_type']) : '' ?>"]');
            if (occupantType) {
                occupantType.checked = true;
            }
        }

        function validateForm() {
    if (document.getElementById('vacant-property').checked) {
        window.location.href = 'overview_inspection.php?id=<?= $inspectionId ?>';
    } else {
        const form = document.getElementById('consent-form');
        const occupantName = document.getElementById('occupant-name').value.trim();
        const date = document.getElementById('date').value.trim();
        const occupantType = document.querySelector('input[name="occupant_type"]:checked');
        const consentPhotos = document.getElementById('consent-photos').checked;
        const consentPhotosException = document.getElementById('consent-photos-exception').checked;
        const exceptionDetails = document.getElementById('exception-details').value.trim();
        const signatureRequested = document.getElementById('signature-requested').value;

        if (!occupantName) {
            alert('Please enter the occupant name.');
            return;
        }

        if (!date) {
            alert('Please enter the date.');
            return;
        }

        if (!occupantType) {
            alert('Please select the occupant type.');
            return;
        }

        if (!consentPhotos && !consentPhotosException) {
            alert('Please provide consent for photos or specify exceptions.');
            return;
        }

        if (consentPhotosException && !exceptionDetails) {
            alert('Please enter the exception details.');
            return;
        }

        if (signatureRequested != 1) {
            alert('Please provide a signature.');
            return;
        }

        window.location.href = 'overview_inspection.php?id=<?= $inspectionId ?>';
    }
}

        document.addEventListener('DOMContentLoaded', function() {
            setFormValues();
        });
    </script>
</body>

</html>