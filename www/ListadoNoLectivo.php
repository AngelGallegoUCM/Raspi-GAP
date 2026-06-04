<?php
require_once("php/verificar_sesion.php");
verificarSesion();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Días No Lectivos</title>
    <link rel="stylesheet" href="styles.css">
    <style>
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
        .pagination a:hover { background-color: #f8f9fc; }
        .pagination .active { background-color: #4e73df; color: white; border-color: #4e73df; }
        .pagination .disabled { color: #aaa; cursor: not-allowed; }

        @media (max-width: 770px) {
            .main-content {
                padding: 16px !important;
                padding-bottom: 90px !important;
            }

            form[method="GET"] {
                display: grid !important;
                grid-template-columns: 1fr;
                gap: 10px;
                align-items: stretch !important;
            }
            form[method="GET"] input,
            form[method="GET"] button,
            form[method="GET"] .add-btn {
                width: 100%;
                box-sizing: border-box;
                padding: 12px;
                font-size: 16px;
                text-align: center;
                display: block;
            }

            table thead { display: none; }
            table,
            table tbody,
            table tr,
            table td {
                display: block;
                width: 100%;
            }
            table tr {
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                margin-bottom: 12px;
                overflow: hidden;
                box-shadow: 0 6px 18px rgba(0,0,0,0.06);
            }
            table td {
                border: 0;
                padding: 10px 12px;
                text-align: left;
            }
            table td::before {
                content: attr(data-label);
                display: block;
                font-size: 12px;
                font-weight: 700;
                color: #6b7280;
                text-transform: uppercase;
                letter-spacing: .03em;
                margin-bottom: 4px;
            }
            table td[data-label="Fecha"] {
                padding-top: 14px;
                padding-bottom: 14px;
                font-size: 16px;
                font-weight: 800;
            }
            table td[data-label="Acción"] {
                border-top: 1px solid #eef2f7;
                padding-top: 12px;
                padding-bottom: 14px;
            }
            table td[data-label="Acción"] a,
            table td[data-label="Acción"] button {
                display: inline-block;
                margin: 6px 6px 0 0;
                padding: 10px 12px;
                border-radius: 10px;
                font-size: 14px;
            }

            .pagination {
                flex-wrap: wrap;
                gap: 8px;
            }
        }
    </style>
    <script>
        window.onload = function() {
            const msgSuccess = document.getElementById('msg-success');
            if (msgSuccess) {
                setTimeout(function() {
                    msgSuccess.style.opacity = '0';
                    setTimeout(function() { msgSuccess.style.display = 'none'; }, 500);
                }, 3000);
            }
        };
    </script>
</head>
<body>
    <?php include("php/sidebar.php"); ?>

    <div class="main-content">
        <h1>Días No Lectivos</h1>
        <p>Calendario > Listado de Días No Lectivos</p>

        <?php if (isset($_GET['success'])): ?>
        <div id="msg-success" class="success-message">
            <?php
            $mensaje = "Operación realizada con éxito.";
            if ($_GET['success'] == '1') $mensaje = "Día no lectivo añadido correctamente.";
            if ($_GET['success'] == '2') $mensaje = "Día no lectivo eliminado correctamente.";
            echo htmlspecialchars($mensaje);
            ?>
        </div>
        <?php endif; ?>

        <form method="GET" action="" style="display: flex; gap: 10px; align-items: center;">
            <input type="text" name="search" placeholder="Buscar por descripción"
                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit">Buscar</button>
            <button type="button" onclick="window.location.href='ListadoNoLectivo.php'">Eliminar Filtro</button>

            <?php if (in_array($_SESSION['rol'], ['admin', 'editor'])): ?>
            <a href="AgregarDiaNoLectivo.php" class="add-btn">Añadir Día No Lectivo</a>
            <?php endif; ?>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Descripción</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php
                include("php/conexion.php");

                $registros_por_pagina = 12;
                $pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
                $offset = ($pagina_actual - 1) * $registros_por_pagina;

                $query = "SELECT * FROM nolectivo";
                $params = [];
                $types = "";

                if (isset($_GET['search']) && !empty($_GET['search'])) {
                    $search = "%" . $_GET['search'] . "%";
                    $query .= " WHERE descripcion LIKE ?";
                    $params[] = $search;
                    $types = "s";
                }

                $query_count = str_replace("SELECT *", "SELECT COUNT(*) as total", $query);
                $stmt_count = $conn->prepare($query_count);
                if (!empty($params)) $stmt_count->bind_param($types, ...$params);
                $stmt_count->execute();
                $row_count = $stmt_count->get_result()->fetch_assoc();
                $total_registros = $row_count['total'];
                $total_paginas = ceil($total_registros / $registros_por_pagina);

                $query .= " ORDER BY fecha DESC LIMIT ? OFFSET ?";
                $params[] = $registros_por_pagina;
                $params[] = $offset;
                $types .= "ii";

                $stmt = $conn->prepare($query);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $fecha_formateada = date('d/m/Y', strtotime($row['fecha']));
                        echo "<tr>";
                        echo "<td data-label='Fecha'>" . htmlspecialchars($fecha_formateada) . "</td>";
                        echo "<td data-label='Descripción'>" . htmlspecialchars($row['descripcion']) . "</td>";
                        echo "<td data-label='Acción'>";
                        if (in_array($_SESSION['rol'], ['admin', 'editor'])) {
                            echo "<a href='php/EliminarDiaNoLectivo.php?id=" . htmlspecialchars($row['id']) . "' ";
                            echo "class='delete-btn' ";
                            echo "onclick='return confirm(\"¿Estás seguro de que deseas eliminar este día no lectivo?\")'>Eliminar</a>";
                        } else {
                            echo "<span class='action-disabled'>Sin permisos</span>";
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

        <?php if ($total_registros > 0): ?>
        <div class="pagination">
            <?php
            $url_params = [];
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $url_params[] = "search=" . urlencode($_GET['search']);
            }
            $url_base = "ListadoNoLectivo.php?" . implode("&", $url_params);
            $url_base .= !empty($url_params) ? "&" : "";

            if ($pagina_actual > 1) {
                echo "<a href='{$url_base}pagina=1'>&laquo; Primera</a>";
                echo "<a href='{$url_base}pagina=" . ($pagina_actual - 1) . "'>&lt; Anterior</a>";
            } else {
                echo "<span class='disabled'>&laquo; Primera</span>";
                echo "<span class='disabled'>&lt; Anterior</span>";
            }

            $rango = 2;
            for ($i = max(1, $pagina_actual - $rango); $i <= min($total_paginas, $pagina_actual + $rango); $i++) {
                echo ($i == $pagina_actual)
                    ? "<span class='active'>{$i}</span>"
                    : "<a href='{$url_base}pagina={$i}'>{$i}</a>";
            }

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