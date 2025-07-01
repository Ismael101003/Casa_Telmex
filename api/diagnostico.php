<?php
/**
 * API para diagnóstico del sistema
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

try {
    $conexion = obtenerConexion();
    $diagnostico = [];
    
    // Verificar conexión a la base de datos
    $diagnostico['conexion'] = [
        'estado' => 'exitoso',
        'mensaje' => 'Conexión a la base de datos establecida'
    ];
    
    // Verificar tablas existentes
    $sqlTablas = "SHOW TABLES";
    $tablas = $conexion->consultar($sqlTablas);
    $diagnostico['tablas'] = [];
    
    foreach ($tablas as $tabla) {
        $nombreTabla = array_values($tabla)[0];
        $diagnostico['tablas'][] = $nombreTabla;
    }
    
    // Verificar estructura de cada tabla importante
    $tablasImportantes = ['usuarios', 'cursos', 'inscripciones', 'admins'];
    
    foreach ($tablasImportantes as $tabla) {
        if (in_array($tabla, $diagnostico['tablas'])) {
            try {
                $sqlEstructura = "DESCRIBE $tabla";
                $estructura = $conexion->consultar($sqlEstructura);
                $diagnostico['estructuras'][$tabla] = $estructura;
                
                // Contar registros
                $sqlCount = "SELECT COUNT(*) as total FROM $tabla";
                $count = $conexion->consultar($sqlCount);
                $diagnostico['conteos'][$tabla] = $count[0]['total'];
                
            } catch (Exception $e) {
                $diagnostico['errores'][$tabla] = $e->getMessage();
            }
        } else {
            $diagnostico['tablas_faltantes'][] = $tabla;
        }
    }
    
    // Verificar datos de ejemplo
    if (in_array('usuarios', $diagnostico['tablas'])) {
        try {
            $sqlUsuarios = "SELECT * FROM usuarios LIMIT 3";
            $usuariosEjemplo = $conexion->consultar($sqlUsuarios);
            $diagnostico['ejemplos']['usuarios'] = $usuariosEjemplo;
        } catch (Exception $e) {
            $diagnostico['errores']['usuarios_ejemplo'] = $e->getMessage();
        }
    }
    
    if (in_array('cursos', $diagnostico['tablas'])) {
        try {
            $sqlCursos = "SELECT * FROM cursos LIMIT 3";
            $cursosEjemplo = $conexion->consultar($sqlCursos);
            $diagnostico['ejemplos']['cursos'] = $cursosEjemplo;
        } catch (Exception $e) {
            $diagnostico['errores']['cursos_ejemplo'] = $e->getMessage();
        }
    }
    
    echo json_encode([
        'exito' => true,
        'diagnostico' => $diagnostico
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'exito' => false,
        'error' => $e->getMessage(),
        'archivo' => $e->getFile(),
        'linea' => $e->getLine()
    ]);
}
?>
