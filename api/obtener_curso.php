<?php
/**
 * API para obtener un curso específico - AHORA INCLUYE SALA E INSTRUCTOR
 */

require_once '../config/conexion.php';
require_once '../config/configuracion.php';

// Headers para CORS y JSON
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

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
            'mensaje' => 'ID de curso no válido'
        ]);
        exit;
    }
    
    $sql = "SELECT 
                id_curso,
                nombre_curso,
                COALESCE(edad_min, 0) as edad_min,
                COALESCE(edad_max, 100) as edad_max,
                COALESCE(cupo_maximo, 30) as cupo_maximo,
                COALESCE(horario, '') as horario,
                COALESCE(sala, '') as sala,
                COALESCE(instructor, '') as instructor,
                COALESCE(activo, 1) as activo,
                tabla_curso
            FROM cursos 
            WHERE id_curso = ?";
    
    $resultado = $conexion->consultar($sql, [$id]);
    
    if (empty($resultado)) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Curso no encontrado'
        ]);
        exit;
    }
    
    echo json_encode([
        'exito' => true,
        'curso' => $resultado[0]
    ]);
    
} catch (Exception $e) {
    error_log("Error al obtener curso: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>
