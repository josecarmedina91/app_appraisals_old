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

$query = "SELECT num_pisos FROM vt_num_pisos WHERE id_inspeccion = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$inspectionId]);

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $numPisos = $row['num_pisos'];
} else {
    die('Inspección no encontrada.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['imageData']) && isset($data['level']) && isset($data['drawingData'])) {
        $imageData = $data['imageData'];
        $level = $data['level'];
        $drawingData = $data['drawingData'];

        $imageData = str_replace('data:image/png;base64,', '', $imageData);
        $imageData = str_replace(' ', '+', $imageData);
        $data = base64_decode($imageData);

        $imageDir = "img/photo_gallery/$inspectionId/Floor_area_sketch";
        if (!file_exists($imageDir)) {
            mkdir($imageDir, 0777, true);
        }

        $filePath = "$imageDir/Level_$level.jpg";
        file_put_contents($filePath, $data);

        $jsonFilePath = "$imageDir/Level_$level.json";
        file_put_contents($jsonFilePath, json_encode($drawingData));

        echo json_encode(['status' => 'success', 'message' => 'Image and drawing data saved successfully.', 'path' => $filePath]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data.']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responsive UI</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body,
        html {
            height: 100%;
            overflow: hidden;
        }

        .drawing {
            cursor: crosshair;
        }

        .drawing-canvas {
            position: absolute;
            top: 0;
            left: 0;
        }

        #numberInputModal {
            display: none;
        }

        #numberInputModal.show {
            display: flex;
        }

        #numberInputModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
    </style>
</head>

<body class="bg-gray-100 h-screen flex flex-col">
    <div class="flex flex-col h-screen">
        <div class="flex flex-wrap items-center justify-between px-4 py-2 bg-gray-100 border-b">
            <div class="flex items-center gap-2 flex-wrap">
                <select id="levelSelector" class="flex h-10 items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 w-24">
                    <?php for ($i = 1; $i <= $numPisos; $i++) : ?>
                        <option value="<?php echo $i; ?>">Level <?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
                <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-base font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-10 w-10" id="undoButton">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                        <path d="M3 7v6h6"></path>
                        <path d="M21 17a9 9 0 0 0-9-9 9 0 0 0-6 2.3L3 13"></path>
                    </svg>
                    <span class="sr-only">Undo</span>
                </button>
                <div class="flex gap-2 flex-wrap">
                    <button class="justify-center whitespace-nowrap rounded-md text-base font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-red-500 bg-red-500 text-white hover:bg-red-600 h-10 w-32 px-4 py-2 flex items-center gap-2" id="cancelButton">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        Close
                    </button>
                    <button class="justify-center whitespace-nowrap rounded-md text-base font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-blue-500 bg-blue-500 text-white hover:bg-blue-600 h-10 w-32 px-4 py-2 flex items-center gap-2" id="saveButton">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                            <path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"></path>
                            <path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"></path>
                            <path d="M7 3v4a1 1 0 0 0 1 1h7"></path>
                        </svg>
                        Save
                    </button>
                </div>
            </div>
        </div>
        <div class="flex flex-1 flex-col h-full relative" id="drawingArea"></div>
    </div>
    <div id="numberInputModal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">
        <div class="bg-white rounded-lg p-4">
            <label for="numberInput" class="block mb-2">Enter the line value in feet (ft):</label>
            <input type="number" id="numberInput" inputmode="numeric" class="block w-full border rounded-md p-2 mb-4">
            <div class="flex justify-between">
                <button id="cancelInput" class="w-1/2 mr-2 bg-red-500 text-white rounded-md py-2">Cancel</button>
                <button id="submitInput" class="w-1/2 bg-blue-500 text-white rounded-md py-2">Save</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const undoButton = document.getElementById('undoButton');
            const saveButton = document.getElementById('saveButton');
            const cancelButton = document.getElementById('cancelButton');
            const drawingArea = document.getElementById('drawingArea');
            const levelSelector = document.getElementById('levelSelector');
            let drawingEnabled = false;
            let painting = false;
            let brushSize = 5;
            let erasing = false;
            let canvas, ctx;
            let startX, startY;
            let lineMode = true;
            let savedImage = null;
            let lines = [];
            let texts = [];
            let tempLine = null;
            let isDrawing = false;

            function insertText(startX, startY, endX, endY) {
                const modal = document.getElementById('numberInputModal');
                const numberInput = document.getElementById('numberInput');
                const submitInput = document.getElementById('submitInput');
                const cancelInput = document.getElementById('cancelInput');

                const scrollX = window.scrollX;
                const scrollY = window.scrollY;

                numberInput.value = '';
                modal.classList.add('show');
                numberInput.focus();

                return new Promise((resolve) => {
                    submitInput.onclick = () => {
                        let value = numberInput.value;
                        if (value.trim() !== "" && !isNaN(value)) {
                            value += " ft";
                            modal.classList.remove('show');
                            window.scrollTo(scrollX, scrollY);
                            addTextElement(startX, startY, endX, endY, value);
                            resolve(value);
                        }
                    };

                    cancelInput.onclick = () => {
                        modal.classList.remove('show');
                        window.scrollTo(scrollX, scrollY);
                        removeLastLine();
                        resolve(null);
                    };
                });
            }

            function addTextElement(startX, startY, endX, endY, text) {
                const textElement = document.createElement('div');
                textElement.innerText = text;
                textElement.style.position = 'absolute';

                const midX = (startX + endX) / 2;
                const midY = (startY + endY) / 2;
                const isHorizontal = Math.abs(endX - startX) > Math.abs(endY - startY);

                if (isHorizontal) {
                    textElement.style.left = `${midX}px`;
                    textElement.style.top = `${startY - 20}px`;
                } else {
                    textElement.style.left = `${startX - 25}px`;
                    textElement.style.top = `${midY}px`;
                }

                textElement.style.cursor = 'move';
                textElement.style.fontSize = '20px';

                drawingArea.appendChild(textElement);
                texts.push(textElement);

                textElement.addEventListener('mousedown', startDrag);
                textElement.addEventListener('touchstart', startDrag, {
                    passive: false
                });
            }
            let dragElement = null;
            let offsetX = 0;
            let offsetY = 0;

            function startDrag(e) {
                e.preventDefault();
                dragElement = e.target;
                const rect = dragElement.getBoundingClientRect();

                if (e.touches) {
                    offsetX = e.touches[0].clientX - rect.left;
                    offsetY = e.touches[0].clientY - rect.top;
                } else {
                    offsetX = e.clientX - rect.left;
                    offsetY = e.clientY - rect.top;
                }

                document.addEventListener('mousemove', drag);
                document.addEventListener('mouseup', endDrag);
                document.addEventListener('touchmove', drag, {
                    passive: false
                });
                document.addEventListener('touchend', endDrag, {
                    passive: false
                });
            }

            function drag(e) {
                if (!dragElement) return;

                e.preventDefault();
                let x, y;
                if (e.touches) {
                    x = e.touches[0].clientX - offsetX;
                    y = e.touches[0].clientY - offsetY;
                } else {
                    x = e.clientX - offsetX;
                    y = e.clientY - offsetY;
                }
                dragElement.style.left = `${x}px`;
                dragElement.style.top = `${y}px`;
            }

            function endDrag() {
                dragElement = null;
                document.removeEventListener('mousemove', drag);
                document.removeEventListener('mouseup', endDrag);
                document.removeEventListener('touchmove', drag);
                document.removeEventListener('touchend', endDrag);
            }

            undoButton.addEventListener('click', () => {
                undoLastAction();
            });

            saveButton.addEventListener('click', () => {
                saveDrawing();
            });

            cancelButton.addEventListener('click', () => {
                window.location.href = `overview_inspection.php?id=<?php echo htmlspecialchars(urlencode($inspectionId)); ?>`;
            });

            function createCanvas() {
                canvas = document.createElement('canvas');
                canvas.width = drawingArea.clientWidth;
                canvas.height = drawingArea.clientHeight;
                canvas.classList.add('drawing-canvas');
                drawingArea.appendChild(canvas);
                ctx = canvas.getContext('2d');
                ctx.strokeStyle = 'black';
                ctx.lineWidth = brushSize;
            }

            function enableDrawing() {
                if (!canvas) {
                    createCanvas();
                }
                canvas.addEventListener('mousedown', startPosition);
                canvas.addEventListener('mouseup', endPosition);
                canvas.addEventListener('mousemove', draw);
                canvas.addEventListener('mouseleave', endPosition);
                canvas.addEventListener('touchstart', startPosition);
                canvas.addEventListener('touchend', endPosition);
                canvas.addEventListener('touchmove', draw);
            }

            function disableDrawing() {
                if (canvas) {
                    canvas.removeEventListener('mousedown', startPosition);
                    canvas.removeEventListener('mouseup', endPosition);
                    canvas.removeEventListener('mousemove', draw);
                    canvas.removeEventListener('mouseleave', endPosition);
                    canvas.removeEventListener('touchstart', startPosition);
                    canvas.removeEventListener('touchend', endPosition);
                    canvas.removeEventListener('touchmove', draw);
                }
            }

            function startPosition(e) {
                if (!drawingEnabled && !lineMode) return;

                painting = true;
                isDrawing = true;
                const rect = canvas.getBoundingClientRect();
                if (lineMode && lines.length > 0) {
                    const closestPoint = getClosestPoint(e, rect);
                    startX = closestPoint.x;
                    startY = closestPoint.y;
                } else {
                    if (e.touches) {
                        startX = e.touches[0].clientX - rect.left;
                        startY = e.touches[0].clientY - rect.top;
                    } else {
                        startX = e.clientX - rect.left;
                        startY = e.clientY - rect.top;
                    }
                }
                if (!lineMode) {
                    draw(e);
                }
            }

            function endPosition(e) {
                if (!drawingEnabled && !lineMode) return;

                if (!isDrawing) return;

                painting = false;
                isDrawing = false;
                ctx.beginPath();
                if (lineMode) {
                    const rect = canvas.getBoundingClientRect();
                    let endX, endY;
                    if (e.touches) {
                        endX = e.changedTouches[0].clientX - rect.left;
                        endY = e.changedTouches[0].clientY - rect.top;
                    } else {
                        endX = e.clientX - rect.left;
                        endY = e.clientY - rect.top;
                    }
                    ctx.beginPath();
                    ctx.moveTo(startX, startY);
                    ctx.lineTo(endX, endY);
                    ctx.stroke();
                    ctx.beginPath();

                    tempLine = {
                        startX,
                        startY,
                        endX,
                        endY
                    };
                    lines.push(tempLine);

                    insertText(startX, startY, endX, endY);

                    startX = endX;
                    startY = endY;
                }

                savedImage = canvas.toDataURL();
            }

            function draw(e) {
                if (!painting) return;

                e.preventDefault();

                const rect = canvas.getBoundingClientRect();
                let x, y;

                if (e.touches) {
                    x = e.touches[0].clientX - rect.left;
                    y = e.touches[0].clientY - rect.top;
                } else {
                    x = e.clientX - rect.left;
                    y = e.clientY - rect.top;
                }

                ctx.lineWidth = brushSize;
                ctx.lineCap = 'round';

                if (erasing) {
                    ctx.globalCompositeOperation = 'destination-out';
                    ctx.strokeStyle = 'rgba(0,0,0,1)';
                } else {
                    ctx.globalCompositeOperation = 'source-over';
                    ctx.strokeStyle = 'black';
                }

                if (lineMode) {
                    restoreCanvas();
                    ctx.beginPath();
                    ctx.moveTo(startX, startY);
                    ctx.lineTo(x, y);
                    ctx.stroke();
                    ctx.beginPath();
                } else {
                    ctx.lineTo(x, y);
                    ctx.stroke();
                    ctx.beginPath();
                    ctx.moveTo(x, y);
                }
            }

            function restoreCanvas() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                if (savedImage) {
                    let img = new Image();
                    img.src = savedImage;
                    img.onload = () => {
                        ctx.drawImage(img, 0, 0);
                        redrawLines();
                    };
                } else {
                    redrawLines();
                }
            }

            function redrawLines() {
                lines.forEach(line => {
                    ctx.beginPath();
                    ctx.moveTo(line.startX, line.startY);
                    ctx.lineTo(line.endX, line.endY);
                    ctx.stroke();
                });
            }

            function undoLastAction() {
                if (lines.length > 0) {
                    lines.pop();
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    redrawLines();
                    savedImage = canvas.toDataURL();
                }
                if (texts.length > 0) {
                    const lastText = texts.pop();
                    lastText.remove();
                }
            }

            function removeLastLine() {
                if (tempLine) {
                    lines.pop();
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    redrawLines();
                    savedImage = canvas.toDataURL();
                    tempLine = null;
                }
            }

            function getClosestPoint(e, rect) {
                let minDist = Infinity;
                let closestPoint = {
                    x: startX,
                    y: startY
                };
                lines.forEach(line => {
                    const points = [{
                        x: line.startX,
                        y: line.startY
                    }, {
                        x: line.endX,
                        y: line.endY
                    }];
                    points.forEach(point => {
                        const dx = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left - point.x;
                        const dy = (e.touches ? e.touches[0].clientY : e.clientY) - rect.top - point.y;
                        const dist = Math.sqrt(dx * dx + dy * dy);
                        if (dist < minDist) {
                            minDist = dist;
                            closestPoint = point;
                        }
                    });
                });
                return closestPoint;
            }

            async function saveDrawing() {
                const level = levelSelector.value;

                const offscreenCanvas = document.createElement('canvas');
                const offscreenCtx = offscreenCanvas.getContext('2d');
                offscreenCanvas.width = canvas.width;
                offscreenCanvas.height = canvas.height;

                offscreenCtx.fillStyle = 'white';
                offscreenCtx.fillRect(0, 0, offscreenCanvas.width, offscreenCanvas.height);
                offscreenCtx.drawImage(canvas, 0, 0);

                texts.forEach(textElement => {
                    const rect = textElement.getBoundingClientRect();
                    const x = rect.left - canvas.getBoundingClientRect().left;
                    const y = rect.top - canvas.getBoundingClientRect().top + parseInt(window.getComputedStyle(textElement).fontSize, 10);
                    offscreenCtx.font = textElement.style.fontSize + ' ' + 'sans-serif';
                    offscreenCtx.fillStyle = 'black';
                    offscreenCtx.fillText(textElement.innerText, x, y);
                });

                const finalImageData = offscreenCanvas.toDataURL('image/png');

                const drawingData = {
                    lines: lines.map(line => ({
                        start: {
                            x: line.startX,
                            y: line.startY
                        },
                        end: {
                            x: line.endX,
                            y: line.endY
                        }
                    })),
                    texts: texts.map(textElement => ({
                        position: {
                            x: parseFloat(textElement.style.left),
                            y: parseFloat(textElement.style.top)
                        },
                        content: textElement.innerText
                    }))
                };

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        imageData: finalImageData,
                        level: level,
                        drawingData: drawingData
                    }),
                });

                try {
                    const result = await response.json();
                    if (result.status === 'success') {
                        alert('Image and drawing data saved successfully!');
                    } else {
                        alert('Failed to save image and drawing data.');
                    }
                } catch (error) {
                    alert('Failed to parse server response.');
                }
            }

            function loadDrawingData(level) {
                lines = [];
                texts.forEach(textElement => textElement.remove());
                texts = [];
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                fetch(`img/photo_gallery/${<?php echo $inspectionId; ?>}/Floor_area_sketch/Level_${level}.json`)
                    .then(response => response.json())
                    .then(data => {
                        lines = data.lines.map(line => ({
                            startX: line.start.x,
                            startY: line.start.y,
                            endX: line.end.x,
                            endY: line.end.y
                        }));

                        data.texts.forEach(text => {
                            const adjustedX = text.position.x + 25;
                            const adjustedY = text.position.y + 0;
                            addTextElement(
                                adjustedX,
                                adjustedY,
                                adjustedX,
                                adjustedY,
                                text.content
                            );
                        });

                        restoreCanvas();
                    })
                    .catch(error => {
                        console.error('Error loading drawing data:', error);
                    });
            }

            levelSelector.addEventListener('change', () => {
                const level = levelSelector.value;
                window.location.href = `${window.location.pathname}?id=<?php echo $inspectionId; ?>&level=${level}`;
            });

            window.onload = () => {
                const urlParams = new URLSearchParams(window.location.search);
                const level = urlParams.get('level') || levelSelector.value;
                levelSelector.value = level;
                loadDrawingData(level);
            };

            createCanvas();
            enableDrawing();
        });
    </script>

</body>

</html>