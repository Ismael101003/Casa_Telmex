<?php
/**
 * API para obtener estadísticas del sistema
 */

require_once '../config/conexion.php';
require_once '../config/configuracion.php';

// Headers para CORS y JSON
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $conexion = obtenerConexion();
    $estadisticas = [];
    
    // Contar usuarios
    try {
        $sqlUsuarios = "SELECT COUNT(*) as total FROM usuarios WHERE activo = 1";
        $resultUsuarios = $conexion->consultar($sqlUsuarios);
        $estadisticas['total_usuarios'] = $resultUsuarios[0]['total'] ?? 0;
    } catch (Exception $e) {
        // Si falla, intentar sin la columna activo
        try {
            $sqlUsuarios = "SELECT COUNT(*) as total FROM usuarios";
            $resultUsuarios = $conexion->consultar($sqlUsuarios);
            $estadisticas['total_usuarios'] = $resultUsuarios[0]['total'] ?? 0;
        } catch (Exception $e2) {
            $estadisticas['total_usuarios'] = 0;
        }
    }
    
    // Contar cursos
    try {
        $sqlCursos = "SELECT COUNT(*) as total FROM cursos WHERE activo = 1";
        $resultCursos = $conexion->consultar($sqlCursos);
        $estadisticas['total_cursos'] = $resultCursos[0]['total'] ?? 0;
    } catch (Exception $e) {
        // Si falla, intentar sin la columna activo
        try {
            $sqlCursos = "SELECT COUNT(*) as total FROM cursos";
            $resultCursos = $conexion->consultar($sqlCursos);
            $estadisticas['total_cursos'] = $resultCursos[0]['total'] ?? 0;
        } catch (Exception $e2) {
            $estadisticas['total_cursos'] = 0;
        }
    }
    
    // Contar inscripciones
    try {
        $sqlInscripciones = "SELECT COUNT(*) as total FROM inscripciones";
        $resultInscripciones = $conexion->consultar($sqlInscripciones);
        $estadisticas['total_inscripciones'] = $resultInscripciones[0]['total'] ?? 0;
    } catch (Exception $e) {
        $estadisticas['total_inscripciones'] = 0;
    }
    
    // Contar administradores
    try {
        $sqlAdmins = "SELECT COUNT(*) as total FROM admins WHERE activo = 1";
        $resultAdmins = $conexion->consultar($sqlAdmins);
        $estadisticas['total_admins'] = $resultAdmins[0]['total'] ?? 0;
    } catch (Exception $e) {
        // Si falla, intentar sin la columna activo
        try {
            $sqlAdmins = "SELECT COUNT(*) as total FROM admins";
            $resultAdmins = $conexion->consultar($sqlAdmins);
            $estadisticas['total_admins'] = $resultAdmins[0]['total'] ?? 0;
        } catch (Exception $e2) {
            $estadisticas['total_admins'] = 0;
        }
    }
    
    // Estadísticas adicionales
    try {
        // Curso con más inscripciones
        $sqlCursoPopular = "SELECT c.nombre_curso as total_inscripciones
                           FROM cursos c
                           LEFT JOIN inscripciones i ON c.id_curso = i.id_curso
                           GROUP BY c.id_curso, c.nombre_curso
                           ORDER BY total_inscripciones DESC
                           LIMIT 1";
        $resultCursoPopular = $conexion->consultar($sqlCursoPopular);
        $estadisticas['curso_popular'] = $resultCursoPopular[0] ?? null;
    } catch (Exception $e) {
        $estadisticas['curso_popular'] = null;
    }
    
    echo json_encode([
        'exito' => true,
        'estadisticas' => $estadisticas
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Error al obtener estadísticas: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al obtener estadísticas: ' . $e->getMessage(),
        'estadisticas' => [
            'total_usuarios' => 0,
            'total_cursos' => 0,
            'total_inscripciones' => 0,
            'total_admins' => 0
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>
