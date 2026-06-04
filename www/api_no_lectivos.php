<?php
require_once("php/api_auth.php");
verificarSesionApi();

$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Credentials: true');

include("php/conexion.php");

$query = "SELECT DATE_FORMAT(fecha, '%d/%m/%Y') as fecha, descripcion
          FROM nolectivo
          ORDER BY fecha DESC";

$result = $conn->query($query);
$dias   = [];

while ($row = $result->fetch_assoc()) {
    $dias[] = [
        'fecha'  => $row['fecha'],
        'motivo' => $row['descripcion']
    ];
}

echo json_encode(['success' => true, 'data' => $dias]);
$conn->close();
?>