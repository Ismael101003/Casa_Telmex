<?php
/**
 * API para obtener datos completos de un usuario para edición
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Incluir conexión centralizada
require_once '../config/conexion.php';

try {
    $conexion = obtenerConexion();
    
    $id_usuario = (int)($_GET['id'] ?? 0);
    
    if ($id_usuario <= 0) {
        throw new Exception('ID de usuario no válido');
    }
    
    $sql = "SELECT * FROM usuarios WHERE id_usuario = ? LIMIT 1";
    $resultado = $conexion->consultar($sql, [$id_usuario]);
    
    if (empty($resultado)) {
        throw new Exception('Usuario no encontrado');
    }
    
    $usuario = $resultado[0];
    
    echo json_encode([
        'exito' => true,
        'usuario' => $usuario
    ]);
    
} catch (Exception $e) {
    error_log("Error en obtener_usuario_completo.php: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al obtener usuario: ' . $e->getMessage()
    ]);
}
?>
