<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include("php/conexion.php");

$data     = json_decode(file_get_contents('php://input'), true);
$username = $conn->real_escape_string($data['username'] ?? '');
$password = $data['password'] ?? '';

$stmt = $conn->prepare("SELECT id, username, password, rol, IdProfesor FROM usuarios WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
        if ($user['rol'] !== 'profesor') {
            echo json_encode(['success' => false, 'error' => 'Solo profesores pueden usar la app']);
            exit();
        }

        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['rol']        = $user['rol'];
        $_SESSION['IdProfesor'] = $user['IdProfesor'];

        $stmt2 = $conn->prepare("SELECT id, nombre, apellidos FROM profesores WHERE identificador = ?");
        $stmt2->bind_param("s", $user['IdProfesor']);
        $stmt2->execute();
        $prof = $stmt2->get_result()->fetch_assoc();

        echo json_encode([
            'success'     => true,
            'session_id'  => session_id(),
            'username'    => $user['username'],
            'IdProfesor'  => $user['IdProfesor'],
            'profesor_id' => $prof['id'],
            'nombre'      => $prof['nombre'] . ' ' . $prof['apellidos']
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Usuario o contraseña incorrecta']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Usuario o contraseña incorrecta']);
}
$stmt->close();
$conn->close();
?>