<?php
session_start();

class Database {
    private $host = 'localhost';
    private $dbname = 'db_community_appraisals';
    private $username = 'root';
    private $password = '';
    public $pdo;

    public function __construct() {
        try {
            $this->pdo = new PDO("mysql:host=$this->host;dbname=$this->dbname", $this->username, $this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("ERROR: No se pudo conectar. " . $e->getMessage());
        }
    }
}

class Inspection {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getInspectionDetails($inspectionId) {
        $query = "SELECT *, DATE_FORMAT(fecha_programada, '%Y-%m-%d') AS fecha, DATE_FORMAT(fecha_programada, '%H:%i') AS hora FROM tb_inspecciones WHERE id_inspeccion = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$inspectionId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login_index.html');
    exit;
}

$inspectionId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$inspectionId) {
    die('Error: Inspection ID not provided.');
}

$db = new Database();
$inspection = new Inspection($db->pdo);
$inspectionDetails = $inspection->getInspectionDetails($inspectionId);

if (!$inspectionDetails) {
    die('Error: Inspection not found.');
}

$propertyAddress = $inspectionDetails['direccion_propiedad'];
$orderNumber = $inspectionDetails['numero_orden'];
$scheduledDate = $inspectionDetails['fecha'];
$scheduledTime = $inspectionDetails['hora'];
$serviceContact = $inspectionDetails['service_contact'];
$number = $inspectionDetails['number'];
$comments = htmlspecialchars_decode($inspectionDetails['comentarios']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Inspection</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body,
        html {
            height: 100%;
        }

        #login-container {
            min-height: 100%;
        }
    </style>
</head>

<body class="bg-gray-50 p-4 w-full h-screen">
    <div class="text-lg font-semibold mb-4 flex items-center gap-2">
        <button onclick="window.location.href='main_user.php'" style="background: none; border: none; padding: 0; cursor: pointer;">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                <path d="m12 19-7-7 7-7"></path>
                <path d="M19 12H5"></path>
            </svg>
        </button>
        <h1 class="text-xl font-semibold">Property Information</h1>
    </div>
    <div>
        <div class="space-y-4 mt-8">
            <div>
                <label for="property-address" class="flex items-center gap-2 text-base font-medium text-gray-700"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>Property Address</label>
                <input type="text" id="property-address" value="<?php echo htmlspecialchars($propertyAddress); ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-base" readonly />
            </div>
            <div>
                <label for="order-number" class="flex items-center gap-2 text-base font-medium text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                        <path d="m7.5 4.27 9 5.15"></path>
                        <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path>
                        <path d="m3.3 7 8.7 5 8.7-5"></path>
                        <path d="M12 22V12"></path>
                    </svg>
                    File Number
                </label>
                <input type="text" id="order-number" value="<?php echo htmlspecialchars($orderNumber); ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-base" readonly />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="scheduled-date" class="flex items-center gap-2 text-base font-medium text-gray-700"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8 y2=" 6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>Scheduled Date</label>
                    <input type="text" id="scheduled-date" value="<?php echo htmlspecialchars($scheduledDate); ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-base" readonly />
                </div>
                <div>
                    <label for="scheduled-time" class="flex items-center gap-2 text-base font-medium text-gray-700"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>Scheduled Time</label>
                    <input type="text" id="scheduled-time" value="<?php echo htmlspecialchars($scheduledTime); ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-base" readonly />
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="service-contact" class="flex items-center gap-2 text-base font-medium text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                            <path d="M19 2H5a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z"></path>
                            <line x1="16" y1="6" x2="8" y2="6"></line>
                            <line x1="16" y1="10" x2="8" y2="10"></line>
                            <line x1="10" y1="14" x2="8" y2="14"></line>
                        </svg>Service Contact
                    </label>
                    <input type="text" id="service-contact" value="<?php echo htmlspecialchars($serviceContact); ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-base" readonly />
                </div>
                <div>
                    <label for="number" class="flex items-center gap-2 text-base font-medium text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                            <path d="M22 16.92V20a2 2 0 0 1-2.18 2c-9.26-.33-16.8-7.84-17.18-17.1A2 2 0 0 1 4 2h3.09a2 2 0 0 1 2 1.72c.21 1.43.63 2.82 1.24 4.13a2 2 0 0 1-.45 2.1L8.91 11a16 16 0 0 0 6.09 6.09l1.05-1.05a2 2 0 0 1 2.1-.45c1.31.61 2.7 1.03 4.13 1.24a2 2 0 0 1 1.72 2z"></path>
                        </svg>Number
                    </label>
                    <input type="text" id="number" value="<?php echo htmlspecialchars($number); ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-base" readonly />
                </div>
            </div>

            <div>
                <label for="comments" class="block text-base font-medium text-gray-700">Comments</label>
                <textarea id="comments" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-base" placeholder="No comments available" readonly><?php echo htmlspecialchars($comments); ?></textarea>
            </div>
        </div>
        <div class="fixed inset-x-0 bottom-4 flex items-center justify-center p-4">
            <form action="overview_inspection.php" method="GET" style="width: 100%;">
                <input type="hidden" name="id" value="<?php echo $inspectionId; ?>" />
                <button type="submit" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-base font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-primary/90 h-10 px-4 py-2 mt-6 w-full bg-green-600 text-white">
                    Start Inspection
                </button>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.getElementById('comments');
            textarea.style.height = "";
            textarea.style.height = textarea.scrollHeight + "px";
        });
    </script>
</body>

</html>
