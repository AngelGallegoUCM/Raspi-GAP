<?php
// Iniciar sesión y verificar autenticación
require_once("php/verificar_sesion.php");
verificarSesion();

// Verificar si el usuario es administrador
if ($_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Incluir conexión a la base de datos
require_once("php/conexion.php");

// Variable para mensajes
$mensaje = "";

// Determinar qué vista mostrar
$vista_actual = isset($_GET['vista']) ? $_GET['vista'] : 'listar';

// Manejar mensajes de operaciones de eliminación
if (isset($_GET['success']) && $_GET['success'] == 'deleted') {
    $mensaje = "<div class='alert alert-success'>Usuario eliminado correctamente</div>";
} else if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'self':
            $mensaje = "<div class='alert alert-danger'>No puede eliminar su propio usuario</div>";
            break;
        case 'lastadmin':
            $mensaje = "<div class='alert alert-danger'>No puede eliminar el último usuario administrador del sistema</div>";
            break;
        case 'notfound':
            $mensaje = "<div class='alert alert-danger'>El usuario que intenta eliminar no existe</div>";
            break;
        case 'delete':
            $mensaje = "<div class='alert alert-danger'>Error al eliminar el usuario</div>";
            break;
        case 'noid':
            $mensaje = "<div class='alert alert-danger'>ID de usuario no proporcionado</div>";
            break;
    }
}

// Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $nombre = $_POST['nombre'];
    $rol = $_POST['rol'];
    $id_profesor = isset($_POST['id_profesor']) ? trim($_POST['id_profesor']) : null;
    
    if (empty($username) || empty($password) || empty($nombre)) {
        $mensaje = "<div class='alert alert-danger'>Todos los campos son obligatorios</div>";
    } else if ($rol === 'profesor' && empty($id_profesor)) {
        $mensaje = "<div class='alert alert-danger'>Debe proporcionar el identificador del profesor para este rol</div>";
    } else {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $mensaje = "<div class='alert alert-danger'>El nombre de usuario ya existe</div>";
        } else {
            if ($rol === 'profesor') {
                $stmt = $conn->prepare("SELECT id FROM profesores WHERE identificador = ?");
                $stmt->bind_param("s", $id_profesor);
                $stmt->execute();
                $result_profesor = $stmt->get_result();
                
                if ($result_profesor->num_rows == 0) {
                    $mensaje = "<div class='alert alert-danger'>El identificador del profesor no existe en el sistema</div>";
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO usuarios (username, password, nombre, rol, IdProfesor) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $username, $password_hash, $nombre, $rol, $id_profesor);
                    
                    if ($stmt->execute()) {
                        $mensaje = "<div class='alert alert-success'>Usuario creado con éxito</div>";
                        $username = $password = $nombre = $id_profesor = "";
                        $rol = "lector";
                    } else {
                        $mensaje = "<div class='alert alert-danger'>Error al crear el usuario: " . $stmt->error . "</div>";
                    }
                }
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO usuarios (username, password, nombre, rol) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $username, $password_hash, $nombre, $rol);
                
                if ($stmt->execute()) {
                    $mensaje = "<div class='alert alert-success'>Usuario creado con éxito</div>";
                    $username = $password = $nombre = "";
                    $rol = "lector";
                } else {
                    $mensaje = "<div class='alert alert-danger'>Error al crear el usuario: " . $stmt->error . "</div>";
                }
            }
        }
    }
}

// Configuración de paginación
$registros_por_pagina = 7;
$pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Obtener el término de búsqueda si existe
$search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : null;

// Consulta preparada para evitar inyección SQL
$query = "SELECT id, username, nombre, rol, IdProfesor, fecha_creacion FROM usuarios";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " WHERE username LIKE ? OR nombre LIKE ?";
    $params = [$search, $search];
    $types = "ss";
}

// Consulta para obtener el total de registros
$query_count = str_replace("SELECT id, username, nombre, rol, IdProfesor, fecha_creacion", "SELECT COUNT(*) as total", $query);
$stmt_count = $conn->prepare($query_count);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$row_count = $result_count->fetch_assoc();
$total_registros = $row_count['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Añadir límite y offset para paginación
$query .= " ORDER BY fecha_creacion DESC LIMIT ? OFFSET ?";
$params[] = $registros_por_pagina;
$params[] = $offset;
$types .= "ii";

// Preparar y ejecutar la consulta
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Usuarios - Sistema GAP</title>
    <link rel="stylesheet" href="styles.css" />
    <style>
      /* Estilos para los botones de navegación */
      .view-toggle {
        display: flex;
        gap: 10px;
        margin-bottom: 25px;
        background-color: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      }
      
      .view-toggle-btn {
        flex: 1;
        padding: 12px 20px;
        border: 2px solid #e5e7eb;
        background-color: white;
        color: #666;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s;
        text-decoration: none;
        text-align: center;
        display: inline-block;
      }
      
      .view-toggle-btn:hover {
        border-color: #0066cc;
        color: #0066cc;
        background-color: #f0f7ff;
      }
      
      .view-toggle-btn.active {
        background-color: #0066cc;
        color: white;
        border-color: #0066cc;
      }

      /* Estilos adicionales específicos para la gestión de usuarios */
      .user-form {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        padding: 25px;
        margin-bottom: 30px;
      }
      
      .form-row {
        margin-bottom: 15px;
      }
      
      .form-row label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #444;
      }
      
      .form-row input[type="text"],
      .form-row input[type="password"],
      .form-row select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
      }
      
      .form-row input[type="text"]:focus,
      .form-row input[type="password"]:focus,
      .form-row select:focus {
        border-color: #003366;
        outline: none;
        box-shadow: 0 0 0 2px rgba(0,51,102,0.1);
      }
      
      .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
      }
      
      .btn {
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
      }
      
      .btn-create {
        background-color: #0066cc;
        color: white;
      }
      
      .btn-reset {
        background-color: #f2f2f2;
        color: #333;
      }
      
      .btn:hover {
        opacity: 0.9;
      }
      
      .section-title {
        margin: 0 0 20px 0;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
        color: #003366;
        font-size: 18px;
        font-weight: 600;
      }
      
      /* Estilos para las tablas */
      .users-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 30px;
        background-color: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      }
      
      .users-table th,
      .users-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
      }
      
      .users-table th {
        background-color: #f5f5f5;
        font-weight: 600;
        color: #444;
      }
      
      .users-table tr:last-child td {
        border-bottom: none;
      }
      
      .users-table tr:hover td {
        background-color: #f9f9f9;
      }
      
      .role-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
        text-transform: capitalize;
      }
      
      .role-admin {
        background-color: #e6f0ff;
        color: #0066cc;
      }
      
      .role-editor {
        background-color: #fff0e6;
        color: #ff6600;
      }
      
      .role-lector {
        background-color: #f2f2f2;
        color: #666666;
      }
      
      .role-profesor {
        background-color: #e6ffe6;
        color: #009900;
      }
      
      .action-buttons {
        display: flex;
        gap: 5px;
        justify-content: center;
      }
      
      .btn-action {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
        color: white;
        font-size: 14px;
      }
      
      .btn-edit {
        background-color: #ff9933;
      }
      
      .btn-delete {
        background-color: #ff3333;
      }
      
      /* Estilos para la sección de información */
      .info-box {
        background-color: #e6f7ff;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
      }
      
      .info-title {
        color: #003366;
        margin-bottom: 15px;
        font-size: 18px;
        font-weight: 600;
      }
      
      .role-info {
        margin-bottom: 10px;
      }
      
      .role-info strong {
        font-weight: 600;
        color: #333;
      }

      /* Estilos para el campo de contraseña con botón para mostrar/ocultar */
      .password-field-container {
        position: relative;
        width: 100%;
      }
      
      .password-field-container input {
        width: 100%;
        padding-right: 40px;
      }
      
      .password-toggle-btn {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        color: #666;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        transition: background-color 0.2s;
      }
      
      .password-toggle-btn:hover {
        background-color: rgba(0, 0, 0, 0.05);
      }
      
      .password-toggle-btn:focus {
        outline: none;
      }
      
      /* Estilos para el campo condicional de identificador */
      .hidden {
        display: none;
      }
      
      .form-row.highlighted {
        background-color: #fffbf0;
        padding: 15px;
        border-radius: 4px;
        border-left: 3px solid #ff9900;
      }

      /* Estilos para paginación */
      .pagination {
        display: flex;
        justify-content: center;
        margin: 20px 0;
        gap: 5px;
      }
      
      .pagination a, .pagination span {
        display: inline-block;
        padding: 8px 12px;
        text-decoration: none;
        border: 1px solid #ddd;
        color: #4e73df;
        border-radius: 4px;
      }
      
      .pagination a:hover {
        background-color: #f8f9fc;
      }
      
      .pagination .active {
        background-color: #4e73df;
        color: white;
        border-color: #4e73df;
      }
      
      .pagination .disabled {
        color: #aaa;
        cursor: not-allowed;
      }

      /* Estilos para el buscador */
      .search-form {
        display: flex;
        gap: 10px;
        align-items: center;
        margin-bottom: 20px;
        background-color: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      }

      .search-form input[type="text"] {
        flex: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
      }

      .search-form button {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
      }

      .search-form button[type="submit"] {
        background-color: #0066cc;
        color: white;
      }

      .search-form button[type="button"] {
        background-color: #f2f2f2;
        color: #333;
      }

      .search-form button:hover {
        opacity: 0.9;
      }

      /* MODO TARJETAS EN MÓVIL */
      @media (max-width: 768px) {
        .main-content {
          padding: 16px !important;
          padding-bottom: 90px !important;
        }

        .view-toggle {
          flex-direction: column;
        }

        .search-form {
          flex-direction: column;
          align-items: stretch;
        }

        .search-form input,
        .search-form button {
          width: 100%;
        }

        /* Tabla -> tarjetas */
        .users-table thead { 
          display: none; 
        }

        .users-table,
        .users-table tbody,
        .users-table tr,
        .users-table td {
          display: block;
          width: 100%;
        }

        .users-table tr {
          background: #fff;
          border: 1px solid #e5e7eb;
          border-radius: 12px;
          margin-bottom: 12px;
          overflow: hidden;
          box-shadow: 0 6px 18px rgba(0,0,0,0.06);
        }

        .users-table td {
          border: 0;
          padding: 10px 12px;
          text-align: left;
        }

        .users-table td::before {
          content: attr(data-label);
          display: block;
          font-size: 12px;
          font-weight: 700;
          color: #6b7280;
          text-transform: uppercase;
          letter-spacing: .03em;
          margin-bottom: 4px;
        }

        .users-table td:first-child {
          padding-top: 14px;
          padding-bottom: 14px;
          font-size: 16px;
          font-weight: 800;
        }

        .users-table td:last-child {
          border-top: 1px solid #eef2f7;
          padding-top: 12px;
          padding-bottom: 14px;
        }

        .pagination {
          flex-wrap: wrap;
          gap: 8px;
        }
      }
    </style>
  </head>
  <body>
    <div class="container">
      <!-- Barra lateral -->
      <?php include("php/sidebar.php"); ?>

      <!-- Contenido principal -->
      <main class="main-content">
        <header class="header">
          <h1>Gestión de Usuarios</h1>
          <p>Administración de cuentas del sistema</p>
        </header>
        
        <!-- Botones de navegación -->
        <div class="view-toggle">
          <a href="?vista=crear" class="view-toggle-btn <?php echo ($vista_actual === 'crear') ? 'active' : ''; ?>">
            Crear Nuevo Usuario
          </a>
          <a href="?vista=listar" class="view-toggle-btn <?php echo ($vista_actual === 'listar') ? 'active' : ''; ?>">
            Listar Usuarios
          </a>
        </div>

        <!-- Mensaje de resultado -->
        <?php echo $mensaje; ?>
        
        <?php if ($vista_actual === 'crear'): ?>
        <!-- Formulario para crear usuario -->
        <div class="user-form">
          <h2 class="section-title">Crear Nuevo Usuario</h2>
          <form method="POST" action="">
            <div class="form-row">
              <label for="username">Nombre de Usuario:</label>
              <input type="text" id="username" name="username" value="<?php echo isset($username) ? $username : ''; ?>" required>
            </div>
            
            <div class="form-row">
              <label for="password">Contraseña:</label>
              <div class="password-field-container">
                <input type="password" id="password" name="password" required>
                <button type="button" id="togglePassword" class="password-toggle-btn" title="Mostrar/ocultar contraseña">
                  🔒
                </button>
              </div>
            </div>
            
            <div class="form-row">
              <label for="nombre">Nombre Completo:</label>
              <input type="text" id="nombre" name="nombre" value="<?php echo isset($nombre) ? $nombre : ''; ?>" required>
            </div>
            
            <div class="form-row">
              <label for="rol">Rol:</label>
              <select id="rol" name="rol">
                <option value="lector" <?php echo (isset($rol) && $rol == 'lector') ? 'selected' : ''; ?>>Lector</option>
                <option value="editor" <?php echo (isset($rol) && $rol == 'editor') ? 'selected' : ''; ?>>Editor</option>
                <option value="profesor" <?php echo (isset($rol) && $rol == 'profesor') ? 'selected' : ''; ?>>Profesor</option>
                <option value="admin" <?php echo (isset($rol) && $rol == 'admin') ? 'selected' : ''; ?>>Administrador</option>
              </select>
            </div>
            
            <div class="form-row hidden" id="profesor-field">
              <label for="id_profesor">Identificador del Profesor: <span style="color: #ff3333;">*</span></label>
              <input type="text" id="id_profesor" name="id_profesor" value="<?php echo isset($id_profesor) ? $id_profesor : ''; ?>" placeholder="Ej: 00000001A">
              <small style="color: #666; display: block; margin-top: 5px;">El identificador debe existir en la tabla de profesores</small>
            </div>
            
            <div class="form-actions">
              <button type="submit" class="btn btn-create">Crear Usuario</button>
              <button type="reset" class="btn btn-reset">Limpiar</button>
            </div>
          </form>
        </div>
        
        <!-- Explicación de roles -->
        <div class="info-box">
          <h3 class="info-title">Información sobre Roles</h3>
          <div class="role-info">
            <p><strong>Administrador (admin):</strong> Acceso completo al sistema. Puede crear, editar y eliminar usuarios, profesores, asignaturas, y configurar todos los aspectos del sistema.</p>
          </div>
          <div class="role-info">
            <p><strong>Editor (editor):</strong> Puede gestionar profesores, asignaturas, aulas y registrar asistencias e incidencias, pero no puede configurar el sistema ni gestionar usuarios.</p>
          </div>
          <div class="role-info">
            <p><strong>Profesor (profesor):</strong> Acceso limitado para profesores del sistema. Requiere vincular el usuario con un identificador de profesor existente.</p>
          </div>
          <div class="role-info">
            <p><strong>Lector (lector):</strong> Solo puede ver la información, pero no puede realizar cambios en el sistema.</p>
          </div>
        </div>
        
        <?php else: ?>
        <!-- Listado de usuarios -->
        <h2 class="section-title">Usuarios del Sistema</h2>
        
        <!-- Barra de búsqueda -->
        <form method="GET" action="" class="search-form">
          <input type="hidden" name="vista" value="listar">
          <input type="text" name="search" placeholder="Buscar por nombre de usuario o nombre completo" 
                 value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
          <button type="submit">Buscar</button>
          <button type="button" onclick="window.location.href='?vista=listar'">Eliminar Filtro</button>
        </form>

        <table class="users-table">
          <thead>
            <tr>
              <th>Usuario</th>
              <th>Nombre</th>
              <th>Rol</th>
              <th>ID Profesor</th>
              <th>Fecha Creación</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $rolClass = 'role-' . $row['rol'];
                    
                    echo "<tr>";
                    echo "<td data-label='Usuario'>" . htmlspecialchars($row['username']) . "</td>";
                    echo "<td data-label='Nombre'>" . htmlspecialchars($row['nombre']) . "</td>";
                    echo "<td data-label='Rol'><span class='role-badge {$rolClass}'>" . htmlspecialchars($row['rol']) . "</span></td>";
                    echo "<td data-label='ID Profesor'>" . ($row['IdProfesor'] ? htmlspecialchars($row['IdProfesor']) : '-') . "</td>";
                    echo "<td data-label='Fecha Creación'>" . htmlspecialchars($row['fecha_creacion']) . "</td>";
                    echo "<td data-label='Acciones' class='action-buttons'>";
                    echo "<a href='EditarUsuario.php?id=" . $row['id'] . "' class='edit-btn' title='Editar'><span>Editar</span></a>";
                    echo "<a href='EliminarUsuario.php?id=" . $row['id'] . "' class='delete-btn' title='Eliminar' onclick='return confirm(\"¿Está seguro de eliminar este usuario?\");'><span>Eliminar</span></a>";
                    echo "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6' class='no-data'>No se encontraron usuarios</td></tr>";
            }
            ?>
          </tbody>
        </table>

        <!-- Paginación -->
        <?php if ($total_registros > 0): ?>
        <div class="pagination">
          <?php
          // Construir la URL base para los enlaces de paginación
          $url_params = ['vista=listar'];
          if (isset($_GET['search']) && !empty($_GET['search'])) {
              $url_params[] = "search=" . urlencode($_GET['search']);
          }
          $url_base = "GestionUsuarios.php?" . implode("&", $url_params) . "&";
          
          // Enlace a la primera página
          if ($pagina_actual > 1) {
              echo "<a href='{$url_base}pagina=1'>&laquo; Primera</a>";
              echo "<a href='{$url_base}pagina=" . ($pagina_actual - 1) . "'>&lt; Anterior</a>";
          } else {
              echo "<span class='disabled'>&laquo; Primera</span>";
              echo "<span class='disabled'>&lt; Anterior</span>";
          }
          
          // Mostrar un rango de páginas
          $rango = 2;
          for ($i = max(1, $pagina_actual - $rango); $i <= min($total_paginas, $pagina_actual + $rango); $i++) {
              if ($i == $pagina_actual) {
                  echo "<span class='active'>{$i}</span>";
              } else {
                  echo "<a href='{$url_base}pagina={$i}'>{$i}</a>";
              }
          }
          
          // Enlace a la última página
          if ($pagina_actual < $total_paginas) {
              echo "<a href='{$url_base}pagina=" . ($pagina_actual + 1) . "'>Siguiente &gt;</a>";
              echo "<a href='{$url_base}pagina={$total_paginas}'>Última &raquo;</a>";
          } else {
              echo "<span class='disabled'>Siguiente &gt;</span>";
              echo "<span class='disabled'>Última &raquo;</span>";
          }
          ?>
        </div>
        <p style="text-align: center;">
          Mostrando <?php echo min($registros_por_pagina, $result->num_rows); ?> de <?php echo $total_registros; ?> registros
          (Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?>)
        </p>
        <?php endif; ?>
        <?php endif; ?>
      </main>
    </div>
    <script>
    // Script para mostrar/ocultar contraseña
    const togglePasswordBtn = document.getElementById('togglePassword');
    if (togglePasswordBtn) {
      togglePasswordBtn.addEventListener('click', function() {
        const passwordField = document.getElementById('password');
        
        if (passwordField.type === 'password') {
          passwordField.type = 'text';
          this.textContent = '👁️';
          this.title = 'Ocultar contraseña';
        } else {
          passwordField.type = 'password';
          this.textContent = '🔒';
          this.title = 'Mostrar contraseña';
        }
      });
    }
    
    // Script para mostrar/ocultar campo de identificador de profesor
    const rolSelect = document.getElementById('rol');
    const profesorField = document.getElementById('profesor-field');
    const idProfesorInput = document.getElementById('id_profesor');
    
    if (rolSelect && profesorField) {
      function toggleProfesorField() {
        if (rolSelect.value === 'profesor') {
          profesorField.classList.remove('hidden');
          profesorField.classList.add('highlighted');
          idProfesorInput.setAttribute('required', 'required');
        } else {
          profesorField.classList.add('hidden');
          profesorField.classList.remove('highlighted');
          idProfesorInput.removeAttribute('required');
          idProfesorInput.value = '';
        }
      }
      
      // Ejecutar al cargar la página
      toggleProfesorField();
      
      // Ejecutar cuando cambie el rol
      rolSelect.addEventListener('change', toggleProfesorField);
      
      // Limpiar el campo de profesor al resetear el formulario
      const form = document.querySelector('form');
      if (form) {
        form.addEventListener('reset', function() {
          setTimeout(toggleProfesorField, 10);
        });
      }
    }