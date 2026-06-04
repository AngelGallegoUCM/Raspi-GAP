<?php
require_once("php/api_auth.php");
verificarSesionApi();

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

$data          = json_decode(file_get_contents('php://input'), true);
$incidencia_id = intval($data['incidencia_id'] ?? 0);
$justificacion = $conn->real_escape_string($data['justificacion'] ?? '');

if ($incidencia_id === 0 || empty($justificacion)) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit();
}

$stmt = $conn->prepare("
    UPDATE incidencias
    SET justificada = 1, descripcion = ?
    WHERE id = ?
");
$stmt->bind_param("si", $justificacion, $incidencia_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al actualizar']);
}
$conn->close();
?>