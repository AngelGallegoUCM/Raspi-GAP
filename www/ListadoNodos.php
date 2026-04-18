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
    <title>Listado de Nodos</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }

        .incidencias-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .incidencias-table th {
            background-color: #4e73df;
            color: white;
            padding: 10px;
            text-align: left;
        }

        .incidencias-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .incidencias-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fc;
            border-radius: 5px;
        }

        .filter-form label {
            margin-right: 5px;
        }

        .filter-form input,
        .filter-form select {
            padding: 6px 10px;
            border: 1px solid #d1d3e2;
            border-radius: 4px;
        }

        .filter-form button {
            padding: 6px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .filter-form button[type="submit"] {
            background-color: #4e73df;
            color: white;
        }

        .filter-form button[type="button"] {
            background-color: #6c757d;
            color: white;
        }

        /* Indicador de batería */
        .bateria-bar {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .bateria-bar-bg {
            width: 80px;
            height: 12px;
            background-color: #e0e0e0;
            border-radius: 6px;
            overflow: hidden;
        }

        .bateria-bar-fill {
            height: 100%;
            border-radius: 6px;
            transition: width 0.3s;
        }

        .bateria-alta   { background-color: #28a745; }
        .bateria-media  { background-color: #ffc107; }
        .bateria-baja   { background-color: #dc3545; }

        /* Paginación */
        .pagination {
            display: flex;
            justify-content: center;
            margin: 20px 0;
            gap: 5px;
        }

        .pagination a,
        .pagination span {
            display: inline-block;
            padding: 8px 12px;
            text-decoration: none;
            border: 1px solid #ddd;
            color: #4e73df;
            border-radius: 4px;
        }

        .pagination a:hover        { background-color: #f8f9fc; }
        .pagination .active        { background-color: #4e73df; color: white; border-color: #4e73df; }
        .pagination .disabled      { color: #aaa; cursor: not-allowed; }

        /* ====== MODO TARJETAS EN MÓVIL ====== */
        @media (max-width: 768px) {
            .main-content {
                padding: 16px !important;
                padding-bottom: 90px !important;
            }

            .filter-form {
                display: grid;
                grid-template-columns: 1fr;
                gap: 10px;
                align-items: stretch;
            }

            .filter-form label { margin: 0; font-weight: 700; }

            .filter-form input,
            .filter-form select,
            .filter-form button {
                width: 100%;
                box-sizing: border-box;
                padding: 12px;
                font-size: 16px;
            }

            .table-container { overflow: visible; }

            .incidencias-table thead { display: none; }

            .incidencias-table,
            .incidencias-table tbody,
            .incidencias-table tr,
            .incidencias-table td {
                display: block;
                width: 100%;
            }

            .incidencias-table tr {
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                margin-bottom: 12px;
                overflow: hidden;
                box-shadow: 0 6px 18px rgba(0,0,0,0.06);
            }

            .incidencias-table td {
                border: 0;
                padding: 10px 12px;
                text-align: left;
            }

            .incidencias-table td::before {
                content: attr(data-label);
                display: block;
                font-size: 12px;
                font-weight: 700;
                color: #6b7280;
                text-transform: uppercase;
                letter-spacing: .03em;
                margin-bottom: 4px;
            }

            .incidencias-table td[data-label="Aula"] {
                padding-top: 14px;
                padding-bottom: 14px;
                font-size: 16px;
                font-weight: 800;
            }

            .pagination { flex-wrap: wrap; gap: 8px; }
        }
    </style>
</head>
<body>
    <?php include("php/sidebar.php"); ?>

    <div class="main-content">
        <h1>Listado de Nodos</h1>
        <p>Informe > Nodos</p>

        <!-- Filtros -->
        <form id="filter-form" method="GET" action="" class="filter-form">
            <label for="aula">Aula:</label>
            <input type="text" id="aula" name="aula" maxlength="4" placeholder="Ej: 0101"
                   value="<?php echo isset($_GET['aula']) ? htmlspecialchars($_GET['aula']) : ''; ?>">

            <label for="bateria_min">Batería mínima (%):</label>
            <input type="number" id="bateria_min" name="bateria_min" min="0" max="100" placeholder="0"
                   value="<?php echo isset($_GET['bateria_min']) ? htmlspecialchars($_GET['bateria_min']) : ''; ?>">

            <label for="bateria_max">Batería máxima (%):</label>
            <input type="number" id="bateria_max" name="bateria_max" min="0" max="100" placeholder="100"
                   value="<?php echo isset($_GET['bateria_max']) ? htmlspecialchars($_GET['bateria_max']) : ''; ?>">

            <button type="submit">Filtrar</button>
            <button type="button" onclick="window.location.href='ListadoNodos.php'">Restablecer Filtro</button>
        </form>

        <!-- Tabla de Nodos -->
        <div class="table-container">
            <table class="incidencias-table">
                <thead>
                    <tr>
                        <th>Aula</th>
                        <th>Capacidad</th>
                        <th>Última Conexión</th>
                        <th>Batería</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    include("php/conexion.php");

                    // Configuración de paginación
                    $registros_por_pagina = 12;
                    $pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
                    $offset = ($pagina_actual - 1) * $registros_por_pagina;

                    // Preparar condiciones de filtro
                    $conditions = [];
                    $params     = [];
                    $types      = "";

                    if (!empty($_GET['aula'])) {
                        $conditions[] = "a.numero_aula LIKE ?";
                        $params[]     = "%" . $_GET['aula'] . "%";
                        $types       .= "s";
                    }

                    if (isset($_GET['bateria_min']) && $_GET['bateria_min'] !== '') {
                        $conditions[] = "n.bateria >= ?";
                        $params[]     = intval($_GET['bateria_min']);
                        $types       .= "i";
                    }

                    if (isset($_GET['bateria_max']) && $_GET['bateria_max'] !== '') {
                        $conditions[] = "n.bateria <= ?";
                        $params[]     = intval($_GET['bateria_max']);
                        $types       .= "i";
                    }

                    $where = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

                    // Consulta total para paginación
                    $query_count = "
                        SELECT COUNT(*) AS total
                        FROM nodos n
                        JOIN aulas a ON n.aula_id = a.id
                        $where";

                    $stmt_count = $conn->prepare($query_count);
                    if (!empty($params)) {
                        $stmt_count->bind_param($types, ...$params);
                    }
                    $stmt_count->execute();
                    $result_count    = $stmt_count->get_result();
                    $row_count       = $result_count->fetch_assoc();
                    $total_registros = $row_count['total'];
                    $total_paginas   = ceil($total_registros / $registros_por_pagina);

                    // Consulta principal
                    $query = "
                        SELECT n.id,
                               a.numero_aula,
                               a.capacidad,
                               DATE_FORMAT(n.ultima_conexion, '%d/%m/%Y %H:%i') AS ultima_conexion_fmt,
                               n.bateria
                        FROM nodos n
                        JOIN aulas a ON n.aula_id = a.id
                        $where
                        ORDER BY n.ultima_conexion DESC
                        LIMIT ? OFFSET ?";

                    $params[] = $registros_por_pagina;
                    $params[] = $offset;
                    $types   .= "ii";

                    $stmt = $conn->prepare($query);
                    if (!empty($params)) {
                        $stmt->bind_param($types, ...$params);
                    }
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $bat = (int)$row['bateria'];

                            if ($bat >= 60) {
                                $bat_clase = 'bateria-alta';
                            } elseif ($bat >= 25) {
                                $bat_clase = 'bateria-media';
                            } else {
                                $bat_clase = 'bateria-baja';
                            }

                            echo "<tr>";
                            echo "<td data-label='Aula'>"            . htmlspecialchars($row['numero_aula'])          . "</td>";
                            echo "<td data-label='Capacidad'>"       . htmlspecialchars($row['capacidad']) . " alumnos</td>";
                            echo "<td data-label='Última Conexión'>" . htmlspecialchars($row['ultima_conexion_fmt'])  . "</td>";
                            echo "<td data-label='Batería'>
                                    <div class='bateria-bar'>
                                        <div class='bateria-bar-bg'>
                                            <div class='bateria-bar-fill {$bat_clase}' style='width:{$bat}%'></div>
                                        </div>
                                        <span>{$bat}%</span>
                                    </div>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4'>No se encontraron nodos que coincidan con los criterios de búsqueda.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($total_registros > 0): ?>
        <div class="pagination">
            <?php
            $url_params = [];
            if (!empty($_GET['aula']))        $url_params[] = "aula="        . urlencode($_GET['aula']);
            if (isset($_GET['bateria_min']) && $_GET['bateria_min'] !== '') $url_params[] = "bateria_min=" . urlencode($_GET['bateria_min']);
            if (isset($_GET['bateria_max']) && $_GET['bateria_max'] !== '') $url_params[] = "bateria_max=" . urlencode($_GET['bateria_max']);

            $url_base  = "ListadoNodos.php?" . implode("&", $url_params);
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
                if ($i == $pagina_actual) {
                    echo "<span class='active'>{$i}</span>";
                } else {
                    echo "<a href='{$url_base}pagina={$i}'>{$i}</a>";
                }
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

    <script>
        window.onload = function () {
            document.getElementById('filter-form').addEventListener('submit', function (e) {
                const batMin = parseInt(document.getElementById('bateria_min').value);
                const batMax = parseInt(document.getElementById('bateria_max').value);

                if (!isNaN(batMin) && !isNaN(batMax) && batMin > batMax) {
                    alert('La batería mínima no puede ser mayor que la máxima.');
                    e.preventDefault();
                    return false;
                }
                return true;
            });
        };
    </script>
</body>
</html>