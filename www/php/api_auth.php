<?php
function verificarSesionApi() {
    session_start();
    if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'profesor') {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        exit();
    }
}
?>