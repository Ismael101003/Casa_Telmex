<?php
/**
 * API para eliminar administradores
 */

require_once '../config/conexion.php';
require_once '../config/configuracion.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

try {
    $conexion = obtenerConexion();
    
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'ID de administrador no válido'
        ]);
        exit;
    }
    
    // Verificar que el administrador existe
    $sqlVerificar = "SELECT COUNT(*) as total FROM admins WHERE id_admin = ?";
    $resultado = $conexion->consultar($sqlVerificar, [$id]);
    
    if ($resultado[0]['total'] == 0) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Administrador no encontrado'
        ]);
        exit;
    }
    
    // No permitir eliminar el último administrador
    $sqlContarAdmins = "SELECT COUNT(*) as total FROM admins WHERE activo = 1";
    $resultadoContar = $conexion->consultar($sqlContarAdmins);
    
    if ($resultadoContar[0]['total'] <= 1) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'No se puede eliminar el último administrador del sistema'
        ]);
        exit;
    }
    
    // Eliminar administrador
    $sql = "DELETE FROM admins WHERE id_admin = ?";
    $conexion->ejecutar($sql, [$id]);
    
    echo json_encode([
        'exito' => true,
        'mensaje' => 'Administrador eliminado correctamente'
    ]);
    
} catch (Exception $e) {
    error_log("Error al eliminar administrador: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error interno del servidor'
    ]);
}
?>
