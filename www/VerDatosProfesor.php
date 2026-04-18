<?php
// Iniciar sesión y verificar autenticación
require_once("php/verificar_sesion.php");
verificarSesion();
// Para ver los datos solo se necesita estar autenticado, cualquier rol puede acceder
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datos de Profesor</title>
    <link rel="stylesheet" href="stylesDatos.css">
    <style>
        /* Estilos para el horario */
        .horario-container {
            display: none;
            margin-top: 20px;
            width: 100%;
            overflow-x: auto;
        }
        
        .horario-title {
            margin-bottom: 10px;
            font-weight: bold;
            text-align: center;
            font-size: 16px;
            text-transform: uppercase;
        }
        
        .horario-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .horario-table th {
            background-color: #000080;
            color: white;
            text-align: center;
            padding: 8px;
            border: 1px solid #000;
        }
        
        .horario-table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
            height: 40px;
            vertical-align: middle;
            font-size: 12px;
        }
        
        .horario-table td.hora {
            background-color: #e6f2ff;
            font-weight: bold;
        }
        
        .horario-table td.asignatura {
            font-size: 11px;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
            background-color: #f8f9fa;
        }
        
        .info-buttons {
            display: flex;
            margin-bottom: 15px;
            gap: 10px;
        }
        
        .info-btn {
            padding: 8px 15px;
            background-color: #4e73df;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .info-btn:hover {
            background-color: #2e59d9;
        }
        
        .info-btn.active {
            background-color: #224abe;
            font-weight: bold;
        }
        
        /* Estilos para botones de acción */
        .action-buttons {
            display: flex;
            margin-top: 20px;
            gap: 10px;
        }
        
        .action-buttons button, 
        .action-buttons a {
            padding: 8px 15px;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
            display: inline-block;
            text-align: center;
            font-size: 14px;
        }
        
        .volver {
            background-color: #6c757d;
        }
        
        .edit-btn {
            background-color: #28a745;
        }
        
        .delete-btn {
            background-color: #dc3545;
        }

        /* Estilos para el campo de identificador con botón NFC */
        .identificador-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .identificador-group input {
            flex: 1;
        }

        .nfc-btn {
            padding: 10px 20px;
            background-color: #ff6600;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nfc-btn:hover {
            background-color: #ff8533;
        }

        .nfc-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        .nfc-btn .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Modal para mensajes NFC */
        .nfc-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .nfc-modal.show {
            display: flex;
        }

        .nfc-modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .nfc-modal-content h3 {
            margin-top: 0;
            color: #333;
        }

        .nfc-modal-content p {
            margin: 15px 0;
            color: #666;
        }

        .nfc-modal-content .nfc-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .nfc-modal-content button {
            margin-top: 15px;
            padding: 10px 20px;
            background-color: #4e73df;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .nfc-modal-content button:hover {
            background-color: #2e59d9;
        }

        .nfc-status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            font-weight: 500;
        }

        .nfc-status.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .nfc-status.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .nfc-status.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        /* Estilos para dispositivos móviles */
        @media (max-width: 768px) {
            .horario-table {
                font-size: 11px;
            }
            
            .horario-table td {
                padding: 4px;
            }
            
            .horario-table td.asignatura {
                font-size: 10px;
                padding: 2px;
            }

            .identificador-group {
                flex-direction: column;
            }

            .identificador-group input,
            .nfc-btn {
                width: 100%;
            }

            .nfc-btn {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php
    // Conexión a la base de datos
    include("php/conexion.php");

    // Validar y obtener el ID del profesor
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $profesor_id = intval($_GET['id']);

        // Consulta para obtener los datos del profesor y su departamento usando consulta preparada
        $query_profesor = "
            SELECT p.nombre, p.apellidos, p.identificador, p.CorreoPropio, d.nombre_departamento, d.correo_departamento 
            FROM profesores p
            LEFT JOIN departamento d ON p.departamento_id = d.id
            WHERE p.id = ?";

        $stmt = $conn->prepare($query_profesor);
        $stmt->bind_param("i", $profesor_id);
        $stmt->execute();
        $result_profesor = $stmt->get_result();

        if ($result_profesor->num_rows > 0) {
            $profesor = $result_profesor->fetch_assoc();
        } else {
            die("Profesor no encontrado.");
        }

        // Consulta para obtener las asignaturas y horarios del profesor usando consulta preparada
        $query_asignaturas = "
            SELECT a.id, a.nombre_asignatura, a.grupo, au.numero_aula
            FROM asignaturas a
            JOIN aulas au ON a.aula_id = au.id
            WHERE a.profesor_id = ?";

        $stmt = $conn->prepare($query_asignaturas);
        $stmt->bind_param("i", $profesor_id);
        $stmt->execute();
        $result_asignaturas = $stmt->get_result();
        
        // Consulta para obtener todos los horarios del profesor
        $query_horarios = "
            SELECT a.nombre_asignatura, a.grupo, au.numero_aula, h.dia_semana, 
                   h.hora_inicio, h.hora_fin 
            FROM asignaturas a
            JOIN aulas au ON a.aula_id = au.id
            JOIN horarios h ON a.id = h.asignatura_id
            WHERE a.profesor_id = ?
            ORDER BY FIELD(h.dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'),
                     h.hora_inicio";

        $stmt = $conn->prepare($query_horarios);
        $stmt->bind_param("i", $profesor_id);
        $stmt->execute();
        $result_horarios = $stmt->get_result();
        
        // Preparar el array de días
        $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
        
        // Las horas para mostrar en la cuadrícula (9:00 - 20:00)
        $horas_display = [];
        for ($h = 9; $h <= 20; $h++) {
            $horas_display[] = $h;
        }
        
        // Estructura para almacenar las clases con su duración
        $horario_clases = [];
        
        // Llenar el array de horario con los datos obtenidos
        if ($result_horarios && $result_horarios->num_rows > 0) {
            while ($row = $result_horarios->fetch_assoc()) {
                $dia = $row['dia_semana'];
                $hora_inicio_obj = new DateTime($row['hora_inicio']);
                $hora_fin_obj = new DateTime($row['hora_fin']);
                
                $hora_inicio_num = (int)$hora_inicio_obj->format('G');
                $hora_fin_num = (int)$hora_fin_obj->format('G');
                
                if ($hora_fin_obj->format('i') > 0) {
                    $hora_fin_num++;
                }
                
                $duracion = $hora_fin_num - $hora_inicio_num;
                if ($duracion < 1) $duracion = 1;
                
                if ($hora_inicio_num >= 9 && $hora_inicio_num <= 20) {
                    if (strtoupper($row['nombre_asignatura']) == 'FAL' || strtoupper($row['nombre_asignatura']) == 'SO') {
                        $siglas = strtoupper($row['nombre_asignatura']);
                    } else {
                        $nombre_parts = explode(' ', $row['nombre_asignatura']);
                        if (count($nombre_parts) > 1) {
                            $siglas = '';
                            foreach ($nombre_parts as $part) {
                                if (!empty($part)) {
                                    $siglas .= strtoupper(substr($part, 0, 1));
                                }
                            }
                        } else {
                            $siglas = strtoupper(substr($row['nombre_asignatura'], 0, 3));
                        }
                    }
                    
                    $horario_clases[$dia][$hora_inicio_num] = [
                        'asignatura' => $siglas,
                        'grupo' => $row['grupo'],
                        'aula' => $row['numero_aula'],
                        'duracion' => $duracion
                    ];
                }
            }
        }

    } else {
        die("ID del profesor no especificado o inválido.");
    }
    ?>

    <!-- Modal para mensajes NFC -->
    <div id="nfcModal" class="nfc-modal">
        <div class="nfc-modal-content">
            <div class="nfc-icon" id="nfcIcon">📱</div>
            <h3 id="nfcTitle">Escritura NFC</h3>
            <div id="nfcStatus" class="nfc-status info" style="display: none;"></div>
            <p id="nfcMessage">Acerca tu dispositivo a la tarjeta NFC...</p>
            <button id="closeModal" style="display: none;">Cerrar</button>
        </div>
    </div>

    <div class="container">
        <?php include("php/sidebar.php"); ?>
        <main class="content">
            <h1>Profesores</h1>
            <h2>Datos de Profesor</h2>

            <div class="form-container">
                <form>
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" id="nombre" value="<?php echo htmlspecialchars($profesor['nombre']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="apellidos">Apellidos</label>
                        <input type="text" id="apellidos" value="<?php echo htmlspecialchars($profesor['apellidos']); ?>" readonly>
                    </div>

                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                    <div class="form-group">
                        <label for="identificador">Identificador</label>
                        <div class="identificador-group">
                            <input type="text" id="identificador" value="<?php echo htmlspecialchars($profesor['identificador']); ?>" readonly>
                            <button type="button" id="writeNfcBtn" class="nfc-btn">
                                <span>Escribir en NFC</span>
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="correo">Correo Propio</label>
                        <input type="text" id="correo" value="<?php echo htmlspecialchars($profesor['CorreoPropio']); ?>" readonly>
                    </div>

                    <?php if (!empty($profesor['nombre_departamento'])): ?>
                        <div class="form-group">
                            <label for="departamento">Departamento</label>
                            <input type="text" id="departamento" value="<?php echo htmlspecialchars($profesor['nombre_departamento']); ?>" readonly>

                            <label for="correo-departamento">Correo Departamento</label>
                            <input type="text" id="correo-departamento" value="<?php echo htmlspecialchars($profesor['correo_departamento']); ?>" readonly>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Botones de información -->
                    <div class="info-buttons">
                        <button type="button" id="btn-asignaturas" class="info-btn active" onclick="mostrarSeccion('asignaturas')">Asignaturas</button>
                        <button type="button" id="btn-horario" class="info-btn" onclick="mostrarSeccion('horario')">Horario</button>
                    </div>

                    <!-- Sección de asignaturas -->
                    <div id="seccion-asignaturas" class="info-section">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Asignaturas</th>
                                    <th>Grupo</th>
                                    <th>Número del Aula</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result_asignaturas && $result_asignaturas->num_rows > 0) {
                                    while ($asignatura = $result_asignaturas->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($asignatura['nombre_asignatura']) . "</td>";
                                        echo "<td>" . htmlspecialchars($asignatura['grupo']) . "</td>";
                                        echo "<td>" . htmlspecialchars($asignatura['numero_aula']) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='3'>No hay asignaturas registradas para este profesor.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Sección de horario -->
                    <div id="seccion-horario" class="info-section horario-container">
                        <div class="horario-title">HORARIO</div>
                        <table class="horario-table">
                            <thead>
                                <tr>
                                    <th width="5%"></th>
                                    <?php foreach ($dias as $dia): ?>
                                        <th width="19%"><?php echo strtoupper($dia); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $ocupadas = [];
                                foreach ($dias as $dia) {
                                    foreach ($horas_display as $hora) {
                                        $ocupadas[$dia][$hora] = false;
                                    }
                                }
                                
                                foreach ($horas_display as $hora): 
                                    echo "<tr>";
                                    echo "<td class='hora'>{$hora}</td>";
                                    
                                    foreach ($dias as $dia): 
                                        if (isset($ocupadas[$dia][$hora]) && $ocupadas[$dia][$hora] === true) {
                                            continue;
                                        }
                                        
                                        if (isset($horario_clases[$dia][$hora])) {
                                            $clase = $horario_clases[$dia][$hora];
                                            if ($clase['duracion'] > 1) {
                                                echo "<td class='asignatura' rowspan='{$clase['duracion']}'>";
                                                echo $clase['asignatura'] . " (" . $clase['grupo'] . ")<br>Aula " . $clase['aula'];
                                                echo "</td>";
                                                
                                                for ($i = 1; $i < $clase['duracion']; $i++) {
                                                    if (isset($ocupadas[$dia][$hora + $i])) {
                                                        $ocupadas[$dia][$hora + $i] = true;
                                                    }
                                                }
                                            } else {
                                                echo "<td class='asignatura'>";
                                                echo $clase['asignatura'] . " (" . $clase['grupo'] . ")<br>Aula " . $clase['aula'];
                                                echo "</td>";
                                            }
                                        } else {
                                            echo "<td></td>";
                                        }
                                    endforeach;
                                    
                                    echo "</tr>";
                                endforeach; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </form>

                <!-- Botones de acción según el rol del usuario -->
                <div class="action-buttons">
                    <button type="button" class="volver" onclick="window.location.href='ListadoProfesores.php'">Volver</button>
                    
                    <?php if (isset($_SESSION['rol']) && in_array($_SESSION['rol'], ['admin', 'editor'])): ?>
                    <a href="ModificarProfesor.php?id=<?php echo $profesor_id; ?>" class="edit-btn">Modificar</a>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                    <button type="button" class="delete-btn" onclick="confirmarEliminacion(<?php echo $profesor_id; ?>)">Eliminar</button>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script>
        // Función para mostrar la sección seleccionada
        function mostrarSeccion(seccion) {
            var seccionAsignaturas = document.getElementById('seccion-asignaturas');
            var seccionHorario = document.getElementById('seccion-horario');
            var btnAsignaturas = document.getElementById('btn-asignaturas');
            var btnHorario = document.getElementById('btn-horario');
            
            if (seccionAsignaturas) seccionAsignaturas.style.display = 'none';
            if (seccionHorario) seccionHorario.style.display = 'none';
            
            if (btnAsignaturas) btnAsignaturas.classList.remove('active');
            if (btnHorario) btnHorario.classList.remove('active');
            
            if (seccion === 'asignaturas') {
                if (seccionAsignaturas) {
                    seccionAsignaturas.style.display = 'block';
                    if (btnAsignaturas) btnAsignaturas.classList.add('active');
                }
            } else if (seccion === 'horario') {
                if (seccionHorario) {
                    seccionHorario.style.display = 'block';
                    if (btnHorario) btnHorario.classList.add('active');
                }
            }
        }
        
        // Función para confirmar la eliminación del profesor
        function confirmarEliminacion(id) {
            if (confirm("¿Estás seguro de que deseas eliminar este profesor? Si tiene asignaturas asignadas, no podrá ser eliminado.")) {
                window.location.href = "php/EliminarProfesor.php?id=" + id;
            }
        }
        
        // Iniciar mostrando la sección de asignaturas por defecto
        document.addEventListener('DOMContentLoaded', function() {
            mostrarSeccion('asignaturas');
        });

        // ========== FUNCIONALIDAD NFC ==========
        
        // Elementos del DOM
        const writeNfcBtn = document.getElementById('writeNfcBtn');
        const nfcModal = document.getElementById('nfcModal');
        const closeModal = document.getElementById('closeModal');
        const nfcIcon = document.getElementById('nfcIcon');
        const nfcTitle = document.getElementById('nfcTitle');
        const nfcStatus = document.getElementById('nfcStatus');
        const nfcMessage = document.getElementById('nfcMessage');

        // Función para verificar soporte NFC
        function checkNFCSupport() {
            if (!('NDEFReader' in window)) {
                return {
                    supported: false,
                    message: 'Tu navegador no soporta Web NFC. Necesitas Chrome en Android.'
                };
            }
            return { supported: true };
        }

        // Función para mostrar el modal
        function showModal() {
            nfcModal.classList.add('show');
        }

        // Función para ocultar el modal
        function hideModal() {
            nfcModal.classList.remove('show');
        }

        // Función para actualizar el estado del modal
        function updateModalStatus(icon, title, message, statusType = null) {
            nfcIcon.textContent = icon;
            nfcTitle.textContent = title;
            nfcMessage.textContent = message;
            
            if (statusType) {
                nfcStatus.style.display = 'block';
                nfcStatus.className = `nfc-status ${statusType}`;
                nfcStatus.textContent = message;
            } else {
                nfcStatus.style.display = 'none';
            }
        }

        // Evento del botón de escritura NFC
        if (writeNfcBtn) {
            writeNfcBtn.addEventListener('click', async () => {
                // Verificar soporte NFC
                const nfcCheck = checkNFCSupport();
                if (!nfcCheck.supported) {
                    alert(nfcCheck.message);
                    return;
                }

                // Obtener el identificador del profesor
                const identificador = document.getElementById('identificador').value.trim();
                if (!identificador) {
                    alert('No hay identificador para escribir');
                    return;
                }

                // Mostrar modal
                showModal();
                updateModalStatus('', 'Escritura NFC', 'Acerca tu dispositivo a la tarjeta NFC...');
                closeModal.style.display = 'none';

                // Deshabilitar botón
                writeNfcBtn.disabled = true;
                writeNfcBtn.innerHTML = '<span class="loading"></span><span>Escribiendo...</span>';

                try {
                    const ndef = new NDEFReader();
                    await ndef.write({
                        records: [{
                            recordType: "text",
                            data: identificador
                        }]
                    });

                    // Éxito
                    updateModalStatus('', 'Escritura Exitosa', `Se escribió correctamente: "${identificador}"`, 'success');
                    closeModal.style.display = 'block';

                } catch (error) {
                    console.error('Error NFC:', error);
                    
                    let errorMessage = 'Error desconocido';
                    if (error.name === 'NotAllowedError') {
                        errorMessage = 'Permiso denegado. Activa NFC en tu dispositivo.';
                    } else if (error.name === 'NetworkError') {
                        errorMessage = 'No se detectó ninguna tarjeta NFC. Intenta de nuevo.';
                    } else {
                        errorMessage = error.message;
                    }

                    updateModalStatus('❌', 'Error de Escritura', errorMessage, 'error');
                    closeModal.style.display = 'block';
                } finally {
                    // Rehabilitar botón
                    writeNfcBtn.disabled = false;
                    writeNfcBtn.innerHTML = '<span>📱</span><span>Escribir en NFC</span>';
                }
            });
        }

        // Evento del botón cerrar modal
        if (closeModal) {
            closeModal.addEventListener('click', hideModal);
        }

        // Cerrar modal al hacer clic fuera
        nfcModal.addEventListener('click', (e) => {
            if (e.target === nfcModal) {
                hideModal();
            }
        });
    </script>
</body>
</html>