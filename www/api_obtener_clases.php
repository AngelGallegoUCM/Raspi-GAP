<?php
require_once("php/api_auth.php");
verificarSesionApi();

$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include("php/conexion.php");

$profesor_id = intval($_GET['profesor_id'] ?? 0);
$dia         = $_GET['dia'] ?? '';

if ($profesor_id === 0 || empty($dia)) {
    echo json_encode(['success' => false, 'error' => 'Faltan datos']);
    exit();
}

$sql = "SELECT h.id, a.nombre_asignatura, h.hora_inicio, h.hora_fin
        FROM horarios h
        INNER JOIN asignaturas a ON h.asignatura_id = a.id
        WHERE a.profesor_id = ? AND h.dia_semana = ?
        ORDER BY h.hora_inicio ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $profesor_id, $dia);
$stmt->execute();
$result = $stmt->get_result();

$clases = [];
while ($row = $result->fetch_assoc()) {
    $clases[] = $row;
}

echo json_encode(['success' => true, 'data' => $clases]);
$conn->close();
?>