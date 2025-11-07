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
    die('User not found.');
}

if (isset($_GET['id'])) {
    $inspectionId = $_GET['id'];
} else {
    echo "Error: Inspection ID not provided.";
    exit;
}

$query = "SELECT 
    vacant_property, 
    consent_photos, 
    consent_photos_exception, 
    exception_details, 
    occupant_name, 
    occupant_type, 
    date, 
    signature_requested 
    FROM tbl_consents WHERE id_inspeccion = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$inspectionId]);

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $vacant_property = $row['vacant_property'];
    $consent_photos = $row['consent_photos'];
    $consent_photos_exception = $row['consent_photos_exception'];
    $exception_details = str_replace('"', '', $row['exception_details']);
    $occupant_name = str_replace('"', '', $row['occupant_name']);
    $occupant_type = str_replace('"', '', $row['occupant_type']);
    $date = str_replace('"', '', $row['date']);
    $signature_requested = $row['signature_requested'];
} else {
    die('Inspection data not found.');
}

$query = "SELECT 
    exterior_finish, 
    type_of_building, 
    roof, 
    garage, 
    damage 
    FROM tb_exterior_inspecciones WHERE id_inspeccion = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$inspectionId]);

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $exterior_finish = str_replace('"', '', $row['exterior_finish']);
    $type_of_building = str_replace('"', '', $row['type_of_building']);
    $roof = str_replace('"', '', $row['roof']);
    $garage = str_replace('"', '', $row['garage']);
    $damage = str_replace('"', '', $row['damage']);
} else {
    die('Exterior inspection data not found.');
}

$query = "SELECT 
    windows, 
    style, 
    interior_finish, 
    construction, 
    foundation, 
    insulation, 
    closets, 
    plumbing_lines, 
    electrical, 
    heating_system, 
    water_heater, 
    flooring, 
    floor_plan, 
    counter_tops, 
    built_ins_extras, 
    overall_in_condition, 
    under_construction_or_renovation,
    basement,
    basement_levels,
    basement_separate_entrace,
    basement_use,
    basement_area,
    basement_finished 
    FROM tb_interior_inspecciones WHERE id_inspeccion = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$inspectionId]);

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $windows = str_replace('"', '', $row['windows']);
    $style = str_replace('"', '', $row['style']);
    $interior_finish = str_replace('"', '', $row['interior_finish']);
    $construction = str_replace('"', '', $row['construction']);
    $foundation = str_replace('"', '', $row['foundation']);
    $insulation = str_replace('"', '', $row['insulation']);
    $closets = str_replace('"', '', $row['closets']);
    $plumbing_lines = str_replace('"', '', $row['plumbing_lines']);
    $electrical = str_replace('"', '', $row['electrical']);
    $heating_system = str_replace('"', '', $row['heating_system']);
    $water_heater = str_replace('"', '', $row['water_heater']);
    $flooring = str_replace('"', '', $row['flooring']);
    $floor_plan = str_replace('"', '', $row['floor_plan']);
    $counter_tops = str_replace('"', '', $row['counter_tops']);
    $built_ins_extras = str_replace('"', '', $row['built_ins_extras']);
    $overall_in_condition = str_replace('"', '', $row['overall_in_condition']);
    $under_construction_or_renovation = str_replace('"', '', $row['under_construction_or_renovation']);
    $basement = str_replace('"', '', $row['basement']);
    $basement_levels = (int) $row['basement_levels'];
    $basement_separate_entrace = str_replace('"', '', $row['basement_separate_entrace']);
    $basement_use = str_replace('"', '', $row['basement_use']);
    $basement_area = (int) $row['basement_area'];
    $basement_finished = (int) $row['basement_finished'];
} else {
    die('Interior inspection data not found.');
}

$query = "SELECT 
    photo_address, 
    photo_address_bsmt 
    FROM tbl_direccion_room_allocation WHERE id_inspeccion = ?";
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

$query = "SELECT 
    photo_address, 
    photo_address_bsmt 
    FROM tbl_direccion_room_allocation WHERE id_inspeccion = ?";
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

$query = "SELECT 
    driveway,
    select_option_driveway, 
    parking, 
    electrical, 
    utilities, 
    features, 
    curb_appeal, 
    topography, 
    landscaping, 
    site_improvements, 
    site_features 
    FROM tb_site_inspecciones WHERE id_inspeccion = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$inspectionId]);

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $driveway = str_replace('"', '', $row['driveway']);
    $select_option_driveway = str_replace('"', '', $row['select_option_driveway']);
    $parking = str_replace('"', '', $row['parking']);
    $electrical_site = str_replace('"', '', $row['electrical']);
    $utilities = str_replace('"', '', $row['utilities']);
    $features = str_replace('"', '', $row['features']);
    $curb_appeal = str_replace('"', '', $row['curb_appeal']);
    $topography = str_replace('"', '', $row['topography']);
    $landscaping = str_replace('"', '', $row['landscaping']);
    $site_improvements = str_replace('"', '', $row['site_improvements']);
    $site_features = str_replace('"', '', $row['site_features']);
} else {
    die('Site inspection data not found.');
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['signature'])) {
        $signature = $_POST['signature'];
        $signature = str_replace('data:image/png;base64,', '', $signature);
        $signature = str_replace(' ', '+', $signature);
        $data = base64_decode($signature);
        $inspectionDate = date("Y-m-d");
        $signaturePath = "../img/photo_gallery/$inspectionId/Certification_signature/Signature_of_" . preg_replace("/[^a-zA-Z0-9]+/", "_", $nombre_usuario) . ".jpg";

        if (!is_dir(dirname($signaturePath))) {
            mkdir(dirname($signaturePath), 0777, true);
        }

        $image = imagecreatefromstring($data);
        $bg = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefilledrectangle($bg, 0, 0, imagesx($image), imagesy($image), imagecolorallocate($bg, 255, 255, 255));
        imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imagedestroy($image);

        $logoPath = '../img/logo.png';
        $logo = imagecreatefrompng($logoPath);

        $logoWidth = imagesx($logo);
        $logoHeight = imagesy($logo);
        $bgWidth = imagesx($bg);
        $bgHeight = imagesy($bg);

        $logoX = $bgWidth - $logoWidth - 10; 
        $logoY = $bgHeight - $logoHeight - 10; 

        imagecopy($bg, $logo, $logoX, $logoY, 0, 0, $logoWidth, $logoHeight);
        imagedestroy($logo);

        $black = imagecolorallocate($bg, 0, 0, 0);
        $fontPath = '../fonts/arial.ttf'; 
        $fontSize = 20;

        imagettftext($bg, $fontSize, 0, 10, 50, $black, $fontPath, "Inspection Date: $inspectionDate");
        imagettftext($bg, $fontSize, 0, 10, 100, $black, $fontPath, "Inspector Name: $nombre_usuario");

        imagejpeg($bg, $signaturePath);
        imagedestroy($bg);

        echo "Signature saved successfully.";
        exit;
    }

}
