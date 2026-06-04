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
if ($profesor_id === 0) {
    echo json_encode(['success' => false, 'error' => 'ID no válido']);
    exit();
}

$conditions = ["p.id = ?"];
$params     = [$profesor_id];
$types      = "i";

if (!empty($_GET['inicio']) && !empty($_GET['fin'])) {
    $conditions[] = "DATE(i.fecha_incidencia) BETWEEN ? AND ?";
    $params[]     = $_GET['inicio'];
    $params[]     = $_GET['fin'];
    $types       .= "ss";
}

if (isset($_GET['justificada']) && in_array($_GET['justificada'], ['0', '1'])) {
    $conditions[] = "i.justificada = ?";
    $params[]     = intval($_GET['justificada']);
    $types       .= "i";
}

$where = "WHERE " . implode(" AND ", $conditions);

$query = "
    SELECT i.id,
           DATE_FORMAT(i.fecha_incidencia, '%d/%m/%Y') AS fecha,
           a.nombre_asignatura,
           i.justificada,
           i.descripcion
    FROM incidencias i
    JOIN asistencias s ON i.asistencia_id = s.id
    JOIN asignaturas a ON s.asignatura_id = a.id
    JOIN profesores  p ON a.profesor_id   = p.id
    $where
    ORDER BY i.fecha_incidencia DESC
    LIMIT 50
";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$incidencias = [];
while ($row = $result->fetch_assoc()) {
    $incidencias[] = [
        'id'                => $row['id'],
        'fecha'             => $row['fecha'],
        'nombre_asignatura' => $row['nombre_asignatura'],
        'justificada'       => (bool)$row['justificada'],
        'estado'            => $row['justificada'] ? 'Justificada' : 'No justificada',
        'descripcion'       => $row['descripcion'] ?? ''
    ];
}

echo json_encode(['success' => true, 'data' => $incidencias]);
$conn->close();
?>