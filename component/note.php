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
    header('Location: ../login_index.html');
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
    $inspectionId = intval($_GET['id']);
} else {
    echo "Error: Inspection ID not provided.";
    exit;
}

if (isset($_GET['moduls'])) {
    $modulsId = intval($_GET['moduls']);
} else {
    echo "Error: Moduls ID not provided.";
    exit;
}

$query = "SELECT moduls, moduls_note FROM tb_select_modul WHERE id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$modulsId]);

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $table_moduls = htmlspecialchars($row['moduls']);
    $column_moduls_note = htmlspecialchars($row['moduls_note']);
} else {
    echo "Error: Moduls not found.";
    exit;
}

$query = "SELECT $column_moduls_note FROM $table_moduls WHERE id_inspeccion = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$inspectionId]);

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $notes = htmlspecialchars($row[$column_moduls_note]);
} else {
    $notes = "No notes found.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
        $updatedNotes = htmlspecialchars($_POST['notes']);
        $query = "UPDATE $table_moduls SET $column_moduls_note = ? WHERE id_inspeccion = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$updatedNotes, $inspectionId]);
        $notes = $updatedNotes;
        echo "<script>alert('Notes updated successfully');</script>";
    } else {
        echo "<script>alert('Invalid CSRF token');</script>";
    }
    exit;
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notepad</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="bg-gray-100 h-screen">
    <div class="container mx-auto p-4 bg-gray-100 shadow-md rounded-md mt-4 h-full">
        <div class="flex items-center mb-4">
            <a href="javascript:history.back()" class="inline-flex items-center justify-center rounded-full text-base font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-blue-500 hover:bg-blue-600 text-white h-10 w-10 mr-2 justify-center items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-xl font-semibold">Notepad</h1>
        </div>
        <form id="notesForm" method="POST" class="h-full">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="bg-yellow-100 p-4 rounded-md border-2 border-yellow-300 h-5/6">
                <textarea id="notes" name="notes" class="w-full h-full p-2 border-none outline-none bg-transparent resize-none"><?php echo $notes; ?></textarea>
            </div>
        </form>
    </div>
    <script>
        $(document).ready(function() {
            let scrollPosition = 0;

            $('#notes').on('focus', function() {
                scrollPosition = $(window).scrollTop();
            });

            $('#notes').on('blur', function() {
                $(window).scrollTop(scrollPosition);
            });

            $('#notes').on('input', function() {
                var notes = $(this).val();
                $.ajax({
                    type: 'POST',
                    url: '',
                    data: {
                        notes: notes,
                        csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                    },
                    success: function(response) {
                        console.log('Notes saved automatically');
                    }
                });
            });
        });
    </script>
</body>

</html>