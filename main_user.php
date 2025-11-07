<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login_index.html');
    exit;
}

require_once './config/db_connect.php';

$usuarioId = $_SESSION['usuario_id'];

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_usuario'] !== 'user') {
    header('Location: login_index.html');
    exit;
}

try {
    $sql = "SELECT * FROM tb_inspecciones WHERE id_usuario_asignado = :usuarioId AND status_inspeccion = 'Assigned'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':usuarioId', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();    

    $propiedades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$stmt = null;
$pdo = null;

function escape($html)
{
    return htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Properties</title>
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

<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- Navbar -->
        <nav class="bg-gray-800 text-white p-4">
            <div class="flex items-center justify-between">
                <button id="menuButton" class="text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <span class="text-xl font-semibold mx-auto">My Properties</span>
            </div>
        </nav>

        <!-- Tabs -->
        <div id="w3ngai5mc" class="flex justify-center border-b border-gray-300 p-4 w-full">
            <button id="inProgressTab" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2 mr-2 bg-green-100 text-green-700 flex-1">
                In Progress
            </button>
            <button id="completedTab" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2 bg-gray-100 text-gray-700 flex-1">
                Completed
            </button>
        </div>

        <!-- Content -->
        <main class="flex-grow p-4">
            <!-- Filter Button and Results -->
            <div class="flex justify-between items-center mb-6">
                <button id="filterButton" class="flex items-center px-3 py-2 bg-white text-gray-700 rounded-md shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 3a1 1 0 01-1-1V1a1 1 0 011-1h10a1 1 0 011 1v1a1 1 0 01-1 1h-1v2.1a5 5 0 00-4 4.9v5.1h-2V10a5 5 0 00-4-4.9V3H5zm4 5a3 3 0 016 0v5h-6V8z" clip-rule="evenodd" />
                    </svg>
                    Filter
                </button>
                <span class="text-base text-gray-600">Results: <?php echo count($propiedades); ?></span>
            </div>
            <!-- Filters Container - initially hidden -->
            <div id="filtersContainer" class="hidden bg-white shadow rounded-lg p-4">
                <h2 class="text-lg font-semibold mb-4">Filters</h2>
                <input type="text" placeholder="Search by keyword" class="w-full p-2 border rounded mb-4">
                <label class="block text-base font-medium mb-1" for="scheduledDate">Scheduled Date</label>
                <input type="date" id="scheduledDate" name="scheduledDate" class="w-full p-2 border rounded mb-4" value="2024-04-08">
                <button class="w-full bg-green-600 text-white py-3 rounded-md hover:bg-green-700 transition-colors">Apply Filters</button>
            </div>

            <?php if (!empty($propiedades)) : ?>
                <?php foreach ($propiedades as $propiedad) : ?>
                    <div class="mt-4 property-item" data-inspection-id="<?php echo escape($propiedad['id_inspeccion']); ?>">
                        <div class="border text-card-foreground overflow-hidden rounded-lg bg-white shadow" data-v0-t="card">
                            <div class="flex-col space-y-1.5 flex items-center justify-between border-b p-4 shadow-lg">
                                <div class="space-y-1">
                                    <p class="text-base text-gray-500">
                                        <?php echo escape($propiedad['direccion_propiedad']); ?>
                                    </p>
                                    <div class="flex items-center space-x-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-[#10B981]">
                                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                                            <line x1="16" x2="16" y1="2" y2="6"></line>
                                            <line x1="8" x2="8" y1="2" y2="6"></line>
                                            <line x1="3" x2="21" y1="10" y2="10"></line>
                                        </svg>
                                        <span class="text-xs text-gray-500">
                                            Scheduled: <?php echo date('l, F j, Y', strtotime($propiedad['fecha_programada'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="text-center p-4 border border-gray-300 rounded-lg bg-white">
                    <p class="text-gray-700 mb-4">You have no inspections currently assigned.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <script src="./js/main/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const propertyItems = document.querySelectorAll('.property-item');

            propertyItems.forEach(item => {
                item.addEventListener('click', function() {
                    const inspectionId = this.dataset.inspectionId;
                    window.location.href = `start_inspection.php?id=${inspectionId}`;
                });
            });

            const inProgressTab = document.getElementById('inProgressTab');
            const completedTab = document.getElementById('completedTab');

            inProgressTab.addEventListener('click', () => {
                inProgressTab.classList.add('bg-green-100', 'text-green-700');
                inProgressTab.classList.remove('bg-gray-100', 'text-gray-700');
                completedTab.classList.add('bg-gray-100', 'text-gray-700');
                completedTab.classList.remove('bg-green-100', 'text-green-700');
            });

            completedTab.addEventListener('click', () => {
                completedTab.classList.add('bg-green-100', 'text-green-700');
                completedTab.classList.remove('bg-gray-100', 'text-gray-700');
                inProgressTab.classList.add('bg-gray-100', 'text-gray-700');
                inProgressTab.classList.remove('bg-green-100', 'text-green-700');
            });

            const menuButton = document.getElementById('menuButton');
            menuButton.addEventListener('click', function() {
                const usuarioId = this.dataset.usuarioId;
                window.location.href = `menu.php?id=${usuarioId}`;
            });
        });
    </script>
</body>

</html>
