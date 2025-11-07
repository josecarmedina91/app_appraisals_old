<?php
session_start();

require '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

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
    die('User not found.');
}

if (isset($_GET['id'])) {
    $inspectionId = $_GET['id'];
} else {
    echo "Error: Inspection ID not provided.";
    exit;
}

$query = "SELECT direccion_propiedad FROM tb_inspecciones WHERE id_inspeccion = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$inspectionId]);

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $direccion_propiedad = $row['direccion_propiedad'];
} else {
    die('Property address not found.');
}

$encabezado = "Inspection Report - " . $direccion_propiedad;

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'OpenSansRegular');
$options->set('isFontSubsettingEnabled', true);

$dompdf = new Dompdf($options);

$imagePath = '../img/logo.png';
$imageData = base64_encode(file_get_contents($imagePath));
$src = 'data:image/png;base64,' . $imageData;

$query = "SELECT 
    vacant_property, 
    consent_photos, 
    consent_photos_exception, 
    exception_details, 
    occupant_name, 
    occupant_type, 
    date
    FROM tbl_consents WHERE id_inspeccion = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$inspectionId]);

$inspectionData = $stmt->fetch(PDO::FETCH_ASSOC);

$labels = [
    'vacant_property' => 'Vacant Property',
    'consent_photos' => 'Consent Photos',
    'consent_photos_exception' => 'Consent Photos Exception',
    'exception_details' => 'Exception Details',
    'occupant_name' => 'Occupant Name',
    'occupant_type' => 'Occupant Type',
    'date' => 'Inspection Date'
];

$occupantName = isset($inspectionData['occupant_name']) ? $inspectionData['occupant_name'] : '';
$signatureFileName = 'Signature_of_' . str_replace(' ', '_', $occupantName) . '.jpg';
$signaturePath = "../img/photo_gallery/$inspectionId/Photo_Consent_Form/$signatureFileName";
$signatureData = null;

if (file_exists($signaturePath)) {
    $signatureData = base64_encode(file_get_contents($signaturePath));
}

$floorAreaSketchDir = "../img/photo_gallery/$inspectionId/Floor_area_sketch";
$floorAreaSketchImages = glob($floorAreaSketchDir . '/*.jpg');

$exteriorInspectionDir = "../img/photo_gallery/$inspectionId/Exterior_Inspection_Details";
$exteriorInspectionImages = glob($exteriorInspectionDir . '/*.jpg');

$query = "SELECT 
    exterior_finish, 
    notas_ext_fin,
    type_of_building, 
    notas_ext_type_of_bu,
    roof, 
    notas_ext_roof,
    garage, 
    notas_ext_garage,
    damage,
    notas_ext_damage
    FROM tb_exterior_inspecciones WHERE id_inspeccion = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$inspectionId]);

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $exterior_finish = str_replace('"', '', $row['exterior_finish']);
    $notas_ext_fin = str_replace('"', '', $row['notas_ext_fin']);
    $type_of_building = str_replace('"', '', $row['type_of_building']);
    $notas_ext_type_of_bu = str_replace('"', '', $row['notas_ext_type_of_bu']);
    $roof = str_replace('"', '', $row['roof']);
    $notas_ext_roof = str_replace('"', '', $row['notas_ext_roof']);
    $garage = str_replace('"', '', $row['garage']);
    $notas_ext_garage = str_replace('"', '', $row['notas_ext_garage']);
    $damage = str_replace('"', '', $row['damage']);
    $notas_ext_damage = str_replace('"', '', $row['notas_ext_damage']);
} else {
    die('Exterior inspection data not found.');
}

// Consulta original que obtiene todos los datos de tb_interior_inspecciones excepto under_construction_or_renovation
$query = "SELECT 
    windows,
    notas_int_win, 
    style,
    notas_int_style, 
    interior_finish,
    notas_int_interior_finish, 
    construction, 
    notas_int_construction,
    foundation, 
    notas_int_foundation,
    insulation, 
    notas_int_insulation,
    closets, 
    notas_int_closets,
    plumbing_lines, 
    notas_int_plumbing_lines,
    electrical, 
    notas_int_electrical,
    heating_system, 
    notas_int_heating_system,
    water_heater, 
    notas_int_water_heater,
    flooring, 
    notas_int_flooring,
    floor_plan, 
    notas_int_floor_plan,
    counter_tops, 
    notas_int_counter_tops,
    built_ins_extras, 
    notas_int_built_ins_extras,
    overall_in_condition, 
    notas_int_overall_in_condition,
    basement,
    notas_int_basement,
    basement_levels,
    basement_separate_entrace,
    basement_use,
    basement_area,
    basement_finished,
    notas_int_under_construction_or_renovation 
    FROM tb_interior_inspecciones WHERE id_inspeccion = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$inspectionId]);

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $windows = str_replace('"', '', $row['windows']);
    $notas_int_win = str_replace('"', '', $row['notas_int_win']);
    $style = str_replace('"', '', $row['style']);
    $notas_int_style = str_replace('"', '', $row['notas_int_style']);
    $interior_finish = str_replace('"', '', $row['interior_finish']);
    $notas_int_interior_finish = str_replace('"', '', $row['notas_int_interior_finish']);
    $construction = str_replace('"', '', $row['construction']);
    $notas_int_construction = str_replace('"', '', $row['notas_int_construction']);
    $foundation = str_replace('"', '', $row['foundation']);
    $notas_int_foundation = str_replace('"', '', $row['notas_int_foundation']);
    $insulation = str_replace('"', '', $row['insulation']);
    $notas_int_insulation = str_replace('"', '', $row['notas_int_insulation']);
    $closets = str_replace('"', '', $row['closets']);
    $notas_int_closets = str_replace('"', '', $row['notas_int_closets']);
    $plumbing_lines = str_replace('"', '', $row['plumbing_lines']);
    $notas_int_plumbing_lines = str_replace('"', '', $row['notas_int_plumbing_lines']);
    $electrical = str_replace('"', '', $row['electrical']);
    $notas_int_electrical = str_replace('"', '', $row['notas_int_electrical']);
    $heating_system = str_replace('"', '', $row['heating_system']);
    $notas_int_heating_system = str_replace('"', '', $row['notas_int_heating_system']);
    $water_heater = str_replace('"', '', $row['water_heater']);
    $notas_int_water_heater = str_replace('"', '', $row['notas_int_water_heater']);
    $flooring = str_replace('"', '', $row['flooring']);
    $notas_int_flooring = str_replace('"', '', $row['notas_int_flooring']);
    $floor_plan = str_replace('"', '', $row['floor_plan']);
    $notas_int_floor_plan = str_replace('"', '', $row['notas_int_floor_plan']);
    $counter_tops = str_replace('"', '', $row['counter_tops']);
    $notas_int_counter_tops = str_replace('"', '', $row['notas_int_counter_tops']);
    $built_ins_extras = str_replace('"', '', $row['built_ins_extras']);
    $notas_int_built_ins_extras = str_replace('"', '', $row['notas_int_built_ins_extras']);
    $overall_in_condition = str_replace('"', '', $row['overall_in_condition']);
    $notas_int_overall_in_condition = str_replace('"', '', $row['notas_int_overall_in_condition']);
    $basement = str_replace('"', '', $row['basement']);
    $notas_int_basement = str_replace('"', '', $row['notas_int_basement']);
    $basement_levels = (int) $row['basement_levels'];
    $basement_separate_entrace = str_replace('"', '', $row['basement_separate_entrace']);
    $basement_use = str_replace('"', '', $row['basement_use']);
    $basement_area = (int) $row['basement_area'];
    $basement_finished = (int) $row['basement_finished'];
    $notas_int_under_construction_or_renovation = str_replace('"', '', $row['notas_int_under_construction_or_renovation']);
} else {
    die('Interior inspection data not found.');
}

$query_uc = "SELECT 
    under_construction_or_renovation
    FROM vt_interior_underconstruction WHERE id_inspeccion = ?";
$stmt_uc = $pdo->prepare($query_uc);
$stmt_uc->execute([$inspectionId]);

if ($row_uc = $stmt_uc->fetch(PDO::FETCH_ASSOC)) {
    $under_construction_or_renovation = str_replace('"', '', $row_uc['under_construction_or_renovation']);
} else {
    die('Under construction or renovation data not found.');
}

$query = "SELECT 
    driveway,
    notas_site_driveway,
    select_option_driveway, 
    parking,
    notas_site_parking,
    electrical, 
    notas_site_electrical,
    utilities, 
    notas_site_utilities,
    features, 
    notas_site_features,
    curb_appeal, 
    notas_site_curb_appeal,
    topography, 
    notas_site_topography,
    landscaping, 
    notas_site_landscaping,
    site_improvements, 
    notas_site_site_improvements,
    site_features, 
    notas_site_site_features
    FROM tb_site_inspecciones WHERE id_inspeccion = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$inspectionId]);

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $driveway = str_replace('"', '', $row['driveway']);
    $notas_site_driveway = str_replace('"', '', $row['notas_site_driveway']);
    $select_option_driveway = str_replace('"', '', $row['select_option_driveway']);
    $parking = str_replace('"', '', $row['parking']);
    $notas_site_parking = str_replace('"', '', $row['notas_site_parking']);
    $electrical_site = str_replace('"', '', $row['electrical']);
    $notas_site_electrical = str_replace('"', '', $row['notas_site_electrical']);
    $utilities = str_replace('"', '', $row['utilities']);
    $notas_site_utilities = str_replace('"', '', $row['notas_site_utilities']);
    $features = str_replace('"', '', $row['features']);
    $notas_site_features = str_replace('"', '', $row['notas_site_features']);
    $curb_appeal = str_replace('"', '', $row['curb_appeal']);
    $notas_site_curb_appeal = str_replace('"', '', $row['notas_site_curb_appeal']);
    $topography = str_replace('"', '', $row['topography']);
    $notas_site_topography = str_replace('"', '', $row['notas_site_topography']);
    $landscaping = str_replace('"', '', $row['landscaping']);
    $notas_site_landscaping = str_replace('"', '', $row['notas_site_landscaping']);
    $site_improvements = str_replace('"', '', $row['site_improvements']);
    $notas_site_site_improvements = str_replace('"', '', $row['notas_site_site_improvements']);
    $site_features = str_replace('"', '', $row['site_features']);
    $notas_site_site_features = str_replace('"', '', $row['notas_site_site_features']);
} else {
    die('Site inspection data not found.');
}

$query = "SELECT photo_address, photo_address_bsmt FROM tbl_direccion_room_allocation WHERE id_inspeccion = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$inspectionId]);

$room_allocations = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $room_allocation = getRelevantPart($row['photo_address']);
    $basement_room_allocation = getRelevantPart($row['photo_address_bsmt']);
    $room_allocations[] = [
        'photo_address' => $room_allocation,
        'photo_address_bsmt' => $basement_room_allocation
    ];
}

function getRelevantPart($address)
{
    $parts = explode('/', $address);
    if (count($parts) > 4) {
        $relevantPart = $parts[4];
        $underscorePos = strpos($relevantPart, '_');
        if ($underscorePos !== false) {
            return substr($relevantPart, $underscorePos + 1);
        }
        return $relevantPart;
    }
    return $address;
}

$room_allocation = '';
$basement_room_allocation = '';
if (!empty($room_allocations)) {
    foreach ($room_allocations as $allocation) {
        $room_allocation .= $allocation['photo_address'] . "\n";
        $basement_room_allocation .= $allocation['photo_address_bsmt'] . "\n";
    }
    $room_allocation = trim($room_allocation);
    $basement_room_allocation = trim($basement_room_allocation);
} else {
    die('Room allocation data not found.');
}

function parseRoomAllocations($allocations)
{
    $parsed = [];
    foreach ($allocations as $allocation) {
        if (!empty($allocation)) {
            list($level, $roomInfo) = explode('_', $allocation);
            preg_match('/(\D+)(\d+)/', $roomInfo, $matches);
            $roomType = $matches[1];
            $roomNumber = (int)$matches[2];

            if (!isset($parsed[$level])) {
                $parsed[$level] = [];
            }

            if (!isset($parsed[$level][$roomType]) || $parsed[$level][$roomType] < $roomNumber) {
                $parsed[$level][$roomType] = $roomNumber;
            }
        }
    }
    return $parsed;
}

$room_allocations = parseRoomAllocations(explode("\n", $room_allocation));
$basement_room_allocations = parseRoomAllocations(explode("\n", $basement_room_allocation));

$html = '
<html>
<head>
    <style>
        @font-face {
            font-family: "OpenSansCondensedBold";
            src: url("../fonts/OpenSans_Condensed-Bold.ttf") format("truetype");
            font-weight: bold;
        }
        @font-face {
            font-family: "OpenSansRegular";
            src: url("../fonts/OpenSans-Regular.ttf") format("truetype");
            font-weight: normal;
        }
        body {
            font-family: "OpenSansRegular", Arial, sans-serif;
            margin: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .title {
            font-family: "OpenSansCondensedBold";
            font-size: 24px;
            font-weight: bold;
        }
        .logo img {
            width: 250px;
            margin-bottom: 20px;
        }
        .section-title {
            font-family: "OpenSansCondensedBold";
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin-top: 40px;
            text-decoration: underline;
        }
        .section-subtitle {
            font-family: "OpenSansCondensedBold";
            font-size: 16px;
            font-weight: bold;
            text-align: left;
            margin-top: 20px;
            margin-bottom: 10px;
            text-decoration: underline;
        }
        .content {
            display: flex;
            justify-content: space-between;
        }
        .data-section {
            flex: 1;
            margin-top: 10px;
        }
        .data-item {
            width: 45%;
            margin-bottom: 10px;
        }
        .data-item span {
            font-family: "OpenSansCondensedBold";
            font-weight: bold;
            display: block;
            font-size: 14px;
        }
        .data-item div {
            font-size: 12px;
            padding: 5px;
            border-radius: 5px;
            margin-bottom: 10px; 
        }
        .signature-section {
            margin-top: 20px; 
            width: 100%; 
            text-align: center;
        }
        .signature-section img {
            width: 300px; 
            height: auto; 
        }
        .signature-section span {
            font-size: 14px; 
            display: block;
            margin-top: 5px; 
        }        
        .floor-area-sketch-page, .exterior-inspection-page, .interior-inspection-page, .site-inspection-page {
            page-break-before: always;
        }
        .floor-area-sketch, .exterior-inspection, .interior-inspection, .site-inspection {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-top: 20px;
            gap: 10px;
        }
        .floor-area-sketch .image, .exterior-inspection .image, .interior-inspection .image, .site-inspection .image {
            width: calc(45% - 20px);
            box-sizing: border-box;
            padding: 5px;
            background-color: #f2f2f2;
            border-radius: 5px;
        }
        .floor-area-sketch img, .exterior-inspection img, .interior-inspection img, .site-inspection img {
            width: 100%;
            height: auto;
            border-radius: 5px;
        }
        .floor-area-sketch .title, .exterior-inspection .title, .interior-inspection .title, .site-inspection .title {
            text-align: center;
            font-size: 14px;
            margin-top: 5px;
        }
        .exterior-details, .interior-details, .site-details {
            margin-top: 20px;
        }
        .exterior-details .data-item, .interior-details .data-item, .site-details .data-item {
            width: 100%;
            margin-bottom: 10px;
        }
        .exterior-details .data-item div.notes, .interior-details .data-item div.notes, .site-details .data-item div.notes {
            padding-bottom: 10px; 
            margin-bottom: 40px;
        }
        .certification-image img {
            width: 400px;
            height: auto;
        }
        .certification-image {
            text-align: center;
            margin-top: 20px;
        }
        .page-break {
            page-break-before: always;
        }
        .certification-page {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <img src="' . $src . '" alt="Logo de la compañía">
        </div>
        <div class="title">' . $encabezado . '</div>
    </div>
    <div class="section-title">Photo Consent Form:</div>
    <div class="content">
        <div class="data-section">';

foreach ($labels as $key => $label) {
    $value = isset($inspectionData[$key]) ? htmlspecialchars($inspectionData[$key]) : '';
    if (is_numeric($value)) {
        $value = $value == 1 ? 'Yes' : ($value == 0 ? 'No' : $value);
    }
    $html .= '<div class="data-item"><span>' . $label . ':</span> <div>' . $value . '</div></div>';
}

$html .= '
        </div>';

if ($signatureData) {
    $html .= '
        </div> <!-- Close the content div -->
        <div class="signature-section">
            <img src="data:image/jpeg;base64,' . $signatureData . '" alt="Signature">
            <span>Signature: ' . htmlspecialchars($occupantName) . '</span>
        </div>';
}

$html .= '
    </div>
    <div class="section-subtitle floor-area-sketch-page">Floor area sketch:</div>
    <div class="floor-area-sketch">';

foreach ($floorAreaSketchImages as $imagePath) {
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageName = basename($imagePath);
    $html .= '
        <div class="image">
            <img src="data:image/jpeg;base64,' . $imageData . '" alt="Floor area sketch">
            <div class="title">' . htmlspecialchars($imageName) . '</div>
        </div>';
}

$html .= '
    </div>
    <div class="section-subtitle exterior-inspection-page">Exterior Inspection Details:</div>
    <div class="exterior-details">
        <div class="data-item"><span>Exterior Finish:</span> <div>' . (empty($exterior_finish) ? '' : htmlspecialchars($exterior_finish)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_ext_fin) ? '' : htmlspecialchars($notas_ext_fin)) . '</div></div>
        <div class="data-item"><span>Type of building:</span> <div>' . (empty($type_of_building) ? '' : htmlspecialchars($type_of_building)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_ext_type_of_bu) ? '' : htmlspecialchars($notas_ext_type_of_bu)) . '</div></div>
        <div class="data-item"><span>Roof:</span> <div>' . (empty($roof) ? '' : htmlspecialchars($roof)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_ext_roof) ? '' : htmlspecialchars($notas_ext_roof)) . '</div></div>
        <div class="data-item"><span>Garage:</span> <div>' . (empty($garage) ? '' : htmlspecialchars($garage)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_ext_garage) ? '' : htmlspecialchars($notas_ext_garage)) . '</div></div>
        <div class="data-item"><span>Damage:</span> <div>' . (empty($damage) ? '' : htmlspecialchars($damage)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_ext_damage) ? '' : htmlspecialchars($notas_ext_damage)) . '</div></div>
    </div>
    <div class="exterior-inspection">';

foreach ($exteriorInspectionImages as $imagePath) {
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageName = basename($imagePath);
    $html .= '
        <div class="image">
            <img src="data:image/jpeg;base64,' . $imageData . '" alt="Exterior Inspection Details">
            <div class="title">' . htmlspecialchars($imageName) . '</div>';
}

$html .= '
    </div>
    <div class="section-subtitle interior-inspection-page">Interior Inspection Details:</div>
    <div class="interior-details">
        <div class="data-item"><span>Windows:</span> <div>' . (empty($windows) ? '' : htmlspecialchars($windows)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_int_win) ? '' : htmlspecialchars($notas_int_win)) . '</div></div>
        <div class="data-item"><span>Style:</span> <div>' . (empty($style) ? '' : htmlspecialchars($style)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_int_style) ? '' : htmlspecialchars($notas_int_style)) . '</div></div>
        <div class="data-item"><span>Interior Finish:</span> <div>' . (empty($interior_finish) ? '' : htmlspecialchars($interior_finish)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_int_interior_finish) ? '' : htmlspecialchars($notas_int_interior_finish)) . '</div></div>
        <div class="data-item"><span>Construction:</span> <div>' . (empty($construction) ? '' : htmlspecialchars($construction)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_int_construction) ? '' : htmlspecialchars($notas_int_construction)) . '</div></div>
        <div class="data-item"><span>Foundation:</span> <div>' . (empty($foundation) ? '' : htmlspecialchars($foundation)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_int_foundation) ? '' : htmlspecialchars($notas_int_foundation)) . '</div></div>
        <div class="data-item"><span>Insulation:</span> <div>' . (empty($insulation) ? '' : htmlspecialchars($insulation)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_int_insulation) ? '' : htmlspecialchars($notas_int_insulation)) . '</div></div>
        <div class="data-item"><span>Closets:</span> <div>' . (empty($closets) ? '' : htmlspecialchars($closets)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_int_closets) ? '' : htmlspecialchars($notas_int_closets)) . '</div></div>
        <div class="data-item"><span>Plumbing Lines:</span> <div>' . (empty($plumbing_lines) ? '' : htmlspecialchars($plumbing_lines)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_int_plumbing_lines) ? '' : htmlspecialchars($notas_int_plumbing_lines)) . '</div></div>
        <div class="data-item"><span>Electrical:</span> <div>' . (empty($electrical) ? '' : htmlspecialchars($electrical)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_int_electrical) ? '' : htmlspecialchars($notas_int_electrical)) . '</div></div>
        <div class="data-item"><span>Heating System:</span> <div>' . (empty($heating_system) ? '' : htmlspecialchars($heating_system)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_int_heating_system) ? '' : htmlspecialchars($notas_int_heating_system)) . '</div></div>
        <div class="data-item"><span>Water Heater:</span> <div>' . (empty($water_heater) ? '' : htmlspecialchars($water_heater)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_int_water_heater) ? '' : htmlspecialchars($notas_int_water_heater)) . '</div></div>
        <div class="data-item"><span>Flooring:</span> <div>' . (empty($flooring) ? '' : htmlspecialchars($flooring)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_int_flooring) ? '' : htmlspecialchars($notas_int_flooring)) . '</div></div>
        <div class="data-item"><span>Floor Plan:</span> <div>' . (empty($floor_plan) ? '' : htmlspecialchars($floor_plan)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_int_floor_plan) ? '' : htmlspecialchars($notas_int_floor_plan)) . '</div></div>
        <div class="data-item"><span>Counter Tops:</span> <div>' . (empty($counter_tops) ? '' : htmlspecialchars($counter_tops)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_int_counter_tops) ? '' : htmlspecialchars($notas_int_counter_tops)) . '</div></div>
        <div class="data-item"><span>Built-ins & Extras:</span> <div>' . (empty($built_ins_extras) ? '' : htmlspecialchars($built_ins_extras)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_int_built_ins_extras) ? '' : htmlspecialchars($notas_int_built_ins_extras)) . '</div></div>
        <div class="data-item"><span>Overall Interior Condition:</span> <div>' . (empty($overall_in_condition) ? '' : htmlspecialchars($overall_in_condition)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_int_overall_in_condition) ? '' : htmlspecialchars($notas_int_overall_in_condition)) . '</div></div>
        <div class="data-item"><span>Under Construction or Renovation:</span> <pre>' . (empty($under_construction_or_renovation) ? '' : htmlspecialchars($under_construction_or_renovation)) . '</pre></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_int_under_construction_or_renovation) ? '' : htmlspecialchars($notas_int_under_construction_or_renovation)) . '</div></div>
        <div class="data-item"><span>Basement:</span> <div>' . (empty($basement) ? '' : htmlspecialchars($basement)) . '</div></div>
        <div class="data-item"><span>Basement Levels:</span> <div>' . (empty($basement_levels) ? '' : htmlspecialchars($basement_levels)) . '</div></div>
        <div class="data-item"><span>Basement Separate Entrance:</span> <div>' . (empty($basement_separate_entrace) ? '' : htmlspecialchars($basement_separate_entrace)) . '</div></div>
        <div class="data-item"><span>Basement Use:</span> <div>' . (empty($basement_use) ? '' : htmlspecialchars($basement_use)) . '</div></div>
        <div class="data-item"><span>Basement Area:</span> <div>' . (empty($basement_area) ? '' : htmlspecialchars($basement_area)) . " %" . '</div></div>
        <div class="data-item"><span>Basement Finished:</span> <div>' . (empty($basement_finished) ? '' : htmlspecialchars($basement_finished)) . " %" . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_int_basement) ? '' : htmlspecialchars($notas_int_basement)) . '</div></div>
    </div>
    <div class="interior-inspection">';

foreach ($exteriorInspectionImages as $imagePath) {
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageName = basename($imagePath);
    $html .= '
        <div class="image">
            <img src="data:image/jpeg;base64,' . $imageData . '" alt="Interior Inspection Details">
            <div class="title">' . htmlspecialchars($imageName) . '</div>';
}

$html .= '
    </div>
    <div class="section-subtitle page-break">Room Allocation</div>';

foreach ($room_allocations as $level => $rooms) {
    $html .= '<div class="mt-4">
        <h4 class="font-bold text-base">' . htmlspecialchars($level) . '</h4>
        <table class="min-w-full bg-white border border-gray-300 mt-2">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b border-gray-300 text-center border-r w-1/2">Room</th>
                    <th class="py-2 px-4 border-b border-gray-300 text-center">Number</th>
                </tr>
            </thead>
            <tbody>';
    foreach ($rooms as $room => $number) {
        $html .= '<tr>
            <td class="py-2 px-4 border-b border-gray-300 text-center">' . htmlspecialchars($room) . '</td>
            <td class="py-2 px-4 border-b border-gray-300 text-center">' . htmlspecialchars($number) . '</td>
        </tr>';
    }
    $html .= '</tbody>
        </table>
    </div>';
}

$html .= '<div class="section-subtitle">Basement Room Allocation</div>';

foreach ($basement_room_allocations as $level => $rooms) {
    $html .= '<div class="mt-4">
        <h4 class="font-bold text-base">' . htmlspecialchars($level) . '</h4>
        <table class="min-w-full bg-white border border-gray-300 mt-2">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b border-gray-300 text-center border-r w-1/2">Room</th>
                    <th class="py-2 px-4 border-b border-gray-300 text-center">Number</th>
                </tr>
            </thead>
            <tbody>';
    foreach ($rooms as $room => $number) {
        $html .= '<tr>
            <td class="py-2 px-4 border-b border-gray-300 text-center">' . htmlspecialchars($room) . '</td>
            <td class="py-2 px-4 border-b border-gray-300 text-center">' . htmlspecialchars($number) . '</td>
        </tr>';
    }
    $html .= '</tbody>
        </table>
    </div>';
}

$html .= '
    <div class="section-subtitle site-inspection-page">Site Inspection Details:</div>
    <div class="site-details">
        <div class="data-item"><span>Driveway:</span> <div>' . (empty($driveway) ? '' : htmlspecialchars($driveway)) . '</div></div>
        <div class="data-item"><span>Driveway Options:</span> <div>' . (empty($select_option_driveway) ? '' : htmlspecialchars($select_option_driveway)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_site_driveway) ? '' : htmlspecialchars($notas_site_driveway)) . '</div></div>
        <div class="data-item"><span>Parking:</span> <div>' . (empty($parking) ? '' : htmlspecialchars($parking)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_site_parking) ? '' : htmlspecialchars($notas_site_parking)) . '</div></div>
        <div class="data-item"><span>Electrical:</span> <div>' . (empty($electrical_site) ? '' : htmlspecialchars($electrical_site)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_site_electrical) ? '' : htmlspecialchars($notas_site_electrical)) . '</div></div>
        <div class="data-item"><span>Utilities:</span> <div>' . (empty($utilities) ? '' : htmlspecialchars($utilities)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_site_utilities) ? '' : htmlspecialchars($notas_site_utilities)) . '</div></div>
        <div class="data-item"><span>Features:</span> <div>' . (empty($features) ? '' : htmlspecialchars($features)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_site_features) ? '' : htmlspecialchars($notas_site_features)) . '</div></div>
        <div class="data-item"><span>Curb Appeal:</span> <div>' . (empty($curb_appeal) ? '' : htmlspecialchars($curb_appeal)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_site_curb_appeal) ? '' : htmlspecialchars($notas_site_curb_appeal)) . '</div></div>
        <div class="data-item"><span>Topography:</span> <div>' . (empty($topography) ? '' : htmlspecialchars($topography)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_site_topography) ? '' : htmlspecialchars($notas_site_topography)) . '</div></div>
        <div class="data-item"><span>Landscaping:</span> <div>' . (empty($landscaping) ? '' : htmlspecialchars($landscaping)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_site_landscaping) ? '' : htmlspecialchars($notas_site_landscaping)) . '</div></div>
        <div class="data-item"><span>Site Improvements:</span> <div>' . (empty($site_improvements) ? '' : htmlspecialchars($site_improvements)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_site_site_improvements) ? '' : htmlspecialchars($notas_site_site_improvements)) . '</div></div>
        <div class="data-item"><span>Site Features:</span> <div>' . (empty($site_features) ? '' : htmlspecialchars($site_features)) . '</div></div>
        <div class="data-item"><span>Notes:</span> <div class="notes">' . (empty($notas_site_site_features) ? '' : htmlspecialchars($notas_site_site_features)) . '</div></div>
    </div>
    <div class="site-inspection">';

foreach ($exteriorInspectionImages as $imagePath) {
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageName = basename($imagePath);
    $html .= '
        <div class="image">
            <img src="data:image/jpeg;base64,' . $imageData . '" alt="Site Inspection Details">
            <div class="title">' . htmlspecialchars($imageName) . '</div>
        </div>';
}

$html .= '
</div>
<div class="section-subtitle certification-page">Certification</div>
<div class="certification-section">';

$certificationDir = "../img/photo_gallery/$inspectionId/Certification_signature/";
$certificationFiles = glob($certificationDir . 'Signature_of_*.jpg');

$certificationData = null;

if (!empty($certificationFiles)) {
$certificationFilePath = $certificationFiles[0];
$certificationData = base64_encode(file_get_contents($certificationFilePath));
$html .= '
    <div class="certification-image">
        <img src="data:image/jpeg;base64,' . $certificationData . '" alt="Certification">
    </div>';
} else {
$html .= '<div class="data-item">Certification image not found.</div>';
}
$html .= '
</div>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$pdfOutput = $dompdf->output();

$filePath = '../export_pdf/' . $encabezado . '.pdf';
$result = file_put_contents($filePath, $pdfOutput);

if ($result === false) {
    die("Error: PDF could not be created.");
} else {
    header("Location: file_organizer_for_cloud.php?id=$inspectionId");
    exit;
}
?>
