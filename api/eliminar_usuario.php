<?php
/**
 * API para eliminar usuarios
 */

require_once '../config/conexion.php';
require_once '../config/configuracion.php';

// Headers para CORS y JSON
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $conexion = obtenerConexion();
    
    $id_usuario = (int)($_POST['id'] ?? 0);
    
    if ($id_usuario <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de usuario no válido'
        ]);
        exit;
    }
    
    // Verificar la estructura de la tabla usuarios
    $sqlVerificarColumnas = "SHOW COLUMNS FROM usuarios";
    $columnas = $conexion->consultar($sqlVerificarColumnas);
    
    // Verificar si existe la columna id o id_usuario
    $tieneId = false;
    $tieneIdUsuario = false;
    $columnaPrimaria = "id";
    
    foreach ($columnas as $columna) {
        if ($columna['Field'] === 'id') {
            $tieneId = true;
        }
        if ($columna['Field'] === 'id_usuario') {
            $tieneIdUsuario = true;
            $columnaPrimaria = "id_usuario";
        }
    }
    
    // Verificar que el usuario existe
    $sqlVerificar = "SELECT COUNT(*) as total FROM usuarios WHERE $columnaPrimaria = ?";
    $resultado = $conexion->consultar($sqlVerificar, [$id_usuario]);
    
    if ($resultado[0]['total'] == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Usuario no encontrado'
        ]);
        exit;
    }
    
    // Iniciar transacción
    $conexion->iniciarTransaccion();
    
    try {
        // Eliminar inscripciones primero (por la clave foránea)
        $sqlEliminarInscripciones = "DELETE FROM inscripciones WHERE id_usuario = ?";
        $conexion->ejecutar($sqlEliminarInscripciones, [$id_usuario]);
        
        // Eliminar usuario
        $sqlEliminarUsuario = "DELETE FROM usuarios WHERE $columnaPrimaria = ?";
        $resultadoEliminacion = $conexion->ejecutar($sqlEliminarUsuario, [$id_usuario]);
        
        // Confirmar transacción
        $conexion->confirmarTransaccion();
        
        echo json_encode([
            'success' => true,
            'message' => 'Usuario eliminado correctamente'
        ]);
        
    } catch (Exception $e) {
        // Cancelar transacción en caso de error
        $conexion->cancelarTransaccion();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error al eliminar usuario: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al eliminar usuario: ' . $e->getMessage()
    ]);
}
?>
