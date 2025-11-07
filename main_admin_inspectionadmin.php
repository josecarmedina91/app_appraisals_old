<?php
session_start();

class Database
{
    private $pdo;

    public function __construct($host, $dbname, $username, $password)
    {
        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
            ]);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("ERROR: Could not connect to the database.");
        }
    }

    public function getPDO()
    {
        return $this->pdo;
    }
}

class Inspection
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllInspections()
    {
        $query = "SELECT i.*, u.nombre_completo FROM tb_inspecciones i LEFT JOIN usuarios u ON i.id_usuario_asignado = u.id_usuario";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        $inspecciones = $stmt->fetchAll();

        foreach ($inspecciones as &$inspeccion) {
            $fechaProgramada = new DateTime($inspeccion['fecha_programada']);
            $inspeccion['fecha_programada'] = $fechaProgramada->format('Y-m-d h:i A');
        }

        return $inspecciones;
    }

    public function createInspection($data)
    {
        $query = "INSERT INTO tb_inspecciones (direccion_propiedad, numero_orden, fecha_programada, service_contact, number, comentarios, id_usuario_asignado) 
                  VALUES (:direccion_propiedad, :numero_orden, :fecha_programada, :service_contact, :number, :comentarios, :id_usuario_asignado)";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($data);
    }

    public function deleteInspection($id_inspeccion)
    {
        $tables = ['tbl_consents', 'tbl_direccion_room_allocation', 'tb_exterior_inspecciones', 'tb_interior_inspecciones', 'tb_site_inspecciones', 'tb_inspecciones'];
        foreach ($tables as $table) {
            $query = "DELETE FROM $table WHERE id_inspeccion = :id_inspeccion";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([':id_inspeccion' => $id_inspeccion]);
        }
    }

    public function updateInspection($data)
    {
        $query = "UPDATE tb_inspecciones 
                  SET direccion_propiedad = :direccion_propiedad, 
                      numero_orden = :numero_orden, 
                      fecha_programada = :fecha_programada, 
                      service_contact = :service_contact, 
                      number = :number, 
                      comentarios = :comentarios, 
                      id_usuario_asignado = :id_usuario_asignado,
                      status_inspeccion = :status_inspeccion 
                  WHERE id_inspeccion = :id_inspeccion";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($data);
    }
}

class User
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllUsers()
    {
        $query = "SELECT id_usuario, nombre_completo FROM usuarios";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

$host = 'localhost';
$dbname = 'db_community_appraisals';
$username = 'root';
$password = '';

$database = new Database($host, $dbname, $username, $password);
$pdo = $database->getPDO();

$inspection = new Inspection($pdo);
$user = new User($pdo);

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_usuario'] !== 'admin') {
    header('Location: login_index.html');
    exit;
}

session_regenerate_id(true);

$inspecciones = $inspection->getAllInspections();
$usuarios = $user->getAllUsers();

function sanitize_input($data)
{
    return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_inspeccion'])) {
    $data = [
        ':direccion_propiedad' => sanitize_input($_POST['direccion_propiedad']),
        ':numero_orden' => sanitize_input($_POST['numero_orden']),
        ':fecha_programada' => (new DateTime(sanitize_input($_POST['fecha_programada']) . ' ' . sanitize_input($_POST['hora_programada'])))->format('Y-m-d H:i:s'),
        ':service_contact' => sanitize_input($_POST['service_contact']),
        ':number' => sanitize_input($_POST['number']),
        ':comentarios' => sanitize_input($_POST['comentarios']),
        ':id_usuario_asignado' => sanitize_input($_POST['id_usuario_asignado']) ?: null
    ];

    $inspection->createInspection($data);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar_inspeccion'])) {
    $id_inspeccion = sanitize_input($_POST['id_inspeccion']);
    $inspection->deleteInspection($id_inspeccion);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar_inspeccion'])) {
    $data = [
        ':direccion_propiedad' => sanitize_input($_POST['direccion_propiedad']),
        ':numero_orden' => sanitize_input($_POST['numero_orden']),
        ':fecha_programada' => (new DateTime(sanitize_input($_POST['fecha_programada']) . ' ' . sanitize_input($_POST['hora_programada'])))->format('Y-m-d H:i:s'),
        ':service_contact' => sanitize_input($_POST['service_contact']),
        ':number' => sanitize_input($_POST['number']),
        ':comentarios' => sanitize_input($_POST['comentarios']),
        ':id_usuario_asignado' => sanitize_input($_POST['id_usuario_asignado']) ?: null,
        ':status_inspeccion' => sanitize_input($_POST['id_usuario_asignado']) ? 'Assigned' : 'Unassigned',
        ':id_inspeccion' => sanitize_input($_POST['id_inspeccion'])
    ];

    $inspection->updateInspection($data);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration of Inspections</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script>
        function onSubmitForm() {
            return true;
        }

        function confirmDeletion(id) {
            if (confirm('Are you sure you want to delete this inspection?')) {
                document.getElementById('deleteForm' + id).submit();
            }
        }

        function openEditModal(id, direccion, numeroOrden, fechaProgramada, serviceContact, number, comentarios, idUsuarioAsignado) {
            document.getElementById('editarInspeccionModal').classList.remove('hidden');
            document.getElementById('edit_id_inspeccion').value = id;
            document.getElementById('edit_direccion_propiedad').value = direccion;
            document.getElementById('edit_numero_orden').value = numeroOrden;
            document.getElementById('edit_fecha_programada')._flatpickr.setDate(fechaProgramada.split(' ')[0]);
            document.getElementById('edit_hora_programada')._flatpickr.setDate(fechaProgramada.split(' ')[1].slice(0, 5));
            document.getElementById('edit_service_contact').value = serviceContact;
            document.getElementById('edit_number').value = number;
            document.getElementById('edit_comentarios').value = htmlspecialchars_decode(comentarios);
            document.getElementById('edit_id_usuario_asignado').value = idUsuarioAsignado;
        }

        function closeEditModal() {
            document.getElementById('editarInspeccionModal').classList.add('hidden');
        }

        function htmlspecialchars_decode(str) {
            if (typeof str === 'string') {
                return str.replace(/&amp;/g, '&')
                    .replace(/&quot;/g, '"')
                    .replace(/&#039;/g, "'")
                    .replace(/&lt;/g, '<')
                    .replace(/&gt;/g, '>');
            }
            return str;
        }

        document.addEventListener('DOMContentLoaded', function() {
            flatpickr("#fecha_programada", {
                dateFormat: "Y-m-d"
            });
            flatpickr("#hora_programada", {
                enableTime: true,
                noCalendar: true,
                dateFormat: "h:i K",
                time_24hr: false
            });

            flatpickr("#edit_fecha_programada", {
                dateFormat: "Y-m-d"
            });
            flatpickr("#edit_hora_programada", {
                enableTime: true,
                noCalendar: true,
                dateFormat: "h:i K",
                time_24hr: false
            });
        });
    </script>
    <style>
        .table-cell-ellipsis {
            white-space: nowrap;
        }
    </style>
</head>

<body class="bg-gray-100 text-sm">
    <div class="min-h-screen flex flex-col">
        <header class="bg-gray-800 text-white p-4 shadow">
            <div class="flex items-center justify-between">
                <button type="button" onclick="window.location.href='main_admin.php'" class="inline-block p-2 bg-blue-500 text-white rounded-full hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-lg">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="flex-grow text-center">
                    <h1 class="text-lg font-semibold">Administration of Inspections</h1>
                </div>
                <div class="w-8"></div>
            </div>
        </header>
        <main class="flex-grow p-4">
            <div class="flex justify-end mb-4">
                <button type="button" onclick="document.getElementById('crearInspeccionModal').classList.remove('hidden')" class="bg-green-500 text-white px-4 py-2 rounded-full hover:bg-green-600">
                    <i class="fas fa-plus"></i> New Inspection
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white shadow rounded-lg overflow-hidden">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 bg-gray-200 table-cell-ellipsis">File Number</th>
                            <th class="py-2 px-4 bg-gray-200">Property Address</th>
                            <th class="py-2 px-4 bg-gray-200 table-cell-ellipsis">Scheduled Date</th>
                            <th class="py-2 px-4 bg-gray-200">Site Visit Name</th>
                            <th class="py-2 px-4 bg-gray-200 table-cell-ellipsis">Phone Number</th>
                            <th class="py-2 px-4 bg-gray-200">Comments</th>
                            <th class="py-2 px-4 bg-gray-200">Assigned User</th>
                            <th class="py-2 px-4 bg-gray-200 table-cell-ellipsis">Inspection Status</th>
                            <th class="py-2 px-4 bg-gray-200">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inspecciones as $inspeccion) : ?>
                            <tr class="hover:bg-gray-100">
                                <td class="border px-4 py-2 table-cell-ellipsis"><?php echo htmlspecialchars($inspeccion['numero_orden']); ?></td>
                                <td class="border px-4 py-2"><?php echo htmlspecialchars($inspeccion['direccion_propiedad']); ?></td>
                                <td class="border px-4 py-2 table-cell-ellipsis"><?php echo htmlspecialchars($inspeccion['fecha_programada']); ?></td>
                                <td class="border px-4 py-2"><?php echo htmlspecialchars($inspeccion['service_contact']); ?></td>
                                <td class="border px-4 py-2 table-cell-ellipsis"><?php echo htmlspecialchars($inspeccion['number']); ?></td>
                                <td class="border px-4 py-2"><?php echo htmlspecialchars_decode($inspeccion['comentarios'], ENT_QUOTES); ?></td>
                                <td class="border px-4 py-2"><?php echo htmlspecialchars($inspeccion['nombre_completo'] ?? 'Unassigned'); ?></td>
                                <td class="border px-4 py-2 table-cell-ellipsis"><?php echo htmlspecialchars($inspeccion['status_inspeccion']); ?></td>
                                <td class="border px-4 py-2">
                                    <div class="flex space-x-2">
                                        <button type="button" onclick="openEditModal('<?php echo htmlspecialchars($inspeccion['id_inspeccion']); ?>', '<?php echo htmlspecialchars($inspeccion['direccion_propiedad']); ?>', '<?php echo htmlspecialchars($inspeccion['numero_orden']); ?>', '<?php echo htmlspecialchars($inspeccion['fecha_programada']); ?>', '<?php echo htmlspecialchars($inspeccion['service_contact']); ?>', '<?php echo htmlspecialchars($inspeccion['number']); ?>', '<?php echo htmlspecialchars($inspeccion['comentarios']); ?>', '<?php echo htmlspecialchars($inspeccion['id_usuario_asignado']); ?>')" class="bg-yellow-500 text-white px-2 py-2 rounded hover:bg-yellow-600 w-full">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-edit"></i>
                                                <span class="block text-xs">Edit</span>
                                            </div>
                                        </button>
                                        <form id="deleteForm<?php echo htmlspecialchars($inspeccion['id_inspeccion']); ?>" method="POST" action="" style="display:inline;" class="w-full">
                                            <input type="hidden" name="id_inspeccion" value="<?php echo htmlspecialchars($inspeccion['id_inspeccion']); ?>">
                                            <button type="button" onclick="confirmDeletion(<?php echo htmlspecialchars($inspeccion['id_inspeccion']); ?>)" class="bg-red-500 text-white px-2 py-2 rounded hover:bg-red-600 w-full">
                                                <div class="flex flex-col items-center">
                                                    <i class="fas fa-trash-alt"></i>
                                                    <span class="block text-xs">Delete</span>
                                                </div>
                                            </button>
                                            <input type="hidden" name="eliminar_inspeccion">
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Modal for creating a new inspection -->
    <div id="crearInspeccionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-6 rounded-lg w-full max-w-md shadow-lg">
            <h2 class="text-lg font-semibold mb-4">Create New Inspection</h2>
            <form method="POST" action="" onsubmit="return onSubmitForm()">
                <div class="mb-4 relative">
                    <i class="fas fa-map-marker-alt absolute top-3 left-3 text-gray-400"></i>
                    <input type="text" name="direccion_propiedad" class="w-full border px-4 py-2 rounded-lg pl-10" placeholder="Property Address" required>
                </div>
                <div class="mb-4 relative">
                    <i class="fas fa-file-alt absolute top-3 left-3 text-gray-400"></i>
                    <input type="text" name="numero_orden" class="w-full border px-4 py-2 rounded-lg pl-10" placeholder="File Number" required>
                </div>
                <div class="mb-4 relative">
                    <i class="fas fa-calendar-alt absolute top-3 left-3 text-gray-400"></i>
                    <input type="date" id="fecha_programada" name="fecha_programada" class="w-full border px-4 py-2 rounded-lg pl-10" placeholder="Select Date" required>
                </div>
                <div class="mb-4 relative">
                    <i class="fas fa-clock absolute top-3 left-3 text-gray-400"></i>
                    <input type="time" id="hora_programada" name="hora_programada" class="w-full border px-4 py-2 rounded-lg pl-10" placeholder="Select Time" required>
                </div>

                <div class="mb-4 relative">
                    <i class="fas fa-user-alt absolute top-3 left-3 text-gray-400"></i>
                    <input type="text" name="service_contact" class="w-full border px-4 py-2 rounded-lg pl-10" placeholder="Site Visit Party - Full Name" required>
                </div>
                <div class="mb-4 relative">
                    <i class="fas fa-phone-alt absolute top-3 left-3 text-gray-400"></i>
                    <input type="text" name="number" class="w-full border px-4 py-2 rounded-lg pl-10" placeholder="Site Visit Party - Phone Number" required>
                </div>
                <div class="mb-4 relative">
                    <i class="fas fa-comments absolute top-3 left-3 text-gray-400"></i>
                    <textarea name="comentarios" class="w-full border px-4 py-2 rounded-lg pl-10" placeholder="Comments"></textarea>
                </div>
                <div class="mb-4 relative">
                    <i class="fas fa-user-tag absolute top-3 left-3 text-gray-400"></i>
                    <select name="id_usuario_asignado" class="w-full border px-4 py-2 rounded-lg pl-10">
                        <option value="">Unassigned</option>
                        <?php foreach ($usuarios as $usuario) : ?>
                            <option value="<?php echo htmlspecialchars($usuario['id_usuario']); ?>"><?php echo htmlspecialchars($usuario['nombre_completo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex justify-end">
                    <button type="button" onclick="document.getElementById('crearInspeccionModal').classList.add('hidden')" class="bg-red-500 text-white px-4 py-2 rounded-full hover:bg-red-600 mr-2">Cancel</button>
                    <button type="submit" name="crear_inspeccion" class="bg-blue-500 text-white px-4 py-2 rounded-full hover:bg-blue-600">Create</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal for editing an inspection -->
    <div id="editarInspeccionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-6 rounded-lg w-full max-w-md shadow-lg">
            <h2 class="text-lg font-semibold mb-4">Edit Inspection</h2>
            <form method="POST" action="">
                <input type="hidden" id="edit_id_inspeccion" name="id_inspeccion">
                <input type="hidden" id="edit_inspection_status" name="status_inspeccion">
                <div class="mb-4 relative">
                    <i class="fas fa-map-marker-alt absolute top-3 left-3 text-gray-400"></i>
                    <input type="text" id="edit_direccion_propiedad" name="direccion_propiedad" class="w-full border px-4 py-2 rounded-lg pl-10" placeholder="Property Address" required>
                </div>
                <div class="mb-4 relative">
                    <i class="fas fa-file-alt absolute top-3 left-3 text-gray-400"></i>
                    <input type="text" id="edit_numero_orden" name="numero_orden" class="w-full border px-4 py-2 rounded-lg pl-10" placeholder="File Number" required>
                </div>
                <div class="mb-4 relative">
                    <i class="fas fa-calendar-alt absolute top-3 left-3 text-gray-400"></i>
                    <input type="date" id="edit_fecha_programada" name="fecha_programada" class="w-full border px-4 py-2 rounded-lg pl-10" placeholder="Select Date" required>
                </div>
                <div class="mb-4 relative">
                    <i class="fas fa-clock absolute top-3 left-3 text-gray-400"></i>
                    <input type="time" id="edit_hora_programada" name="hora_programada" class="w-full border px-4 py-2 rounded-lg pl-10" placeholder="Select Time" required>
                </div>

                <div class="mb-4 relative">
                    <i class="fas fa-user-alt absolute top-3 left-3 text-gray-400"></i>
                    <input type="text" id="edit_service_contact" name="service_contact" class="w-full border px-4 py-2 rounded-lg pl-10" placeholder="Site Visit Party - Full Name" required>
                </div>
                <div class="mb-4 relative">
                    <i class="fas fa-phone-alt absolute top-3 left-3 text-gray-400"></i>
                    <input type="text" id="edit_number" name="number" class="w-full border px-4 py-2 rounded-lg pl-10" placeholder="Site Visit Party - Phone Number" required>
                </div>
                <div class="mb-4 relative">
                    <i class="fas fa-comments absolute top-3 left-3 text-gray-400"></i>
                    <textarea id="edit_comentarios" name="comentarios" class="w-full border px-4 py-2 rounded-lg pl-10" placeholder="Comments"></textarea>
                </div>
                <div class="mb-4 relative">
                    <i class="fas fa-user-tag absolute top-3 left-3 text-gray-400"></i>
                    <select id="edit_id_usuario_asignado" name="id_usuario_asignado" class="w-full border px-4 py-2 rounded-lg pl-10">
                        <option value="">Unassigned</option>
                        <?php foreach ($usuarios as $usuario) : ?>
                            <option value="<?php echo htmlspecialchars($usuario['id_usuario']); ?>"><?php echo htmlspecialchars($usuario['nombre_completo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex justify-end">
                    <button type="button" onclick="closeEditModal()" class="bg-red-500 text-white px-4 py-2 rounded-full hover:bg-red-600 mr-2">Cancel</button>
                    <button type="submit" name="editar_inspeccion" class="bg-blue-500 text-white px-4 py-2 rounded-full hover:bg-blue-600">Save</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
