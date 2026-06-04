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

$data        = json_decode(file_get_contents('php://input'), true);
$profesor_id = intval($data['profesor_id'] ?? 0);
$horario_id  = intval($data['horario_id'] ?? 0);
$fecha       = $data['fecha'] ?? '';
$motivo      = $data['motivo'] ?? '';

if (!$profesor_id || !$horario_id || !$fecha || !$motivo) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit();
}

$sql_h  = "SELECT asignatura_id FROM horarios WHERE id = ?";
$stmt_h = $conn->prepare($sql_h);
$stmt_h->bind_param("i", $horario_id);
$stmt_h->execute();
$horario = $stmt_h->get_result()->fetch_assoc();

if (!$horario) {
    echo json_encode(['success' => false, 'error' => 'Horario no encontrado']);
    exit();
}
$asignatura_id = $horario['asignatura_id'];

$conn->begin_transaction();

try {
    $sql_asist  = "INSERT INTO asistencias (asignatura_id, fecha, presente) VALUES (?, ?, 1)";
    $stmt_asist = $conn->prepare($sql_asist);
    $stmt_asist->bind_param("is", $asignatura_id, $fecha);
    $stmt_asist->execute();
    $asistencia_id = $conn->insert_id;

    $descripcion    = "PREJUSTIFICACIÓN: " . $motivo;
    $fecha_completa = $fecha . " 08:00:00";
    $sql_inc        = "INSERT INTO incidencias (asistencia_id, justificada, descripcion, fecha_incidencia)
                       VALUES (?, 1, ?, ?)";
    $stmt_inc = $conn->prepare($sql_inc);
    $stmt_inc->bind_param("iss", $asistencia_id, $descripcion, $fecha_completa);
    $stmt_inc->execute();

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Error en BD: ' . $e->getMessage()]);
}

$conn->close();
?>