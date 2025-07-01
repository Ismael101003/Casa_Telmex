<?php
/**
 * API para verificar sesión de administrador
 */

session_start();

header('Content-Type: application/json; charset=utf-8');

if (isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id'])) {
    echo json_encode([
        'exito' => true,
        'admin' => [
            'id' => $_SESSION['admin_id'],
            'usuario' => $_SESSION['admin_usuario'] ?? 'Admin',
            'nombre' => $_SESSION['admin_nombre'] ?? 'Administrador'
        ]
    ]);
} else {
    echo json_encode([
        'exito' => false,
        'mensaje' => 'No hay sesión activa'
    ]);
}
?>
