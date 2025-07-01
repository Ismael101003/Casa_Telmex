<?php
/**
 * API para obtener detalles completos de un usuario específico
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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

try {
    $conexion = obtenerConexion();
    
    $id_usuario = (int)($_GET['id'] ?? 0);
    
    if ($id_usuario <= 0) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'ID de usuario no válido'
        ]);
        exit;
    }
    
    // Determinar la columna primaria
    $sqlVerificarColumnas = "SHOW COLUMNS FROM usuarios";
    $columnas = $conexion->consultar($sqlVerificarColumnas);
    
    $columnaPrimaria = "id";
    foreach ($columnas as $columna) {
        if ($columna['Key'] === 'PRI') {
            $columnaPrimaria = $columna['Field'];
            break;
        }
    }
    
    // Obtener datos básicos del usuario
    $sqlUsuario = "SELECT * FROM usuarios WHERE $columnaPrimaria = ?";
    $usuario = $conexion->consultar($sqlUsuario, [$id_usuario]);
    
    if (empty($usuario)) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Usuario no encontrado'
        ]);
        exit;
    }
    
    $datosUsuario = $usuario[0];
    
    // Obtener cursos inscritos
    $sqlCursos = "SELECT 
                      c.id_curso,
                      c.nombre_curso,
                      c.horario,
                      c.edad_min,
                      c.edad_max,
                  FROM inscripciones i
                  INNER JOIN cursos c ON i.id_curso = c.id_curso
                  WHERE i.id_usuario = ?
                  ;
    
    $cursosInscritos = $conexion->consultar($sqlCursos, [$id_usuario]);
    
    // Formatear fechas
    if (!empty($datosUsuario['fecha_nacimiento'])) {
        $fecha = new DateTime($datosUsuario['fecha_nacimiento']);
        $datosUsuario['fecha_nacimiento_formateada'] = $fecha->format('d/m/Y');
    }
    
    if (!empty($datosUsuario['fecha_registro'])) {
        $fecha = new DateTime($datosUsuario['fecha_registro']);
        $datosUsuario['fecha_registro_formateada'] = $fecha->format('d/m/Y H:i');
    }
    
    // Formatear fechas de inscripción
    foreach ($cursosInscritos as &$curso) {
        if (!empty($curso['fecha_inscripcion'])) {
            $fecha = new DateTime($curso['fecha_inscripcion']);
            $curso['fecha_inscripcion_formateada'] = $fecha->format('d/m/Y H:i');
        }
    }
    
    echo json_encode([
        'exito' => true,
        'usuario' => $datosUsuario,
        'cursos' => $cursosInscritos,
        'total_cursos' => count($cursosInscritos)
    ]);
    
} catch (Exception $e) {
    error_log("Error al obtener detalles del usuario: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>
