<?php
session_start();

// Configuración de conexión a la base de datos
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

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login_index.html');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Obtener información del usuario
$query = "SELECT nombre_completo, correo_electronico FROM usuarios WHERE id_usuario = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$usuario_id]);

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $nombre_usuario = htmlspecialchars($row['nombre_completo'], ENT_QUOTES, 'UTF-8');
    $correo_usuario = htmlspecialchars($row['correo_electronico'], ENT_QUOTES, 'UTF-8');
} else {
    die('Usuario no encontrado.');
}

// Obtener ID de inspección
if (isset($_GET['id'])) {
    $inspectionId = htmlspecialchars($_GET['id'], ENT_QUOTES, 'UTF-8');
} else {
    echo "Error: ID de inspección no proporcionado.";
    exit;
}

// Obtener detalles de la inspección
$query = "SELECT
    select_option_driveway,
    driveway,
    parking,
    utilities,
    features,
    electrical,
    curb_appeal,
    topography,
    landscaping,
    site_improvements,
    site_features
FROM
    tb_site_inspecciones
WHERE
    id_inspeccion = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$inspectionId]);

// Inicializar variables con valores por defecto
$inspectionDetails = [
    'select_option_driveway' => '',
    'driveway' => '',
    'parking' => '',
    'utilities' => '',
    'features' => '',
    'electrical' => '',
    'curb_appeal' => '',
    'topography' => '',
    'landscaping' => '',
    'site_improvements' => '',
    'site_features' => ''
];

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $inspectionDetails = array_map(function($value) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }, $row);
} else {
    echo "Error: Inspección no encontrada.";
    exit;
}

$checkIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#10B981" class="bi bi-check-circle-fill ml-2" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08 0L7.47 8.47l-2.24-2.24a.75.75 0 0 0-1.06 1.06l2.75 2.75c.3.3.77.3 1.06 0l4.5-4.5a.75.75 0 0 0 0-1.06z"/></svg>';
$incompleteIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#FF0000" class="bi bi-x-circle-fill ml-2" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-4.646-4.646a.5.5 0 0 0-.708 0L8 6.293 4.854 3.146a.5.5 0 1 0-.708.708L7.293 7 3.146 10.854a.5.5 0 0 0 .708.708L8 7.707l3.146 3.147a.5.5 0 0 0 .708-.708L8.707 7l3.147-3.146a.5.5 0 0 0 0-.708z"/></svg>';

// Función para determinar el ícono a mostrar
function getIcon($condition) {
    global $checkIcon, $incompleteIcon;
    return $condition ? $checkIcon : $incompleteIcon;
}

// Determinar los íconos a mostrar para cada sección
$iconToShowDriveway = getIcon($inspectionDetails['select_option_driveway'] === 'None' || !empty($inspectionDetails['driveway']));
$iconToShowParking = getIcon(!empty($inspectionDetails['parking']));
$iconToShowUtilities = getIcon(!empty($inspectionDetails['utilities']));
$iconToShowFeatures = getIcon(!empty($inspectionDetails['features']));
$iconToShowElectrical = getIcon(!empty($inspectionDetails['electrical']));
$iconToShowCurbAppeal = getIcon(!empty($inspectionDetails['curb_appeal']));
$iconToShowTopography = getIcon(!empty($inspectionDetails['topography']));
$iconToShowLandscaping = getIcon(!empty($inspectionDetails['landscaping']));
$iconToShowSite_improvements = getIcon(!empty($inspectionDetails['site_improvements']));
$iconToShowSite_features = getIcon(!empty($inspectionDetails['site_features']));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection Site</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body,
        html {
            height: 100%;
        }
    </style>
</head>

<body>
    <div class="bg-white p-4">
        <div class="flex items-center justify-between border-b pb-4">
            <a href="overview_inspection.php?id=<?php echo htmlspecialchars(urlencode($inspectionId)); ?>" class="inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-800 h-6 w-6">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
            </a>
            <h1 class="text-xl font-semibold text-center text-gray-800">Site</h1>
            <div></div>
        </div>
        <div class="hidden mt-4 flex justify-between">
            <button onclick="window.location.href='component/note.php?id=<?php echo $inspectionId; ?>&moduls=<?php echo $moduls; ?>'" class="flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-blue-500 hover:bg-blue-600 text-white h-10 px-4 py-2 flex-1 mr-2">
                <svg class="h-6 w-6 text-white" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" />
                    <line x1="13" y1="20" x2="20" y2="13" />
                    <path d="M13 20v-6a1 1 0 0 1 1 -1h6v-7a2 2 0 0 0 -2 -2h-12a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7" />
                </svg>
                <span class="ml-2">All Notes</span>
            </button>

            <button onclick="redirectToGallery()" class="flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-green-500 hover:bg-green-600 text-white h-10 px-4 py-2 flex-1 ml-2">
                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="ml-2">Gallery</span>
            </button>
        </div>

        <div class="mt-8 space-y-4">
            <?php
            echo '<a href="site_overview_driveway.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . ($inspectionDetails['select_option_driveway'] === 'None' || !empty($inspectionDetails['driveway']) ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Driveway' . $iconToShowDriveway . '</a>';
            echo '<a href="site_overview_parking.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . (!empty($inspectionDetails['parking']) ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Parking' . $iconToShowParking . '</a>';
            echo '<a href="site_overview_electrical.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . (!empty($inspectionDetails['electrical']) ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Electrical' . $iconToShowElectrical . '</a>';
            echo '<a href="site_overview_utilities.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . (!empty($inspectionDetails['utilities']) ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Utilities' . $iconToShowUtilities . '</a>';
            echo '<a href="site_overview_features.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . (!empty($inspectionDetails['features']) ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Features' . $iconToShowFeatures . '</a>';
            echo '<a href="site_overview_curb_appeal.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . (!empty($inspectionDetails['curb_appeal']) ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Curb appeal' . $iconToShowCurbAppeal . '</a>';
            echo '<a href="site_overview_topography.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . (!empty($inspectionDetails['topography']) ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">Topography' . $iconToShowTopography . '</a>';
            echo '<a href="site_overview_landscaping.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . (!empty($inspectionDetails['landscaping']) ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Landscaping' . $iconToShowLandscaping . '</a>';
            echo '<a href="site_overview_site_improvements.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . (!empty($inspectionDetails['site_improvements']) ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Site improvements' . $iconToShowSite_improvements . '</a>';
            echo '<a href="site_overview_site_features.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . (!empty($inspectionDetails['site_features']) ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Site features' . $iconToShowSite_features . '</a>';
            ?>
        </div>
    </div>
</body>

</html>
