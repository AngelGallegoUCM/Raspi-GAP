<?php
require_once("php/api_auth.php");
verificarSesionApi();

$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Credentials: true');

include("php/conexion.php");

$profesor_id = intval($_GET['profesor_id'] ?? 0);
if ($profesor_id === 0) {
    echo json_encode(['success' => false, 'error' => 'ID no válido']);
    exit();
}

$stmt = $conn->prepare("
    SELECT a.nombre_asignatura, a.grupo, au.numero_aula,
           h.dia_semana, h.hora_inicio, h.hora_fin
    FROM asignaturas a
    JOIN horarios h ON h.asignatura_id = a.id
    LEFT JOIN aulas au ON a.aula_id = au.id
    WHERE a.profesor_id = ?
    ORDER BY FIELD(h.dia_semana,'Lunes','Martes','Miércoles','Jueves','Viernes'), h.hora_inicio
");
$stmt->bind_param("i", $profesor_id);
$stmt->execute();
$result = $stmt->get_result();

$horario = [];
while ($row = $result->fetch_assoc()) {
    $horario[] = $row;
}
echo json_encode(['success' => true, 'data' => $horario]);
$conn->close();
?>