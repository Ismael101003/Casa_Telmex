<?php
/**
 * API para obtener un administrador específico
 */

require_once '../config/conexion.php';
require_once '../config/configuracion.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

try {
    $conexion = obtenerConexion();
    
    $id = (int)($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'ID de administrador no válido'
        ]);
        exit;
    }
    
    $sql = "SELECT id_admin, usuario, nombre, email, activo FROM admins WHERE id_admin = ?";
    $resultado = $conexion->consultar($sql, [$id]);
    
    if (empty($resultado)) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Administrador no encontrado'
        ]);
        exit;
    }
    
    echo json_encode([
        'exito' => true,
        'admin' => $resultado[0]
    ]);
    
} catch (Exception $e) {
    error_log("Error al obtener administrador: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error interno del servidor'
    ]);
}
?>
