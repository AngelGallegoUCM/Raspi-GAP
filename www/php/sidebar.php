<?php
// Se asume que la sesión ya está iniciada en la página que incluye este sidebar
?>
<style>
/* --- ESTILOS BASE DEL SIDEBAR --- */
.sidebar {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    background-color: #003366;
    color: white;
    width: 250px;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    position: sticky;
    top: 0;
    align-self: flex-start;
}

.logo {
    text-align: center;
    padding: 20px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.logo img {
    width: 120px;
    height: auto;
}

.sidebar-nav {
    flex: 1;
    padding: 20px 0;
    overflow-y: visible;
}

.sidebar-nav ul {
    list-style-type: none;
    padding: 0;
    margin: 0;
}

.sidebar-nav a {
    display: block;
    padding: 12px 20px;
    color: #e6e6e6;
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
    font-size: 15px;
}

.sidebar-nav a:hover,
.sidebar-nav a.current-page {
    background-color: rgba(255, 255, 255, 0.1);
    color: white;
    border-left-color: #4e73df;
}

.user-info {
    margin-top: auto;
    padding: 15px;
    background-color: rgba(0, 0, 0, 0.2);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.user-role {
    display: inline-block;
    background-color: #4e73df;
    color: white;
    font-size: 12px;
    font-weight: bold;
    padding: 2px 8px;
    border-radius: 10px;
    text-transform: capitalize;
}

.logout-btn {
    display: block;
    width: 100%;
    margin-top: 10px;
    padding: 8px;
    background-color: rgba(255, 255, 255, 0.15);
    color: white;
    text-decoration: none;
    border-radius: 4px;
    text-align: center;
    font-size: 13px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    transition: background-color 0.3s ease;
}

.logout-btn:hover {
    background-color: rgba(255, 255, 255, 0.25);
}

.menu-section-title {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: rgba(255, 255, 255, 0.5);
    padding: 10px 20px;
    margin-top: 15px;
}

.mobile-toggle {
    display: none;
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 4000;
    background-color: #003366;
    color: white;
    border: none;
    padding: 15px 20px;
    font-size: 24px;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}

.sidebar-overlay {
    display: none;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
}

.container {
    display: flex;
    min-height: 100vh;
}

.main-content {
    flex: 1;
}

@media (max-width: 770px) {
    .mobile-toggle { display: block; }

    .container {
        display: block;
        min-height: auto;
    }

    .sidebar {
        position: fixed !important;
        top: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        left: auto !important;
        height: 100dvh !important;
        min-height: 100dvh !important;
        width: 250px;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        z-index: 3000 !important;
        margin: 0 !important;
        box-shadow: none;
        overflow: hidden;
    }

    .sidebar.active { transform: translateX(0); }

    .sidebar-overlay {
        display: block;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        z-index: 2500;
        background: rgba(0,0,0,0);
    }

    .sidebar-overlay.active {
        opacity: 1;
        pointer-events: auto;
    }

    .sidebar-nav {
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
}
</style>

<button class="mobile-toggle" id="mobileToggle">☰</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
    <div class="logo">
        <a href="index.php">
            <img src="img/logo.png" alt="Logo Universidad Complutense">
        </a>
    </div>

    <nav class="sidebar-nav">

        <?php
        $rol = $_SESSION['rol'] ?? '';
        $pagina_actual = basename($_SERVER['PHP_SELF']);

        // Helper para generar enlaces activos
        function nav_link($href, $label, $pagina_actual) {
            $clase = ($pagina_actual === $href) ? 'current-page' : '';
            echo "<li><a href=\"{$href}\" class=\"{$clase}\">{$label}</a></li>";
        }
        ?>

        <?php if (in_array($rol, ['admin', 'editor'])): ?>
        <div class="menu-section-title">Gestión Académica</div>
        <ul>
            <?php nav_link('ListadoProfesores.php',  'Listar Profesores',  $pagina_actual); ?>
            <?php nav_link('ListadoAulas.php',       'Listar Aulas',       $pagina_actual); ?>
            <?php nav_link('ListadoAsignaturas.php', 'Listar Asignaturas', $pagina_actual); ?>
        </ul>
        <?php endif; ?>

        <?php if ($rol === 'profesor' && !empty($_SESSION['IdProfesor'])): ?>
        <div class="menu-section-title">Mi Espacio</div>
        <ul>
            <?php
            include_once("php/conexion.php");
            $stmt = $conn->prepare("SELECT id FROM profesores WHERE identificador = ?");
            $stmt->bind_param("s", $_SESSION['IdProfesor']);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()):
                $idReal = $row['id'];
                nav_link("VerDatosProfesor.php?id={$idReal}", 'Mi Horario', $pagina_actual);
            endif;
            $stmt->close();
            ?>
        </ul>
        <?php endif; ?>

        <div class="menu-section-title">Control de Asistencia</div>
        <ul>
            <?php nav_link('ListadoNoLectivo.php', 'Días no lectivos', $pagina_actual); ?>

            <?php if (in_array($rol, ['admin', 'editor', 'profesor'])): ?>
                <?php nav_link('ListadoIncidencias.php', 'Listar Incidencias', $pagina_actual); ?>
            <?php endif; ?>

            <?php if ($rol === 'profesor'): ?>
                <?php nav_link('Prejustificacion.php', 'Prejustificar Incidencia', $pagina_actual); ?>
            <?php endif; ?>

            <?php if ($rol === 'admin'): ?>
                <?php nav_link('ListadoNodos.php',   'Nodos',        $pagina_actual); ?>
                <?php nav_link('VerEstadisticas.php', 'Estadísticas', $pagina_actual); ?>
                <?php nav_link('GestionUsuarios.php', 'Configuración', $pagina_actual); ?>
            <?php endif; ?>
        </ul>

    </nav>

    <div class="user-info">
        <?php if (isset($_SESSION['username'])): ?>
            <p>¡Hola, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
            <p>Rol: <span class="user-role"><?php echo htmlspecialchars($_SESSION['rol']); ?></span></p>
            <a href="logout.php" class="logout-btn">Cerrar sesión</a>
        <?php endif; ?>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('mobileToggle');
    const sidebar   = document.getElementById('sidebar');
    const overlay   = document.getElementById('sidebarOverlay');

    function toggleMenu() {
        const open = sidebar.classList.toggle('active');
        overlay.classList.toggle('active', open);
        toggleBtn.innerHTML = open ? '✕' : '☰';
    }

    toggleBtn.addEventListener('click', toggleMenu);
    overlay.addEventListener('click', toggleMenu);
});
</script>