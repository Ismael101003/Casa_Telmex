<?php
/**
 * Versión de debug para identificar el error 500
 */

// Configuración básica
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Limpiar output
if (ob_get_level()) {
    ob_end_clean();
}

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

echo "DEBUG: Iniciando script\n";

try {
    echo "DEBUG: Verificando método\n";
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        echo json_encode(['debug' => 'OPTIONS request']);
        exit(0);
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['error' => 'Método no permitido']);
        exit;
    }
    
    echo "DEBUG: Método POST confirmado\n";
    echo "DEBUG: POST data: " . json_encode($_POST) . "\n";
    
    // Verificar archivos
    echo "DEBUG: Verificando archivos\n";
    
    $config_path = '../config/conexion.php';
    $configuracion_path = '../config/configuracion.php';
    
    if (!file_exists($config_path)) {
        echo json_encode(['error' => 'conexion.php no encontrado']);
        exit;
    }
    
    if (!file_exists($configuracion_path)) {
        echo json_encode(['error' => 'configuracion.php no encontrado']);
        exit;
    }
    
    echo "DEBUG: Archivos encontrados\n";
    
    // Incluir archivos
    echo "DEBUG: Incluyendo conexion.php\n";
    require_once $config_path;
    
    echo "DEBUG: Incluyendo configuracion.php\n";
    require_once $configuracion_path;
    
    echo "DEBUG: Archivos incluidos\n";
    
    // Verificar función
    if (!function_exists('obtenerConexion')) {
        echo json_encode(['error' => 'Función obtenerConexion no existe']);
        exit;
    }
    
    echo "DEBUG: Función obtenerConexion existe\n";
    
    // Obtener conexión
    echo "DEBUG: Obteniendo conexión\n";
    $conexion = obtenerConexion();
    
    if (!$conexion) {
        echo json_encode(['error' => 'No se pudo conectar a BD']);
        exit;
    }
    
    echo "DEBUG: Conexión obtenida\n";
    
    // Obtener datos
    $id_curso = isset($_POST['id_curso']) ? (int)$_POST['id_curso'] : 0;
    $nombre_curso = isset($_POST['nombre_curso']) ? trim($_POST['nombre_curso']) : '';
    $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;
    
    echo "DEBUG: Datos obtenidos - ID: $id_curso, Nombre: $nombre_curso, Activo: $activo\n";
    
    // Verificar tabla
    echo "DEBUG: Verificando tabla cursos\n";
    $columnas = $conexion->consultar("SHOW COLUMNS FROM cursos");
    echo "DEBUG: Columnas obtenidas: " . json_encode($columnas) . "\n";
    
    // Respuesta exitosa
    echo json_encode([
        'exito' => true,
        'mensaje' => 'Debug completado',
        'datos' => $_POST,
        'id_curso' => $id_curso,
        'columnas' => count($columnas)
    ]);
    
} catch (Exception $e) {
    echo "DEBUG: Excepción capturada: " . $e->getMessage() . "\n";
    echo "DEBUG: Línea: " . $e->getLine() . "\n";
    echo "DEBUG: Archivo: " . $e->getFile() . "\n";
    
    echo json_encode([
        'error' => 'Excepción',
        'mensaje' => $e->getMessage(),
        'linea' => $e->getLine(),
        'archivo' => basename($e->getFile())
    ]);
} catch (Error $e) {
    echo "DEBUG: Error fatal: " . $e->getMessage() . "\n";
    echo "DEBUG: Línea: " . $e->getLine() . "\n";
    
    echo json_encode([
        'error' => 'Error fatal',
        'mensaje' => $e->getMessage(),
        'linea' => $e->getLine()
    ]);
}

echo "\nDEBUG: Fin del script\n";
?>
