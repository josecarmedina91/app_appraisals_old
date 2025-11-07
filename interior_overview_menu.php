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

$query = "SELECT * FROM tb_interior_inspecciones WHERE id_inspeccion = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$inspectionId]);

$under_construction_or_renovation = $overall_in_condition = $built_ins_extras = $counter_tops = $floor_plan = $flooring = $water_heater = $heating_system = $electrical = $plumbing_lines = $closets = $insulation = $exterior_finish = $windows = $type_of_building = $style = $roof_material = $roofing_condition = $basement = $interior_finish = $construction = $foundation =  '';

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $windows = $row['windows'];
    $style = $row['style'];
    $basement = $row['basement'];
    $interior_finish = $row['interior_finish'];
    $construction = $row['construction'];
    $foundation = $row['foundation'];
    $insulation = $row['insulation'];
    $closets = $row['closets'];
    $plumbing_lines = $row['plumbing_lines'];
    $electrical = $row['electrical'];
    $heating_system = $row['heating_system'];
    $water_heater = $row['water_heater'];
    $flooring = $row['flooring'];
    $floor_plan = $row['floor_plan'];
    $counter_tops = $row['counter_tops'];
    $built_ins_extras = $row['built_ins_extras'];
    $overall_in_condition = $row['overall_in_condition'];
    $under_construction_or_renovation = $row['under_construction_or_renovation'];
} else {
    echo "Error: Inspección no encontrada.";
    exit;
}

// Nueva consulta para verificar photo_address y photo_address_bsmt en tbl_direccion_room_allocation
$query = "SELECT photo_address, photo_address_bsmt FROM tbl_direccion_room_allocation WHERE id_inspeccion = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$inspectionId]);

$photo_address_exists = false;
$photo_address_bsmt_exists = false;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (!empty($row['photo_address'])) {
        $photo_address_exists = true;
    }
    if (!empty($row['photo_address_bsmt'])) {
        $photo_address_bsmt_exists = true;
    }
}

// Consulta para verificar basement_levels y basement_finished
$query = "SELECT basement_levels, basement_finished FROM tb_interior_inspecciones WHERE id_inspeccion = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$inspectionId]);

$showBasementRoomAllocation = false;

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $basement_levels = $row['basement_levels'];
    $basement_finished = $row['basement_finished'];

    if (!empty($basement_levels) && $basement_finished > 0) {
        $showBasementRoomAllocation = true;
    }
}

$query = "SELECT damage FROM tb_exterior_inspecciones WHERE id_inspeccion = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$inspectionId]);

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $damage = $row['damage'];
} else {
    echo "Error: Inspección no encontrada en tb_exterior_inspecciones.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection</title>
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
            <h1 class="text-xl font-semibold text-center text-gray-800">Interior</h1>
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
        <!-- Los comentados estan sin trabajar -->
        <div class="mt-8 space-y-4">
            <?php
            $checkIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#10B981" class="bi bi-check-circle-fill ml-2" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08 0L7.47 8.47l-2.24-2.24a.75.75 0 0 0-1.06 1.06l2.75 2.75c.3.3.77.3 1.06 0l4.5-4.5a.75.75 0 0 0 0-1.06z"/></svg>';
            $incompleteIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#FF0000" class="bi bi-x-circle-fill ml-2" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-4.646-4.646a.5.5 0 0 0-.708 0L8 6.293 4.854 3.146a.5.5 0 1 0-.708.708L7.293 7 3.146 10.854a.5.5 0 0 0 .708.708L8 7.707l3.146 3.147a.5.5 0 0 0 .708-.708L8.707 7l3.147-3.146a.5.5 0 0 0 0-.708z"/></svg>';

            echo '<a href="interior_overview_style.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . ($style ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Style' . ($style ? $checkIcon : $incompleteIcon) . '</a>';
            echo '<a href="interior_overview_bsmt.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . ($basement ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Basement' . ($basement ? $checkIcon : $incompleteIcon) . '</a>';
            
            if ($showBasementRoomAllocation) {
                echo '<a href="interior_overview_room_bsmt.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . ($photo_address_bsmt_exists ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Basement Room Allocation' . ($photo_address_bsmt_exists ? $checkIcon : $incompleteIcon) . '</a>';
            }

            echo '<a href="interior_overview_room.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . ($photo_address_exists ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Room Allocation' . ($photo_address_exists ? $checkIcon : $incompleteIcon) . '</a>';
            echo '<a href="interior_overview_interior.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . ($interior_finish ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Interior Finish' . ($interior_finish ? $checkIcon : $incompleteIcon) . '</a>';
            echo '<a href="interior_overview_construction.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . ($construction ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Construction' . ($construction ? $checkIcon : $incompleteIcon) . '</a>';
            echo '<a href="interior_overview_foundation.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . ($foundation ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Foundation' . ($foundation ? $checkIcon : $incompleteIcon) . '</a>';
            echo '<a href="interior_overview_windows.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . ($windows ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Windows' . ($windows ? $checkIcon : $incompleteIcon) . '</a>';
            echo '<a href="interior_overview_insulation.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . ($insulation ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Insulation' . ($insulation ? $checkIcon : $incompleteIcon) . '</a>';
            echo '<a href="interior_overview_closets.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . ($closets ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Closets Condition' . ($closets ? $checkIcon : $incompleteIcon) . '</a>';
            echo '<a href="interior_overview_plulin.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . ($plumbing_lines ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Plumbing Lines' . ($plumbing_lines ? $checkIcon : $incompleteIcon) . '</a>';
            echo '<a href="interior_overview_electrical.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . ($electrical ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Electrical' . ($electrical ? $checkIcon : $incompleteIcon) . '</a>';
            echo '<a href="interior_overview_heasys.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . ($heating_system ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Heating System' . ($heating_system ? $checkIcon : $incompleteIcon) . '</a>';
            echo '<a href="interior_overview_wathea.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . ($water_heater ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Water Heater' . ($water_heater ? $checkIcon : $incompleteIcon) . '</a>';
            echo '<a href="interior_overview_flooring.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . ($flooring ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Flooring' . ($flooring ? $checkIcon : $incompleteIcon) . '</a>';
            echo '<a href="interior_overview_floor_plan.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . ($floor_plan ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Floor Plan' . ($floor_plan ? $checkIcon : $incompleteIcon) . '</a>';
            echo '<a href="interior_overview_counter_tops.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . ($counter_tops ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Counter Tops' . ($counter_tops ? $checkIcon : $incompleteIcon) . '</a>';
            echo '<a href="interior_overview_built_ins_extras.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . ($built_ins_extras ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Built-ins/Extras' . ($built_ins_extras ? $checkIcon : $incompleteIcon) . '</a>';
            echo '<a href="interior_overview_overcon.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . ($overall_in_condition ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Overall Interior Condition' . ($overall_in_condition ? $checkIcon : $incompleteIcon) . '</a>';
            echo '<a href="interior_overview_under_conren.php?id=' . htmlspecialchars(urlencode($inspectionId)) . '" class="flex h-10 items-center justify-between rounded-md border border-input ' . ($under_construction_or_renovation ? 'bg-blue-100' : 'bg-background') . ' px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full">' . 'Under construction or renovation' . ($under_construction_or_renovation ? $checkIcon : $incompleteIcon) . '</a>';
            ?>
        </div>
    </div>
</body>

</html>
