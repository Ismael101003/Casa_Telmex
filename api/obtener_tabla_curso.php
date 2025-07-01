<?php
/**
 * API para obtener usuarios de un curso específico para mostrar en tabla
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
    $idCurso = $_GET['id_curso'] ?? null;
    
    if (!$idCurso) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'ID de curso requerido',
            'usuarios' => []
        ]);
        exit;
    }
    
    // Detectar estructura de tablas
    $estructuraCursos = $conexion->consultar("DESCRIBE cursos");
    $estructuraUsuarios = $conexion->consultar("DESCRIBE usuarios");
    $estructuraInscripciones = $conexion->consultar("DESCRIBE inscripciones");
    
    // Determinar columnas primarias y campos
    $columnaPrimariaCursos = 'id_curso';
    $columnaPrimariaUsuarios = 'id_usuario';
    $columnaPrimariaInscripciones = 'id_inscripcion';
    $campoUsuarioInscripciones = 'id_usuario';
    $campoCursoInscripciones = 'id_curso';
    $campoFechaInscripcion = null; // No asumimos que existe
    
    foreach ($estructuraCursos as $col) {
        if ($col['Key'] === 'PRI') {
            $columnaPrimariaCursos = $col['Field'];
            break;
        }
    }
    
    foreach ($estructuraUsuarios as $col) {
        if ($col['Key'] === 'PRI') {
            $columnaPrimariaUsuarios = $col['Field'];
            break;
        }
    }
    
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
    
    // Primero obtener información del curso
    $sqlCurso = "SELECT 
                    $columnaPrimariaCursos as id_curso,
                    nombre_curso,
                    edad_min,
                    edad_max,
                    COALESCE(cupo_maximo, 30) as cupo_maximo,
                    COALESCE(horario, 'Por definir') as horario,
                    COALESCE(activo, 1) as activo
                 FROM cursos 
                 WHERE $columnaPrimariaCursos = ?";
    
    $resultadoCurso = $conexion->consultar($sqlCurso, [$idCurso]);
    
    if (empty($resultadoCurso)) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Curso no encontrado',
            'usuarios' => []
        ]);
        exit;
    }
    
    $curso = $resultadoCurso[0];
    
    // Construir campo de fecha según disponibilidad
    $campoFechaSQL = $campoFechaInscripcion ? "i.$campoFechaInscripcion" : "u.fecha_registro";
    
    // Obtener usuarios inscritos en el curso
    $sqlUsuarios = "SELECT 
                        u.$columnaPrimariaUsuarios as id_usuario,
                        u.nombre,
                        u.apellidos,
                        u.curp,
                        u.fecha_nacimiento,
                        u.edad,
                        COALESCE(u.meses, 0) as meses,
                        COALESCE(u.salud, '') as salud,
                        u.tutor,
                        COALESCE(u.numero_tutor, 'N/A') as numero_tutor,
                        COALESCE(u.numero_usuario, 'N/A') as numero_usuario,
                        u.fecha_registro,
                        $campoFechaSQL as fecha_inscripcion,
                        i.$columnaPrimariaInscripciones as id_inscripcion
                    FROM usuarios u
                    INNER JOIN inscripciones i ON u.$columnaPrimariaUsuarios = i.$campoUsuarioInscripciones
                    WHERE i.$campoCursoInscripciones = ?
                    ORDER BY u.nombre, u.apellidos";
    
    $usuarios = $conexion->consultar($sqlUsuarios, [$idCurso]);
    
    // Formatear datos de usuarios
    $usuariosFormateados = [];
    foreach ($usuarios as $usuario) {
        // Formatear fecha de nacimiento
        $fechaNacimientoFormateada = '';
        if (!empty($usuario['fecha_nacimiento'])) {
            try {
                $fecha = new DateTime($usuario['fecha_nacimiento']);
                $fechaNacimientoFormateada = $fecha->format('d/m/Y');
            } catch (Exception $e) {
                $fechaNacimientoFormateada = 'Fecha inválida';
            }
        }
        
        // Formatear fecha de inscripción
        $fechaInscripcionFormateada = '';
        if (!empty($usuario['fecha_inscripcion'])) {
            try {
                $fecha = new DateTime($usuario['fecha_inscripcion']);
                $fechaInscripcionFormateada = $fecha->format('d/m/Y H:i');
            } catch (Exception $e) {
                $fechaInscripcionFormateada = 'Fecha inválida';
            }
        }
        
        // Formatear fecha de registro
        $fechaRegistroFormateada = '';
        if (!empty($usuario['fecha_registro'])) {
            try {
                $fecha = new DateTime($usuario['fecha_registro']);
                $fechaRegistroFormateada = $fecha->format('d/m/Y H:i');
            } catch (Exception $e) {
                $fechaRegistroFormateada = 'Fecha inválida';
            }
        }
        
        $usuariosFormateados[] = [
            'id_usuario' => $usuario['id_usuario'],
            'id_inscripcion' => $usuario['id_inscripcion'],
            'nombre' => $usuario['nombre'],
            'apellidos' => $usuario['apellidos'],
            'nombre_completo' => trim($usuario['nombre'] . ' ' . $usuario['apellidos']),
            'curp' => $usuario['curp'],
            'fecha_nacimiento' => $usuario['fecha_nacimiento'],
            'fecha_nacimiento_formateada' => $fechaNacimientoFormateada,
            'edad' => $usuario['edad'],
            'meses' => $usuario['meses'],
            'salud' => $usuario['salud'],
            'tutor' => $usuario['tutor'],
            'numero_tutor' => $usuario['numero_tutor'],
            'numero_usuario' => $usuario['numero_usuario'],
            'fecha_registro' => $usuario['fecha_registro'],
            'fecha_registro_formateada' => $fechaRegistroFormateada,
            'fecha_inscripcion' => $usuario['fecha_inscripcion'],
            'fecha_inscripcion_formateada' => $fechaInscripcionFormateada
        ];
    }
    
    echo json_encode([
        'exito' => true,
        'curso' => [
            'id_curso' => $curso['id_curso'],
            'nombre_curso' => $curso['nombre_curso'],
            'edad_min' => $curso['edad_min'],
            'edad_max' => $curso['edad_max'],
            'cupo_maximo' => $curso['cupo_maximo'],
            'horario' => $curso['horario'],
            'activo' => $curso['activo'],
            'total_inscritos' => count($usuariosFormateados)
        ],
        'usuarios' => $usuariosFormateados,
        'total' => count($usuariosFormateados),
        'estructura_detectada' => [
            'campo_fecha_inscripciones' => $campoFechaInscripcion,
            'usando_fecha_usuarios' => !$campoFechaInscripcion
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en obtener_tabla_curso: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al cargar usuarios del curso: ' . $e->getMessage(),
        'usuarios' => [],
        'error_detalle' => $e->getMessage()
    ]);
}
?>
