<?php
/**
 * API para eliminar cursos y sus tablas específicas - VERSIÓN CON CLASE CONEXION
 */

// Incluir la clase de conexión
require_once '../config/conexion.php';

// Headers para CORS y JSON
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

try {
    // Obtener conexión usando la clase
    $conexion = obtenerConexion();
    
    $id = (int)($_POST['id'] ?? 0);
    
    error_log("=== ELIMINAR CURSO ===");
    error_log("ID del curso a eliminar: " . $id);
    
    if ($id <= 0) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'ID de curso no válido'
        ]);
        exit;
    }
    
    // Obtener información del curso incluyendo la tabla específica
    $cursos = $conexion->consultar("SELECT id_curso, nombre_curso, tabla_curso FROM cursos WHERE id_curso = ?", [$id]);
    
    if (empty($cursos)) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Curso no encontrado'
        ]);
        exit;
    }
    
    $curso = $cursos[0];
    $nombreTablaCurso = $curso['tabla_curso'] ?? '';
    
    error_log("Curso encontrado: " . $curso['nombre_curso']);
    error_log("Tabla específica: " . $nombreTablaCurso);
    
    // Iniciar transacción
    $conexion->iniciarTransaccion();
    
    try {
        // 1. Eliminar inscripciones primero (por la clave foránea)
        $conexion->ejecutar("DELETE FROM inscripciones WHERE id_curso = ?", [$id]);
        error_log("Inscripciones eliminadas");
        
        // 2. Eliminar tabla específica del curso si existe
        if (!empty($nombreTablaCurso)) {
            // Verificar que la tabla existe
            $tablas_existentes = $conexion->consultar("SHOW TABLES LIKE ?", [$nombreTablaCurso]);
            
            if (!empty($tablas_existentes)) {
                // Usar consulta directa para DROP TABLE (no se puede usar parámetros)
                $nombreTablaSeguro = preg_replace('/[^a-zA-Z0-9_]/', '', $nombreTablaCurso);
                $pdo = $conexion->obtenerConexion();
                $pdo->exec("DROP TABLE IF EXISTS `$nombreTablaSeguro`");
                error_log("Tabla específica eliminada: " . $nombreTablaSeguro);
            }
        }
        
        // 3. Eliminar curso
        $resultado = $conexion->ejecutar("DELETE FROM cursos WHERE id_curso = ?", [$id]);
        error_log("Curso eliminado: " . ($resultado ? 'SI' : 'NO'));
        
        // Confirmar transacción
        $conexion->confirmarTransaccion();
        
        echo json_encode([
            'exito' => true,
            'mensaje' => 'Curso y su tabla específica eliminados correctamente',
            'detalles' => [
                'tabla_eliminada' => !empty($nombreTablaCurso),
                'curso_eliminado' => $resultado
            ]
        ]);
        
    } catch (Exception $e) {
        $conexion->cancelarTransaccion();
        error_log("Error en transacción: " . $e->getMessage());
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("ERROR CRÍTICO al eliminar curso: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}

error_log("=== FIN ELIMINAR CURSO ===");
?>
