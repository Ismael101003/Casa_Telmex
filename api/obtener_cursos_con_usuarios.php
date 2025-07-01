<?php
/**
 * API para obtener cursos con información de usuarios inscritos
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
    
    // Detectar estructura de tablas
    $estructuraCursos = $conexion->consultar("DESCRIBE cursos");
    $estructuraInscripciones = $conexion->consultar("DESCRIBE inscripciones");
    
    // Determinar columnas primarias
    $columnaPrimariaCursos = 'id_curso';
    $columnaPrimariaInscripciones = 'id_inscripcion';
    $campoUsuarioInscripciones = 'id_usuario';
    $campoCursoInscripciones = 'id_curso';
    
    foreach ($estructuraCursos as $col) {
        if ($col['Key'] === 'PRI') {
            $columnaPrimariaCursos = $col['Field'];
            break;
        }
    }
    
    foreach ($estructuraInscripciones as $col) {
        if ($col['Key'] === 'PRI') {
            $columnaPrimariaInscripciones = $col['Field'];
        }
        // Detectar campo de usuario
        if (in_array($col['Field'], ['usuario_id', 'id_usuario', 'user_id'])) {
            $campoUsuarioInscripciones = $col['Field'];
        }
        // Detectar campo de curso
        if (in_array($col['Field'], ['curso_id', 'id_curso', 'course_id'])) {
            $campoCursoInscripciones = $col['Field'];
        }
    }
    
    // Obtener cursos con conteo de usuarios (simplificado para evitar problemas de estructura)
    $sql = "SELECT 
                c.$columnaPrimariaCursos as id_curso,
                c.nombre_curso,
                c.edad_min,
                c.edad_max,
                COALESCE(c.cupo_maximo, 30) as cupo_maximo,
                COALESCE(c.horario, 'Por definir') as horario,
                COALESCE(c.activo, 1) as activo,
                COUNT(i.$campoUsuarioInscripciones) as total_inscritos
            FROM cursos c
            LEFT JOIN inscripciones i ON c.$columnaPrimariaCursos = i.$campoCursoInscripciones
            GROUP BY c.$columnaPrimariaCursos, c.nombre_curso, c.edad_min, c.edad_max, c.cupo_maximo, c.horario, c.activo
            ORDER BY c.nombre_curso";
    
    $cursos = $conexion->consultar($sql);
    
    // Formatear datos
    $cursosFormateados = [];
    foreach ($cursos as $curso) {
        $cursosFormateados[] = [
            'id_curso' => $curso['id_curso'],
            'nombre_curso' => $curso['nombre_curso'],
            'edad_min' => $curso['edad_min'],
            'edad_max' => $curso['edad_max'],
            'cupo_maximo' => $curso['cupo_maximo'],
            'horario' => $curso['horario'],
            'activo' => $curso['activo'],
            'total_inscritos' => (int)$curso['total_inscritos']
        ];
    }
    
    echo json_encode([
        'exito' => true,
        'cursos' => $cursosFormateados,
        'total' => count($cursosFormateados)
    ]);
    
} catch (Exception $e) {
    error_log("Error en obtener_cursos_con_usuarios: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al cargar cursos: ' . $e->getMessage(),
        'error_detalle' => $e->getMessage()
    ]);
}
?>
