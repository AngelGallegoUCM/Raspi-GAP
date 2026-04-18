<?php
// Iniciar sesión y verificar autenticación
require_once("php/verificar_sesion.php");
verificarSesion();

$idProfesorReal = null;


?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Asistencia de Profesores</title>
    <link rel="stylesheet" href="style.css" />
  </head>
  <body>
    <div class="container">
      <!-- Barra lateral -->
      <?php include("php/sidebar.php"); ?> <!-- Incluir el sidebar -->

      <!-- Contenido principal -->
      <main class="main-content">
        <header class="header">
          <h1>Bienvenido a Gestión de Asistencia de Profesores</h1>
          <p>Panel de Control - Sistema GAP</p>
        </header>
        
        <!-- Panel de acceso rápido -->
        <div class="quick-access">
          <div class="section-heading">
            <h3>Gestión de Personal</h3>
          </div>
          <div class="modules-grid">
            <!-- Módulo de Profesores (solo admin y editor) -->
            <?php if (in_array($_SESSION['rol'], ['admin', 'editor'])): ?>
            <a href="ListadoProfesores.php" class="module-card">
              <div class="module-icon">👨‍🏫</div>
              <h4>Profesores</h4>
              <p>Listado y gestión del personal docente</p>
            </a>
            
            <!-- Módulo de Añadir Profesor (solo admin y editor) -->
           
              <a href="AgregarProfesor.php" class="module-card">
                <div class="module-icon">➕</div>
                <h4>Añadir Profesor</h4>
                <p>Registrar nuevo docente en el sistema</p>
              </a>

              <!-- Módulo de Aulas (solo admin y editor) -->
              <a href="ListadoAulas.php" class="module-card">
                <div class="module-icon">🏫</div>
                <h4>Aulas</h4>
                <p>Gestión de espacios y capacidades</p>
              </a>
              
              <!-- Módulo de Asignaturas (solo admin y editor) -->
              <a href="ListadoAsignaturas.php" class="module-card">
                <div class="module-icon">📚</div>
                <h4>Asignaturas</h4>
                <p>Administración de cursos y grupos</p>
              </a>
            <?php endif; ?>
            
            <?php 
                if ($_SESSION['rol'] === 'profesor' && !empty($_SESSION['IdProfesor'])): 
                    // 1. IMPORTANTE: Usamos $conn porque es la que define tu archivo conexion.php
                    include("php/conexion.php"); 
                    $identificador = $_SESSION['IdProfesor'];
                    
                    // 2. Cambiamos $conexion->prepare por $conn->prepare
                    $stmt_prof = $conn->prepare("SELECT id FROM profesores WHERE identificador = ?");
                    $stmt_prof->bind_param("s", $identificador);
                    $stmt_prof->execute();
                    $res_prof = $stmt_prof->get_result();
                    
                    if ($row_prof = $res_prof->fetch_assoc()):
                        $idReal = $row_prof['id'];
                ?>
                    <a href="VerDatosProfesor.php?id=<?php echo $idReal; ?>" class="module-card prof-module">
                        <div class="module-icon">📅</div>
                        <h4>Mi Horario</h4>
                        <p>Consulta tus clases y horarios asignados</p>
                    </a>
                <?php 
                    endif;
                    $stmt_prof->close();
                endif; 
                ?>

          </div>
        </div>
        
        <!-- Panel de calendario y asistencias -->
        <div class="quick-access">
          <div class="section-heading">
            <h3>Calendario y Asistencias</h3>
          </div>
          <div class="modules-grid">
            <!-- Módulo de Días No Lectivos -->
            <a href="ListadoNoLectivo.php" class="module-card">
              <div class="module-icon">🎆</div>
              <h4>Días No Lectivos</h4>
              <p>Gestión del calendario académico</p>
            </a>
          
            <!-- Módulo de Incidencias -->
            <?php if (in_array($_SESSION['rol'], ['admin', 'editor', 'profesor'])): ?>
              <a href="ListadoIncidencias.php" class="module-card">
                <div class="module-icon">⚠️</div>
                <h4>Incidencias</h4>
                <p>
                  <?php if ($_SESSION['rol'] === 'profesor'): ?>
                      Consulta tus incidencias
                  <?php else: ?>
                      Gestión de faltas y justificaciones
                  <?php endif; ?>
                </p>
              </a>
            <?php endif; ?>

            <?php if ($_SESSION['rol'] === 'profesor'): ?>
              <a href="Prejustificacion.php" class="module-card">
                  <div class="module-icon">📋</div>
                  <h4>Prejustificar Incidencia</h4>
                  <p>Notificar ausencias futuras</p>
              </a>
            <?php endif; ?>

            <?php if ($_SESSION['rol'] === 'admin'): ?>
              <a href="ListadoNodos.php" class="module-card admin-module">
                <div class="module-icon">🛜</div>
                <h4>Nodos</h4>
                <p>Nodos activos</p>
              </a>
            <?php endif; ?>

            <!-- Módulo de Estadísticas (solo admin) -->
            <?php if ($_SESSION['rol'] === 'admin'): ?>
            <a href="VerEstadisticas.php" class="module-card admin-module">
              <div class="module-icon">📊</div>
              <h4>Estadísticas</h4>
              <p>Reportes y análisis de asistencia</p>
            </a>
            <?php endif; ?>
            
            <!-- Módulo de configuración (solo admin) -->
            <?php if ($_SESSION['rol'] === 'admin'): ?>
            <a href="GestionUsuarios.php" class="module-card admin-module">
              <div class="module-icon">⚙️</div>
              <h4>Configuración</h4>
              <p>Ajustes del sistema</p>
            </a>
            <?php endif; ?>

            

            
          </div>
        </div>
        
        <?php if ($_SESSION['rol'] === 'admin'): ?>
        <!-- Panel de administración - solo visible para administradores -->
        <div class="admin-panel">
          <h3>Panel de Administración</h3>
          <p>Como administrador del sistema, tienes acceso completo a todas las funcionalidades:</p>
          <ul>
            <li>Gestión completa de profesores, aulas y asignaturas (añadir, modificar, eliminar)</li>
            <li>Administración del calendario académico y días no lectivos</li>
            <li>Gestión de incidencias y justificaciones</li>
            <li>Acceso a estadísticas y reportes completos del sistema</li>
            <li>Configuración general del sistema GAP</li>
          </ul>
        </div>
        
        
        
        <?php endif; ?>

      </main>
    </div>
  </body>
</html>