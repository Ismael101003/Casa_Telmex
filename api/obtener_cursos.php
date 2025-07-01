<?php
/**
 * API para obtener todos los cursos - VERSIÓN CORREGIDA CON CAMPO ACTIVO
 */

// Configurar manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Headers para CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Verificar archivos de configuración
    $config_path = '../config/conexion.php';
    $configuracion_path = '../config/configuracion.php';
    
    if (!file_exists($config_path)) {
        throw new Exception('Archivo de conexión no encontrado');
    }
    
    if (!file_exists($configuracion_path)) {
        throw new Exception('Archivo de configuración no encontrado');
    }
    
    require_once $config_path;
    require_once $configuracion_path;
    
    if (!function_exists('obtenerConexion')) {
        throw new Exception('Función obtenerConexion no encontrada');
    }
    
    $conexion = obtenerConexion();
    
    if (!$conexion) {
        throw new Exception('No se pudo establecer conexión con la base de datos');
    }
    
    error_log("=== OBTENER CURSOS ===");
    
    // Verificar si existe la columna activo
    $sqlVerificarColumnas = "SHOW COLUMNS FROM cursos";
    $columnas = $conexion->consultar($sqlVerificarColumnas);
    $tieneActivo = false;
    
    if ($columnas) {
        $nombres_columnas = array_column($columnas, 'Field');
        $tieneActivo = in_array('activo', $nombres_columnas);
        error_log("Columnas en tabla cursos: " . implode(', ', $nombres_columnas));
    }
    
    error_log("Tabla cursos tiene columna activo: " . ($tieneActivo ? 'SI' : 'NO'));
    
    // Construir consulta según las columnas disponibles
    if ($tieneActivo) {
        $sql = "SELECT 
                    id_curso,
                    nombre_curso,
                    COALESCE(edad_min, 0) as edad_min,
                    COALESCE(edad_max, 100) as edad_max,
                    COALESCE(cupo_maximo, 30) as cupo_maximo,
                    COALESCE(horario, '') as horario,
                    COALESCE(activo, 1) as activo,
                    tabla_curso
                FROM cursos 
                ORDER BY nombre_curso";
    } else {
        $sql = "SELECT 
                    id_curso,
                    nombre_curso,
                    COALESCE(edad_min, 0) as edad_min,
                    COALESCE(edad_max, 100) as edad_max,
                    COALESCE(cupo_maximo, 30) as cupo_maximo,
                    COALESCE(horario, '') as horario,
                    1 as activo,
                    tabla_curso
                FROM cursos 
                ORDER BY nombre_curso";
    }
    
    error_log("SQL para obtener cursos: " . $sql);
    
    $cursos = $conexion->consultar($sql);
    
    if (!$cursos) {
        error_log("No se obtuvieron cursos de la consulta");
        $cursos = [];
    }
    
    error_log("Total cursos obtenidos: " . count($cursos));
    
    // Debug: mostrar algunos cursos
    if (count($cursos) > 0) {
        error_log("Primer curso: " . json_encode($cursos[0]));
    }
    
    echo json_encode([
        'exito' => true,
        'cursos' => $cursos,
        'total' => count($cursos)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("ERROR en obtener_cursos: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al obtener cursos: ' . $e->getMessage(),
        'cursos' => []
    ], JSON_UNESCAPED_UNICODE);
}

error_log("=== FIN OBTENER CURSOS ===");
?>
