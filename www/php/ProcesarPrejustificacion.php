<?php
require_once("verificar_sesion.php");
include("conexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $_POST['fecha'];
    $asignatura_id = $_POST['asignatura_horario_id'];
    $motivo = "PREJUSTIFICACIÓN: " . $_POST['motivo'];

    // 1. Crear el registro de asistencia por adelantado
    $stmt_asistencia = $conn->prepare("INSERT INTO asistencias (asignatura_id, fecha, presente) VALUES (?, ?, 0)");
    $stmt_asistencia->bind_param("is", $asignatura_id, $fecha);
    
    if ($stmt_asistencia->execute()) {
        $asistencia_id = $conn->insert_id;

        // 2. Crear la incidencia ya justificada
        $stmt_incidencia = $conn->prepare("INSERT INTO incidencias (asistencia_id, justificada, descripcion, fecha_incidencia) VALUES (?, 1, ?, ?)");
        // Se concatena la fecha con una hora ficticia (ej: inicio del día)
        $fecha_completa = $fecha . " 08:00:00"; 
        $stmt_incidencia->bind_param("iss", $asistencia_id, $motivo, $fecha_completa);
        $stmt_incidencia->execute();

        header("Location: ../ListadoIncidencias.php?success=2");
    } else {
        echo "Error al procesar la prejustificación.";
    }
}