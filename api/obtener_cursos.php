<?php
/**
 * API para obtener cursos disponibles con conteo real de usuarios inscritos
 */

require_once '../config/conexion.php';
require_once '../config/configuracion.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $conexion = obtenerConexion();
    
    // Consulta corregida para obtener cursos con conteo real de inscritos
    $sql = "SELECT 
                c.id_curso,
                c.nombre_curso,
                c.cupo_maximo,
                c.edad_min,
                c.edad_max,
                c.horario,
                c.sala,
                c.instructor,
                c.activo,
                COALESCE(COUNT(i.id_inscripcion), 0) as total_inscritos
            FROM cursos c
            LEFT JOIN inscripciones i ON c.id_curso = i.id_curso
            WHERE c.activo = 1
            GROUP BY c.id_curso, c.nombre_curso, c.cupo_maximo, 
                     c.edad_min, c.edad_max, c.horario, c.sala, c.instructor, 
                     c.activo
            ORDER BY c.nombre_curso";
    
    $cursos = $conexion->consultar($sql);
    
    if ($cursos === false) {
        throw new Exception('Error al consultar cursos');
    }
    
    // Procesar datos adicionales
    foreach ($cursos as &$curso) {
        $curso['total_inscritos'] = (int)$curso['total_inscritos'];
        $curso['cupo_disponible'] = $curso['cupo_maximo'] - $curso['total_inscritos'];
        $curso['esta_lleno'] = $curso['total_inscritos'] >= $curso['cupo_maximo'];
        $curso['porcentaje_ocupacion'] = $curso['cupo_maximo'] > 0 ? 
            round(($curso['total_inscritos'] / $curso['cupo_maximo']) * 100, 2) : 0;
    }
    
    registrarLog("Cursos obtenidos: " . count($cursos));
    
    echo json_encode([
        'exito' => true,
        'cursos' => $cursos,
        'total' => count($cursos)
    ]);
    
} catch (Exception $e) {
    registrarLog("Error en obtener_cursos.php: " . $e->getMessage(), 'ERROR');
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al obtener cursos: ' . $e->getMessage()
    ]);
}
?>
