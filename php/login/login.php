<?php
// login.php
session_start();

require_once '../../config/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] != "POST" || empty($_POST['email']) || empty($_POST['password'])) {
    die(json_encode(["error" => "Incorrect method or invalid input."]));
}

$email = trim($_POST['email']);
$password = trim($_POST['password']);

$sql = "SELECT id_usuario, nombre_completo, correo_electronico, contraseña, rol_usuario FROM usuarios WHERE correo_electronico = :email";

$stmt = $pdo->prepare($sql);
if (!$stmt) {
    die(json_encode(["error" => "Something went wrong. Please try again later."]));
}

$stmt->bindParam(":email", $email, PDO::PARAM_STR);

if (!$stmt->execute()) {
    die(json_encode(["error" => "Something went wrong. Please try again later."]));
}

if ($stmt->rowCount() != 1) {
    die(json_encode(["error" => "There is no account with that email address."]));
}

$row = $stmt->fetch();
$hashed_password = $row['contraseña'];
if (!password_verify($password, $hashed_password)) {
    die(json_encode(["error" => "The password you entered is not valid."]));
}

$_SESSION['usuario_id'] = $row['id_usuario'];
$_SESSION['nombre_completo'] = $row['nombre_completo'];
$_SESSION['rol_usuario'] = $row['rol_usuario'];

$redirectPage = $row['rol_usuario'] === 'admin' ? 'main_admin.php' : 'main_user.php';

setcookie("usuario", session_encode(), time() + 3600);

echo json_encode(["success" => true, "redirect" => $redirectPage]);

unset($stmt);
unset($pdo);
?>
