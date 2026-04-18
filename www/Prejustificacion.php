<?php
require_once("php/verificar_sesion.php");
verificarSesion();
include("php/conexion.php");

// Restringir acceso solo a profesores
if ($_SESSION['rol'] !== 'profesor') {
    header("Location: index.php");
    exit();
}

$identificador = $_SESSION['IdProfesor'];

// Obtener el ID interno del profesor
$stmt_prof = $conn->prepare("SELECT id FROM profesores WHERE identificador = ?");
$stmt_prof->bind_param("s", $identificador);
$stmt_prof->execute();
$idProfesorReal = $stmt_prof->get_result()->fetch_assoc()['id'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prejustificar Incidencia - GAP</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 20px auto;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #4e73df; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d3e2;
            border-radius: 8px;
            box-sizing: border-box;
        }
        .btn-submit {
            background: #4e73df;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }
        .btn-submit:hover { background: #2e59d9; }
        .info-box {
            background: #e7f0fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9em;
            color: #3859ad;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include("php/sidebar.php"); ?>

        <main class="main-content">
            <header class="header">
                <h1>Prejustificar Incidencia</h1>
                <p>Notifica una ausencia programada para que se genere como justificada.</p>
            </header>

            <div class="form-container">
                <div class="info-box">
                    Nota: Al seleccionar una fecha, el sistema mostrará solo las asignaturas que tienes programadas para ese día de la semana.
                </div>

                <form action="php/ProcesarPrejustificacion.php" method="POST">
                    <div class="form-group">
                        <label for="fecha">Fecha de la ausencia:</label>
                        <input type="date" id="fecha" name="fecha" required min="<?php echo date('Y-m-d'); ?>" onchange="cargarAsignaturas(this.value)">
                    </div>

                    <div class="form-group">
                        <label for="asignatura_horario">Asignatura y Horario:</label>
                        <select id="asignatura_horario" name="asignatura_horario_id" required disabled>
                            <option value="">Selecciona primero una fecha...</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="motivo">Motivo de la prejustificación:</label>
                        <textarea id="motivo" name="motivo" rows="4" placeholder="Ej: Asistencia a congreso, cita médica programada..." required></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Registrar Prejustificación</button>
                </form>
            </div>
        </main>
    </div>

    <script>
    function cargarAsignaturas(fechaSeleccionada) {
        const select = document.getElementById('asignatura_horario');
        const dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        const date = new Date(fechaSeleccionada);
        const diaSemana = dias[date.getDay()];

        select.innerHTML = '<option value="">Cargando horarios...</option>';
        select.disabled = true;

        // Llamada AJAX para obtener las clases del profesor ese día
        fetch(`php/ObtenerHorarioDocente.php?dia=${diaSemana}`)
            .then(response => response.json())
            .then(data => {
                select.innerHTML = '';
                if (data.length === 0) {
                    select.innerHTML = '<option value="">No tienes clases los ' + diaSemana + '</option>';
                } else {
                    select.disabled = false;
                    data.forEach(clase => {
                        const option = document.createElement('option');
                        option.value = clase.id; // ID de la asignatura
                        option.textContent = `${clase.nombre_asignatura} (${clase.hora_inicio} - ${clase.hora_fin})`;
                        select.appendChild(option);
                    });
                }
            });
    }
    </script>
</body>
</html>