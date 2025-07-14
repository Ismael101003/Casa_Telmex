<?php
/**
 * API para obtener cursos con información completa incluyendo contadores actualizados
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
    
    // Obtener cursos con conteo real de usuarios inscritos
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
                
                
                COALESCE(COUNT(i.id_inscripcion), 0) as total_inscritos,
                CASE 
                    WHEN c.cupo_maximo > 0 THEN 
                        ROUND((COALESCE(COUNT(i.id_inscripcion), 0) / c.cupo_maximo) * 100, 2)
                    ELSE 0 
                END as porcentaje_ocupacion
            FROM cursos c
            LEFT JOIN inscripciones i ON c.id_curso = i.id_curso
            GROUP BY c.id_curso, c.nombre_curso , c.cupo_maximo, 
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
        $curso['activo'] = (bool)$curso['activo'];
        
        // Determinar estado de ocupación
        if ($curso['porcentaje_ocupacion'] >= 100) {
            $curso['estado_ocupacion'] = 'lleno';
        } elseif ($curso['porcentaje_ocupacion'] >= 80) {
            $curso['estado_ocupacion'] = 'casi_lleno';
        } elseif ($curso['porcentaje_ocupacion'] >= 50) {
            $curso['estado_ocupacion'] = 'medio';
        } else {
            $curso['estado_ocupacion'] = 'disponible';
        }
    }
    
    registrarLog("Cursos completos obtenidos: " . count($cursos));
    
    echo json_encode([
        'exito' => true,
        'cursos' => $cursos,
        'total' => count($cursos),
        'estadisticas' => [
            'total_cursos' => count($cursos),
            'cursos_activos' => count(array_filter($cursos, fn($c) => $c['activo'])),
            'cursos_llenos' => count(array_filter($cursos, fn($c) => $c['esta_lleno'])),
            'total_inscritos' => array_sum(array_column($cursos, 'total_inscritos')),
            'capacidad_total' => array_sum(array_column($cursos, 'cupo_maximo'))
        ]
    ]);
    
} catch (Exception $e) {
    registrarLog("Error en obtener_cursos_completos.php: " . $e->getMessage(), 'ERROR');
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al obtener cursos: ' . $e->getMessage()
    ]);
}
?>
