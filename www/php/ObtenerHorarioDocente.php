<?php
require_once("verificar_sesion.php");
include("conexion.php");

$dia = $_GET['dia'] ?? '';
$identificador = $_SESSION['IdProfesor'];

$query = "SELECT a.id, a.nombre_asignatura, h.hora_inicio, h.hora_fin 
          FROM asignaturas a
          JOIN horarios h ON a.id = h.asignatura_id
          JOIN profesores p ON a.profesor_id = p.id
          WHERE p.identificador = ? AND h.dia_semana = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $identificador, $dia);
$stmt->execute();
$result = $stmt->get_result();

$clases = [];
while ($row = $result->fetch_assoc()) {
    $clases[] = $row;
}

echo json_encode($clases);