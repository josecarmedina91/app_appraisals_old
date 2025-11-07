<?php
session_start();

session_regenerate_id();

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

if (!isset($_SESSION['usuario_id']) || !filter_var($_SESSION['usuario_id'], FILTER_VALIDATE_INT)) {
    header('Location: login_index.html');
    exit;
}

$usuario_id = intval($_SESSION['usuario_id']);

try {
    $query = "SELECT nombre_completo, correo_electronico FROM usuarios WHERE id_usuario = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$usuario_id]);

    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $nombre_usuario = htmlspecialchars($row['nombre_completo']);
        $correo_usuario = htmlspecialchars($row['correo_electronico']);
    } else {
        die('User not found.');
    }
} catch (PDOException $e) {
    die("ERROR: Could not execute query. " . $e->getMessage());
}

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    echo "Error: Inspection ID not provided or invalid.";
    exit;
}

$inspectionId = intval($_GET['id']);

try {
    $queryInspeccion = "SELECT direccion_propiedad FROM tb_inspecciones WHERE id_inspeccion = ?";
    $stmtInspeccion = $pdo->prepare($queryInspeccion);
    $stmtInspeccion->execute([$inspectionId]);

    $direccion = $stmtInspeccion->fetchColumn();
    if ($direccion === false) {
        die('Inspection details not found.');
    }
    $direccion = htmlspecialchars($direccion);
} catch (PDOException $e) {
    die("ERROR: Could not execute query. " . $e->getMessage());
}

class InspectionCompletion
{
    private $pdo;
    private $table;
    private $columns;

    public function __construct($pdo, $table, $columns)
    {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->columns = $columns;
    }

    public function getCompletionCount($inspectionId)
    {
        $columnCount = count($this->columns);
        $completedCount = 0;

        try {
            $query = "SELECT " . implode(", ", $this->columns) . " FROM " . $this->table . " WHERE id_inspeccion = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$inspectionId]);

            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                foreach ($this->columns as $column) {
                    if (!empty($row[$column]) && $row[$column] !== null) {
                        $completedCount++;
                    }
                }
            } else {
                die('Inspection details not found.');
            }
        } catch (PDOException $e) {
            die("ERROR: Could not execute query. " . $e->getMessage());
        }

        return [
            'completed' => $completedCount,
            'total' => $columnCount
        ];
    }
}

class FileInspectionCompletion
{
    private $directory;

    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    public function getCompletionCount()
    {
        $files = glob($this->directory . '/*.jpg');
        $completedCount = count($files) > 0 ? 1 : 0;

        return [
            'completed' => $completedCount,
            'total' => 1
        ];
    }
}

$siteColumns = ['parking', 'electrical', 'utilities', 'features', 'curb_appeal', 'topography', 'landscaping', 'site_improvements', 'site_features', 'select_option_driveway'];
$siteInspection = new InspectionCompletion($pdo, 'tb_site_inspecciones', $siteColumns);
$siteCompletion = $siteInspection->getCompletionCount($inspectionId);

$exteriorColumns = ['exterior_finish', 'type_of_building', 'roof', 'garage'];
$exteriorInspection = new InspectionCompletion($pdo, 'tb_exterior_inspecciones', $exteriorColumns);
$exteriorCompletion = $exteriorInspection->getCompletionCount($inspectionId);

$interiorColumns = ['windows', 'style', 'interior_finish', 'construction', 'foundation', 'insulation', 'closets', 'plumbing_lines', 'electrical', 'heating_system', 'water_heater', 'flooring', 'floor_plan', 'counter_tops', 'built_ins_extras', 'overall_in_condition', 'under_construction_or_renovation', 'basement'];
$interiorInspection = new InspectionCompletion($pdo, 'tb_interior_inspecciones', $interiorColumns);
$interiorCompletion = $interiorInspection->getCompletionCount($inspectionId);

$floorAreaSketchDirectory = __DIR__ . "/img/photo_gallery/{$inspectionId}/Floor_area_sketch";
$floorAreaSketchInspection = new FileInspectionCompletion($floorAreaSketchDirectory);
$floorAreaSketchCompletion = $floorAreaSketchInspection->getCompletionCount();

try {
    $queryConsent = "SELECT completed FROM tbl_consents WHERE id_inspeccion = ?";
    $stmtConsent = $pdo->prepare($queryConsent);
    $stmtConsent->execute([$inspectionId]);

    $consentCompleted = false;
    if ($row = $stmtConsent->fetch(PDO::FETCH_ASSOC)) {
        if ($row['completed'] != true) {
            echo "<script>
                alert('You need to complete the Photo Consent Form section before accessing these pages.');
                window.location.href = 'photo_consent.php?id=" . urlencode($inspectionId) . "';
            </script>";
            exit;
        } else {
            $consentCompleted = true;
        }
    } else {
        echo "<script>
            alert('You need to complete the Photo Consent Form section before accessing these pages.');
            window.location.href = 'photo_consent.php?id=" . urlencode($inspectionId) . "';
        </script>";
        exit;
    }
} catch (PDOException $e) {
    die("ERROR: Could not execute query. " . $e->getMessage());
}

try {
    $queryDamage = "SELECT damage FROM tb_exterior_inspecciones WHERE id_inspeccion = ?";
    $stmtDamage = $pdo->prepare($queryDamage);
    $stmtDamage->execute([$inspectionId]);

    $damageCompleted = false;
    if ($row = $stmtDamage->fetch(PDO::FETCH_ASSOC)) {
        if ($row['damage'] != '' && $row['damage'] !== '"No"') {
            $damageCompleted = true;
        }
    }
} catch (PDOException $e) {
    die("ERROR: Could not execute query. " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Inspection</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body,
        html {
            height: 100%;
        }

        #login-container {
            min-height: 100%;
        }

        .icon {
            height: 32px;
            width: 32px;
            margin-right: 8px;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.3s ease;
        }

        .modal {
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            transform: translateY(-20px);
            transition: transform 0.3s ease;
            opacity: 0;
        }

        .modal.visible .modal {
            opacity: 1;
            transform: translateY(0);
        }

        .hidden {
            display: none;
            opacity: 0;
        }

        .visible {
            opacity: 1;
        }

        .active-tab {
            background-color: #38a169;
            color: white;
        }

        .tab-content {
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .tab-content.active {
            opacity: 1;
        }

        .incomplete-section {
            background-color: red;
            color: white;
        }

        .incomplete-section .text-gray-500 {
            color: white !important;
        }

        .incomplete-section .text-green-500 {
            color: white !important;
        }
    </style>
</head>

<body>
    <div class="modal-overlay hidden" id="modal">
        <div class="modal" id="modal-content">
            <p>Should your phone lose internet connection, please take photos using your device's camera. After restoring the connection, upload inspection photos and notes on this application.</p>
        </div>
    </div>

    <div class="bg-white w-full h-screen">
        <div class="p-4 border-b bg-gray-200">
            <div class="flex items-center">
                <a href="start_inspection.php?id=<?php echo urlencode($inspectionId); ?>" class="inline-flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#6b7280] icon">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                    <h1 class="text-xl font-semibold mx-auto mt-2"><?php echo htmlspecialchars($direccion); ?></h1>
                </a>
            </div>
        </div>
        <div class="border-b mt-8">
            <div dir="ltr" data-orientation="horizontal">
                <div role="tablist" aria-orientation="horizontal" class="h-9 items-center rounded-lg bg-muted text-muted-foreground flex justify-around p-2" tabindex="-1" data-orientation="horizontal" style="outline: none;">
                    <button type="button" role="tab" aria-selected="true" aria-controls="radix-:rc:-content-overview" data-state="active" id="radix-:rc:-trigger-overview" class="inline-flex items-center justify-center whitespace-nowrap rounded-md px-3 py-1 text-base font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 flex-1 active-tab" tabindex="-1" data-orientation="horizontal" data-radix-collection-item="">
                        <span class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-base font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 h-10 px-4 py-2 w-full">
                            Overview
                        </span>
                    </button>

                    <button type="button" role="tab" aria-selected="false" aria-controls="radix-:rc:-content-notes" data-state="inactive" id="radix-:rc:-trigger-notes" class="hidden inline-flex items-center justify-center whitespace-nowrap rounded-md px-3 py-1 text-base font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 flex-1" tabindex="-1" data-orientation="horizontal" data-radix-collection-item="">
                        <span class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-base font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2 w-full">
                            Notes
                        </span>
                    </button>
                    <button type="button" role="tab" aria-selected="false" aria-controls="radix-:rc:-content-gallery" data-state="inactive" id="radix-:rc:-trigger-gallery" class="hidden inline-flex items-center justify-center whitespace-nowrap rounded-md px-3 py-1 text-base font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 flex-1" tabindex="-1" data-orientation="horizontal" data-radix-collection-item="">
                        <span class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-base font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2 w-full">
                            Gallery
                        </span>
                    </button>
                </div>
                <div data-state="active" data-orientation="horizontal" role="tabpanel" aria-labelledby="radix-:rc:-trigger-overview" id="radix-:rc:-content-overview" tabindex="0" class="mt-2 ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 p-4 tab-content active" style="animation-duration: 0s;">
                    <div class="grid grid-cols-1 gap-3 mt-2">
                        <div class="h-14 rounded-lg border text-card-foreground shadow-sm bg-gray-100" data-v0-t="card">
                            <a href="photo_consent.php?id=<?php echo urlencode($inspectionId); ?>" class="h-full p-2 flex items-center justify-between">
                                <div>
                                    <div class="font-semibold">Photo Consent Form</div>
                                    Completed: <?php echo $consentCompleted ? '1/1' : '0/1'; ?>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-500 h-8 w-8">
                                    <path d="M20 4h-3l-2-2H9L7 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zM12 16a4 4 0 1 1 4-4 4 4 0 0 1-4 4zm0-6.5a2.5 2.5 0 1 0 2.5 2.5A2.5 2.5 0 0 0 12 9.5z"></path>
                                </svg>
                            </a>
                        </div>
                        <div class="h-14 rounded-lg border text-card-foreground shadow-sm bg-gray-100" data-v0-t="card">
                            <a href="exterior_overview_menu.php?id=<?php echo urlencode($inspectionId); ?>" class="h-full p-2 flex items-center justify-between">
                                <div>
                                    <div class="font-semibold">Exterior</div>
                                    <div class="text-base text-gray-500">Completed: <?php echo $exteriorCompletion['completed'] . '/' . $exteriorCompletion['total']; ?></div>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-500 h-8 w-8">
                                    <rect width="16" height="20" x="4" y="2" rx="2" ry="2"></rect>
                                    <path d="M9 22v-4h6v4"></path>
                                    <path d="M8 6h.01"></path>
                                    <path d="M16 6h.01"></path>
                                    <path d="M12 6h.01"></path>
                                    <path d="M12 10h.01"></path>
                                    <path d="M12 14h.01"></path>
                                    <path d="M16 10h.01"></path>
                                    <path d="M16 14h.01"></path>
                                    <path d="M8 10h.01"></path>
                                    <path d="M8 14h.01"></path>
                                </svg>
                            </a>
                        </div>
                        <div class="h-14 rounded-lg border text-card-foreground shadow-sm bg-gray-100" data-v0-t="card">
                            <a href="interior_overview_menu.php?id=<?php echo urlencode($inspectionId); ?>" class="h-full p-2 flex items-center justify-between">
                                <div>
                                    <div class="font-semibold">Interior</div>
                                    <div class="text-base text-gray-500">Completed: <?php echo $interiorCompletion['completed'] . '/' . $interiorCompletion['total']; ?></div>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-500 h-8 w-8">
                                    <path d="M20 9V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v3"></path>
                                    <path d="M2 11v5a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-5a2 2 0 0 0-4 0v2H6v-2a2 2 0 0 0-4 0Z"></path>
                                    <path d="M4 18v2"></path>
                                    <path d="M20 18v2"></path>
                                    <path d="M12 4v9"></path>
                                </svg>
                            </a>
                        </div>
                        <div class="h-14 rounded-lg border text-card-foreground shadow-sm bg-gray-100" data-v0-t="card">
                            <a href="site_overview_menu.php?id=<?php echo urlencode($inspectionId); ?>" class="h-full p-2 flex items-center justify-between">
                                <div>
                                    <div class="font-semibold">Site</div>
                                    <div class="text-base text-gray-500">Completed: <?php echo $siteCompletion['completed'] . '/' . $siteCompletion['total']; ?></div>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-500 h-8 w-8">
                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                            </a>
                        </div>
                        <div class="h-14 rounded-lg border text-card-foreground shadow-sm bg-gray-100" data-v0-t="card">
                            <a href="overview_floor_area.php?id=<?php echo urlencode($inspectionId); ?>" class="h-full p-2 flex items-center justify-between">
                                <div>
                                    <div class="font-semibold">Floor Area Sketch</div>
                                    <div class="text-base text-gray-500">Completed: <?php echo $floorAreaSketchCompletion['completed'] . '/' . $floorAreaSketchCompletion['total']; ?></div>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-500 h-8 w-8">
                                    <rect x="3" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="14" width="7" height="7"></rect>
                                    <rect x="3" y="14" width="7" height="7"></rect>
                                    <path d="M7 3v18M17 3v18M3 7h18M3 17h18"></path>
                                </svg>
                            </a>
                        </div>
                        <div class="h-14 rounded-lg border text-card-foreground shadow-sm bg-gray-100" data-v0-t="card">
                            <a href="overview_damage.php?id=<?php echo urlencode($inspectionId); ?>" class="h-full p-2 flex items-center justify-between">
                                <div>
                                    <div class="font-semibold">Detrimental Factors</div>
                                    <div class="text-base text-gray-500">Completed: <?php echo $damageCompleted ? '1/1' : '0/1'; ?></div>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-500 h-8 w-8">
                                    <path d="M12 22s-8-4.5-8-10V6l8-4 8 4v6c0 5.5-8 10-8 10z"></path>
                                    <path d="M9 14l6-6"></path>
                                    <path d="M9 8l6 6"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                    <div class="fixed inset-x-0 bottom-4 flex items-center justify-center p-4">
                        <a href="javascript:void(0)" id="complete-review-button" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-base font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 h-10 px-4 py-2 w-full bg-green-600 hover:bg-green-600 focus:bg-green-600 text-white">
                            Complete and Review Inspection
                        </a>
                    </div>

                </div>
                <div data-state="inactive" data-orientation="horizontal" role="tabpanel" aria-labelledby="radix-:rc:-trigger-notes" hidden="" id="radix-:rc:-content-notes" tabindex="0" class="mt-2 ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 p-4 tab-content"></div>
                <div data-state="inactive" data-orientation="horizontal" role="tabpanel" aria-labelledby="radix-:rc:-trigger-gallery" hidden="" id="radix-:rc:-content-gallery" tabindex="0" class="mt-2 ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 p-4 tab-content"></div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const referrer = document.referrer;
            const showModal = referrer.includes('start_inspection.php');

            if (showModal) {
                const modal = document.getElementById('modal');
                modal.classList.remove('hidden');
                setTimeout(() => {
                    modal.classList.add('visible');
                    modal.querySelector('.modal').style.transform = 'translateY(0)';
                    modal.querySelector('.modal').style.opacity = '1';
                }, 100);
            }

            modal.addEventListener('click', function(e) {
                if (e.target === modal || e.target.id === 'modal-content') {
                    modal.querySelector('.modal').style.transform = 'translateY(-20px)';
                    modal.querySelector('.modal').style.opacity = '0';
                    modal.classList.remove('visible');
                    setTimeout(() => {
                        modal.classList.add('hidden');
                    }, 300);
                }
            });

            const tabs = document.querySelectorAll('[role="tab"]');
            const tabPanels = document.querySelectorAll('[role="tabpanel"]');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const target = tab.getAttribute('aria-controls');

                    tabs.forEach(t => {
                        t.setAttribute('aria-selected', 'false');
                        t.setAttribute('data-state', 'inactive');
                        t.classList.remove('active-tab');
                    });

                    tab.setAttribute('aria-selected', 'true');
                    tab.setAttribute('data-state', 'active');
                    tab.classList.add('active-tab');

                    tabPanels.forEach(panel => {
                        if (panel.id === target) {
                            panel.hidden = false;
                            panel.classList.add('active');
                        } else {
                            panel.hidden = true;
                            panel.classList.remove('active');
                        }
                    });
                });
            });

            document.getElementById('complete-review-button').addEventListener('click', function() {
                const consentCompleted = <?php echo $consentCompleted ? 'true' : 'false'; ?>;
                const exteriorCompleted = <?php echo $exteriorCompletion['completed'] === $exteriorCompletion['total'] ? 'true' : 'false'; ?>;
                const interiorCompleted = <?php echo $interiorCompletion['completed'] === $interiorCompletion['total'] ? 'true' : 'false'; ?>;
                const siteCompleted = <?php echo $siteCompletion['completed'] === $siteCompletion['total'] ? 'true' : 'false'; ?>;
                const floorAreaSketchCompleted = <?php echo $floorAreaSketchCompletion['completed'] === $floorAreaSketchCompletion['total'] ? 'true' : 'false'; ?>;
                const damageCompleted = <?php echo $damageCompleted ? 'true' : 'false'; ?>;

                const sections = [{
                        completed: consentCompleted,
                        element: document.querySelector('a[href*="photo_consent.php"]')
                    },
                    {
                        completed: exteriorCompleted,
                        element: document.querySelector('a[href*="exterior_overview_menu.php"]')
                    },
                    {
                        completed: interiorCompleted,
                        element: document.querySelector('a[href*="interior_overview_menu.php"]')
                    },
                    {
                        completed: siteCompleted,
                        element: document.querySelector('a[href*="site_overview_menu.php"]')
                    },
                    {
                        completed: floorAreaSketchCompleted,
                        element: document.querySelector('a[href*="overview_floor_area.php"]')
                    },
                    {
                        completed: damageCompleted,
                        element: document.querySelector('a[href*="overview_damage.php"]')
                    }
                ];

                let allCompleted = true;
                sections.forEach(section => {
                    if (!section.completed) {
                        section.element.classList.add('incomplete-section');
                        allCompleted = false;
                    } else {
                        section.element.classList.remove('incomplete-section');
                    }
                });

                if (allCompleted) {
                    window.location.href = 'form_export/form_export.php?id=<?php echo urlencode($inspectionId); ?>';
                } else {
                    alert('You cannot review the inspection until all sections are completed.');
                }
            });
        });
    </script>

</body>

</html>