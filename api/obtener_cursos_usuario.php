<?php
/**
 * API para obtener los cursos de un usuario específico
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
    $idUsuario = $_GET['id'] ?? null;
    
    if (!$idUsuario) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'ID de usuario requerido'
        ]);
        exit;
    }
    
    // Detectar estructura de tablas
    $estructuraUsuarios = $conexion->consultar("DESCRIBE usuarios");
    $estructuraCursos = $conexion->consultar("DESCRIBE cursos");
    $estructuraInscripciones = $conexion->consultar("DESCRIBE inscripciones");
    
    // Determinar columnas primarias y campos
    $columnaPrimariaUsuarios = 'id_usuario';
    $columnaPrimariaCursos = 'id_curso';
    $campoUsuarioInscripciones = 'id_usuario';
    $campoCursoInscripciones = 'id_curso';
    $campoFechaInscripcion = null; // Inicializar como null
    
    // Detectar columna primaria de usuarios
    foreach ($estructuraUsuarios as $col) {
        if ($col['Key'] === 'PRI') {
            $columnaPrimariaUsuarios = $col['Field'];
            break;
        }
    }
    
    // Detectar columna primaria de cursos
    foreach ($estructuraCursos as $col) {
        if ($col['Key'] === 'PRI') {
            $columnaPrimariaCursos = $col['Field'];
            break;
        }
    }
    
    // Detectar estructura de inscripciones
    foreach ($estructuraInscripciones as $col) {
        if (in_array($col['Field'], ['usuario_id', 'id_usuario', 'user_id'])) {
            $campoUsuarioInscripciones = $col['Field'];
        }
        if (in_array($col['Field'], ['curso_id', 'id_curso', 'course_id'])) {
            $campoCursoInscripciones = $col['Field'];
        }
        // Buscar campo de fecha en inscripciones
        if (in_array($col['Field'], ['fecha_inscripcion', 'created_at', 'fecha_registro', 'fecha_creacion', 'timestamp'])) {
            $campoFechaInscripcion = $col['Field'];
        }
    }
    
    // Obtener información del usuario
    $sqlUsuario = "SELECT 
                       $columnaPrimariaUsuarios as id_usuario,
                       nombre,
                       apellidos,
                       curp,
                       fecha_nacimiento,
                       edad,
                       COALESCE(meses, 0) as meses,
                       COALESCE(salud, '') as salud,
                       tutor,
                       COALESCE(numero_tutor, 'N/A') as numero_tutor,
                       COALESCE(numero_usuario, 'N/A') as numero_usuario,
                       fecha_registro
                   FROM usuarios 
                   WHERE $columnaPrimariaUsuarios = ?";
    
    $resultadoUsuario = $conexion->consultar($sqlUsuario, [$idUsuario]);
    
    if (empty($resultadoUsuario)) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Usuario no encontrado'
        ]);
        exit;
    }
    
    $usuario = $resultadoUsuario[0];
    
    // Formatear fecha de registro del usuario
    if (!empty($usuario['fecha_registro'])) {
        try {
            $fecha = new DateTime($usuario['fecha_registro']);
            $usuario['fecha_registro'] = $fecha->format('d/m/Y H:i');
        } catch (Exception $e) {
            // Mantener formato original si hay error
        }
    }
    
    // Construir consulta para obtener cursos según la estructura disponible
    $campoFechaSelect = '';
    $campoFechaOrderBy = '';
    
    if ($campoFechaInscripcion) {
        // Si existe campo de fecha en inscripciones, usarlo
        $campoFechaSelect = "i.$campoFechaInscripcion as fecha_inscripcion,";
        $campoFechaOrderBy = "i.$campoFechaInscripcion DESC";
    } else {
        // Si no existe, usar fecha_registro de usuarios como alternativa
        $campoFechaSelect = "u.fecha_registro as fecha_inscripcion,";
        $campoFechaOrderBy = "u.fecha_registro DESC";
    }
    
    // Obtener cursos del usuario
    $sqlCursos = "SELECT 
                      c.$columnaPrimariaCursos as id_curso,
                      c.nombre_curso,
                      c.edad_min,
                      c.edad_max,
                      COALESCE(c.cupo_maximo, 30) as cupo_maximo,
                      COALESCE(c.horario, 'Por definir') as horario,
                      COALESCE(c.activo, 1) as activo,
                      $campoFechaSelect
                      u.nombre,
                      u.apellidos
                  FROM cursos c
                  INNER JOIN inscripciones i ON c.$columnaPrimariaCursos = i.$campoCursoInscripciones
                  INNER JOIN usuarios u ON i.$campoUsuarioInscripciones = u.$columnaPrimariaUsuarios
                  WHERE i.$campoUsuarioInscripciones = ?
                  ORDER BY $campoFechaOrderBy";
    
    $cursos = $conexion->consultar($sqlCursos, [$idUsuario]);
    
    // Formatear cursos
    $cursosFormateados = [];
    foreach ($cursos as $curso) {
        $fechaInscripcion = '';
        if (!empty($curso['fecha_inscripcion'])) {
            try {
                $fecha = new DateTime($curso['fecha_inscripcion']);
                $fechaInscripcion = $fecha->format('d/m/Y');
            } catch (Exception $e) {
                $fechaInscripcion = 'Fecha inválida';
            }
        } else {
            $fechaInscripcion = 'Sin fecha';
        }
        
        $cursosFormateados[] = [
            'id_curso' => $curso['id_curso'],
            'nombre_curso' => $curso['nombre_curso'],
            'edad_min' => $curso['edad_min'],
            'edad_max' => $curso['edad_max'],
            'cupo_maximo' => $curso['cupo_maximo'],
            'horario' => $curso['horario'],
            'activo' => $curso['activo'],
            'estado' => $curso['activo'] ? 'activo' : 'inactivo',
            'fecha_inscripcion' => $fechaInscripcion
        ];
    }
    
    echo json_encode([
        'exito' => true,
        'usuario' => $usuario,
        'cursos' => $cursosFormateados,
        'total_cursos' => count($cursosFormateados),
        'debug_info' => [
            'campo_fecha_usado' => $campoFechaInscripcion ?: 'fecha_registro_usuarios',
            'estructura_detectada' => true
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en obtener_cursos_usuario: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al cargar información del usuario: ' . $e->getMessage(),
        'usuario' => null,
        'cursos' => [],
        'total_cursos' => 0,
        'debug_info' => [
            'error_detalle' => $e->getMessage(),
            'linea' => $e->getLine(),
            'archivo' => $e->getFile()
        ]
    ]);
}
?>
