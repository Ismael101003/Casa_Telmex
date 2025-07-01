<?php
/**
 * API para obtener todas las inscripciones
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
    $estructuraInscripciones = $conexion->consultar("DESCRIBE inscripciones");
    $estructuraUsuarios = $conexion->consultar("DESCRIBE usuarios");
    $estructuraCursos = $conexion->consultar("DESCRIBE cursos");
    
    // Determinar columnas primarias y campos
    $columnaPrimariaInscripciones = 'id_inscripcion';
    $columnaPrimariaUsuarios = 'id_usuario';
    $columnaPrimariaCursos = 'id_curso';
    $campoUsuarioInscripciones = 'id_usuario';
    $campoCursoInscripciones = 'id_curso';
    $campoFechaInscripcion = null; // No asumimos que existe
    
    // Detectar columnas en inscripciones
    foreach ($estructuraInscripciones as $col) {
        if ($col['Key'] === 'PRI') {
            $columnaPrimariaInscripciones = $col['Field'];
        }
        if (in_array($col['Field'], ['usuario_id', 'id_usuario', 'user_id'])) {
            $campoUsuarioInscripciones = $col['Field'];
        }
        if (in_array($col['Field'], ['curso_id', 'id_curso', 'course_id'])) {
            $campoCursoInscripciones = $col['Field'];
        }
        // Verificar si existe algún campo de fecha en inscripciones
        if (in_array($col['Field'], ['fecha_inscripcion', 'created_at', 'fecha_registro', 'fecha_creacion'])) {
            $campoFechaInscripcion = $col['Field'];
        }
    }
    
    foreach ($estructuraUsuarios as $col) {
        if ($col['Key'] === 'PRI') {
            $columnaPrimariaUsuarios = $col['Field'];
            break;
        }
    }
    
    foreach ($estructuraCursos as $col) {
        if ($col['Key'] === 'PRI') {
            $columnaPrimariaCursos = $col['Field'];
            break;
        }
    }
    
    // Construir consulta según la estructura disponible
    $camposFecha = '';
    if ($campoFechaInscripcion) {
        $camposFecha = "i.$campoFechaInscripcion as fecha_inscripcion,";
    } else {
        // Usar fecha_registro de usuarios como alternativa
        $camposFecha = "u.fecha_registro as fecha_inscripcion,";
    }
    
    // Consulta para obtener inscripciones con información de usuario y curso
    $sql = "SELECT 
                i.$columnaPrimariaInscripciones as id_inscripcion,
                i.$campoUsuarioInscripciones as id_usuario,
                i.$campoCursoInscripciones as id_curso,
                $camposFecha
                CONCAT(COALESCE(u.nombre, 'Usuario'), ' ', COALESCE(u.apellidos, 'Eliminado')) as nombre_usuario,
                COALESCE(c.nombre_curso, 'Curso Eliminado') as nombre_curso,
                u.curp,
                u.edad
            FROM inscripciones i
            LEFT JOIN usuarios u ON i.$campoUsuarioInscripciones = u.$columnaPrimariaUsuarios
            LEFT JOIN cursos c ON i.$campoCursoInscripciones = c.$columnaPrimariaCursos
            ORDER BY " . ($campoFechaInscripcion ? "i.$campoFechaInscripcion" : "u.fecha_registro") . " DESC";
    
    $inscripciones = $conexion->consultar($sql);
    
    // Formatear datos
    $inscripcionesFormateadas = [];
    foreach ($inscripciones as $inscripcion) {
        $fechaFormateada = '';
        if (!empty($inscripcion['fecha_inscripcion'])) {
            try {
                $fecha = new DateTime($inscripcion['fecha_inscripcion']);
                $fechaFormateada = $fecha->format('d/m/Y H:i');
            } catch (Exception $e) {
                $fechaFormateada = 'Fecha inválida';
            }
        }
        
        $inscripcionesFormateadas[] = [
            'id_inscripcion' => $inscripcion['id_inscripcion'],
            'id_usuario' => $inscripcion['id_usuario'],
            'id_curso' => $inscripcion['id_curso'],
            'nombre_usuario' => $inscripcion['nombre_usuario'],
            'nombre_curso' => $inscripcion['nombre_curso'],
            'curp' => $inscripcion['curp'] ?? 'N/A',
            'edad' => $inscripcion['edad'] ?? 'N/A',
            'fecha_inscripcion' => $inscripcion['fecha_inscripcion'],
            'fecha_inscripcion_formateada' => $fechaFormateada
        ];
    }
    
    echo json_encode([
        'exito' => true,
        'inscripciones' => $inscripcionesFormateadas,
        'total' => count($inscripcionesFormateadas),
        'estructura_detectada' => [
            'campo_fecha_inscripciones' => $campoFechaInscripcion,
            'usando_fecha_usuarios' => !$campoFechaInscripcion
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en obtener_inscripciones: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al cargar inscripciones: ' . $e->getMessage(),
        'inscripciones' => []
    ]);
}
?>
