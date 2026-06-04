<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// 1. Conexión a la base de datos

include("php/conexion.php"); 

// 2. Obtener parámetros de la App (profesor_id y dia)
$profesor_id = intval($_GET['profesor_id'] ?? 0);
$dia         = $_GET['dia'] ?? '';

if ($profesor_id === 0 || empty($dia)) {
    echo json_encode([
        'success' => false, 
        'error' => 'Faltan parámetros: profesor_id o dia'
    ]);
    exit();
}

// 3. Consulta SQL con JOIN

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

// 4. Devolver respuesta a la App
echo json_encode([
    'success' => true, 
    'data' => $clases
]);

$stmt->close();
$conn->close();
?>