<?php
/**
 * API para obtener todos los catálogos (tipos de seguros, salas, instructores)
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
    
    // Obtener tipos de seguros
    $tipos_seguros = $conexion->consultar(
        "SELECT id_seguro, nombre_seguro FROM tipos_seguros WHERE activo = 1 ORDER BY nombre_seguro"
    );
    
    // Obtener salas
    $salas = $conexion->consultar(
        "SELECT id_sala, nombre_sala FROM salas WHERE activo = 1 ORDER BY nombre_sala"
    );
    
    // Obtener instructores
    $instructores = $conexion->consultar(
        "SELECT id_instructor, nombre_instructor FROM instructores WHERE activo = 1 ORDER BY nombre_instructor"
    );
    
    echo json_encode([
        'exito' => true,
        'catalogos' => [
            'tipos_seguros' => $tipos_seguros,
            'salas' => $salas,
            'instructores' => $instructores
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en obtener_catalogos.php: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al obtener catálogos: ' . $e->getMessage()
    ]);
}
?>
