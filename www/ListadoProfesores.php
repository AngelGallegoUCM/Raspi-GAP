<?php
// Iniciar sesión y verificar autenticación
require_once("php/verificar_sesion.php");
verificarSesion();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Profesores</title>
    <link rel="stylesheet" href="styles.css">
    <style>
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

    /* ====== MODO TARJETAS EN MÓVIL (PROFESORES) ====== */
        @media (max-width: 768px) {
        .main-content{
            padding: 16px !important;
            padding-bottom: 90px !important; /* por el botón flotante del sidebar */
        }

        /* Formulario de búsqueda en columna */
        form[method="GET"]{
            display: grid !important;
            grid-template-columns: 1fr;
            gap: 10px;
            align-items: stretch !important;
        }

        form[method="GET"] input,
        form[method="GET"] button,
        form[method="GET"] .add-btn{
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            font-size: 16px;
        }

        form[method="GET"] .add-btn{
            display: inline-block;
            text-align: center;
        }

        /* Tabla -> tarjetas */
        .data-table thead{ display: none; }

        .data-table,
        .data-table tbody,
        .data-table tr,
        .data-table td{
            display: block;
            width: 100%;
        }

        .data-table tr{
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 12px;
            overflow: hidden;
            box-shadow: 0 6px 18px rgba(0,0,0,0.06);
        }

        .data-table td{
            border: 0;
            padding: 10px 12px;
            text-align: left;
        }

        .data-table td::before{
            content: attr(data-label);
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .03em;
            margin-bottom: 4px;
        }

        /* Nombre como título */
        .data-table td[data-label="Nombre"]{
            padding-top: 14px;
            padding-bottom: 14px;
            font-size: 16px;
            font-weight: 800;
        }
        .data-table td[data-label="Nombre"]::before{
            content: "Profesor";
        }

        /* Acciones */
        .data-table td[data-label="Acción"]{
            border-top: 1px solid #eef2f7;
            padding-top: 12px;
            padding-bottom: 14px;
        }
        .data-table td[data-label="Acción"]::before{
            content: "Acciones";
        }

        .data-table td[data-label="Acción"] a,
        .data-table td[data-label="Acción"] button{
            display: inline-block;
            margin: 6px 6px 0 0;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 14px;
        }

        /* Paginación responsive */
        .pagination{
            flex-wrap: wrap;
            gap: 8px;
        }
        }

    </style>
    <script>
        // Función para mostrar confirmación antes de eliminar
        function confirmarEliminacion(id) {
            if (confirm("¿Estás seguro de que deseas eliminar este profesor?")) {
                window.location.href = "php/EliminarProfesor.php?id=" + id;
            }
        }
        
        // Función para mostrar mensajes temporales
        window.onload = function() {
            const msgSuccess = document.getElementById('msg-success');
            if (msgSuccess) {
                setTimeout(function() {
                    msgSuccess.style.opacity = '0';
                    setTimeout(function() {
                        msgSuccess.style.display = 'none';
                    }, 500);
                }, 3000);
            }
        };
    </script>
</head>
<body>
    <?php include("php/sidebar.php"); ?> <!-- Incluir el sidebar -->

    <div class="main-content">
        <h1>Profesores</h1>
        <p>Profesor > Listado de todos los Profesores</p>
        
        <?php if (isset($_GET['success'])): ?>
        <div id="msg-success" class="success-message">
            <?php 
            $mensaje = "Operación realizada con éxito.";
            if ($_GET['success'] == '1') $mensaje = "Profesor añadido correctamente.";
            if ($_GET['success'] == '2') $mensaje = "Profesor actualizado correctamente.";
            if ($_GET['success'] == '3') $mensaje = "Profesor eliminado correctamente.";
            echo htmlspecialchars($mensaje);
            ?>
        </div>
        <?php endif; ?>

        <!-- Barra de búsqueda con protección XSS -->
        <form method="GET" action="" style="display: flex; gap: 10px; align-items: center;">
            <input type="text" name="search" placeholder="Buscar por nombre o apellidos" 
                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit">Buscar</button>
            <!-- Botón para eliminar el filtro -->
            <button type="button" onclick="window.location.href='ListadoProfesores.php'">Eliminar Filtro</button>
            
            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                <a href="AgregarProfesor.php" class="add-btn">Añadir Profesor</a>
            <?php endif; ?>
        </form>

        <!-- Tabla dinámica -->
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Apellidos</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Conexión a la base de datos
                include("php/conexion.php");

                // Configuración de paginación
                $registros_por_pagina = 12;
                $pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
                $offset = ($pagina_actual - 1) * $registros_por_pagina;

                // Obtener el término de búsqueda si existe y preparar la consulta
                $search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : null;

                // Consulta preparada para evitar inyección SQL
                $query = "SELECT * FROM profesores";
                $params = [];
                $types = "";
                
                if (!empty($search)) {
                    $query .= " WHERE nombre LIKE ? OR apellidos LIKE ?";
                    $params = [$search, $search];
                    $types = "ss";
                }
                
                // Consulta para obtener el total de registros
                $query_count = str_replace("SELECT *", "SELECT COUNT(*) as total", $query);
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
                $query .= " ORDER BY apellidos, nombre LIMIT ? OFFSET ?";
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

                // Verificar si hay resultados
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td data-label='Nombre'>" . htmlspecialchars($row['nombre']) . "</td>";
                        echo "<td data-label='Apellidos'>" . htmlspecialchars($row['apellidos']) . "</td>";
                        echo "<td data-label='Acción'>";
                        
                        // Todos pueden ver
                        echo "<a href='VerDatosProfesor.php?id=" . htmlspecialchars($row['id']) . "' class='view-btn'>Ver</a>";
                        
                        // Solo admin y editor pueden modificar
                        if (in_array($_SESSION['rol'], ['admin', 'editor'])) {
                            echo " | <a href='ModificarProfesor.php?id=" . htmlspecialchars($row['id']) . "' class='edit-btn'>Modificar</a>";
                        }
                        
                        // Solo admin puede eliminar
                        if ($_SESSION['rol'] === 'admin') {
                            echo " | <button onclick='confirmarEliminacion(" . htmlspecialchars($row['id']) . ")' class='delete-btn'>Eliminar</button>";
                        }
                        
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='3'>No se encontraron resultados.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Paginación -->
        <?php if ($total_registros > 0): ?>
        <div class="pagination">
            <?php
            // Construir la URL base para los enlaces de paginación, manteniendo los parámetros de búsqueda
            $url_params = [];
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $url_params[] = "search=" . urlencode($_GET['search']);
            }
            $url_base = "ListadoProfesores.php?" . implode("&", $url_params);
            
            // Agregar separador si ya hay parámetros
            $url_base .= !empty($url_params) ? "&" : "";
            
            // Enlace a la primera página
            if ($pagina_actual > 1) {
                echo "<a href='{$url_base}pagina=1'>&laquo; Primera</a>";
                echo "<a href='{$url_base}pagina=" . ($pagina_actual - 1) . "'>&lt; Anterior</a>";
            } else {
                echo "<span class='disabled'>&laquo; Primera</span>";
                echo "<span class='disabled'>&lt; Anterior</span>";
            }
            
            // Mostrar un rango de páginas
            $rango = 2; // Número de páginas a mostrar a cada lado de la página actual
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
    </div>
</body>
</html>