<?php
/**
 * API para eliminar una inscripción específica
 */

require_once '../config/conexion.php';
require_once '../config/configuracion.php';

// Headers para CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

try {
    $conexion = obtenerConexion();
    
    $id_inscripcion = intval($_POST['id'] ?? 0);
    
    if ($id_inscripcion <= 0) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'ID de inscripción no válido'
        ]);
        exit;
    }
    
    // Analizar estructura de tablas
    $estructura = analizarEstructuraTablas($conexion);
    
    $columnaPrimariaInscripciones = $estructura['inscripciones']['primaria'];
    $campoUsuarioInscripciones = $estructura['inscripciones']['usuario'];
    $campoCursoInscripciones = $estructura['inscripciones']['curso'];
    
    // Obtener información de la inscripción antes de eliminarla
    $sqlObtener = "SELECT $campoUsuarioInscripciones as usuario_id, $campoCursoInscripciones as curso_id 
                   FROM inscripciones 
                   WHERE $columnaPrimariaInscripciones = ?";
    
    $inscripcion = $conexion->consultar($sqlObtener, [$id_inscripcion]);
    
    if (empty($inscripcion)) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Inscripción no encontrada'
        ]);
        exit;
    }
    
    $usuarioId = $inscripcion[0]['usuario_id'];
    $cursoId = $inscripcion[0]['curso_id'];
    
    // Eliminar de la tabla inscripciones
    $sqlEliminar = "DELETE FROM inscripciones WHERE $columnaPrimariaInscripciones = ?";
    $resultado = $conexion->ejecutar($sqlEliminar, [$id_inscripcion]);
    
    if (!$resultado) {
        throw new Exception("Error al eliminar la inscripción de la tabla principal");
    }
    
    // Intentar eliminar de la tabla específica del curso si existe
    $nombreTablaCurso = "curso_$cursoId";
    $sqlVerificarTabla = "SHOW TABLES LIKE '$nombreTablaCurso'";
    $tablaExiste = $conexion->consultar($sqlVerificarTabla);
    
    if (!empty($tablaExiste)) {
        try {
            // Verificar estructura de la tabla específica
            $sqlColumnasTabla = "SHOW COLUMNS FROM $nombreTablaCurso";
            $columnasTabla = $conexion->consultar($sqlColumnasTabla);
            
            $campoUsuarioTabla = 'usuario_id';
            foreach ($columnasTabla as $columna) {
                if (in_array($columna['Field'], ['usuario_id', 'id_usuario'])) {
                    $campoUsuarioTabla = $columna['Field'];
                    break;
                }
            }
            
            $sqlEliminarTabla = "DELETE FROM $nombreTablaCurso WHERE $campoUsuarioTabla = ?";
            $conexion->ejecutar($sqlEliminarTabla, [$usuarioId]);
            
        } catch (Exception $e) {
            error_log("Error al eliminar de tabla específica $nombreTablaCurso: " . $e->getMessage());
            // No fallar si no se puede eliminar de la tabla específica
        }
    }
    
    echo json_encode([
        'exito' => true,
        'mensaje' => 'Inscripción eliminada exitosamente'
    ]);
    
} catch (Exception $e) {
    error_log("Error al eliminar inscripción: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al eliminar inscripción: ' . $e->getMessage()
    ]);
}

function analizarEstructuraTablas($conexion) {
    $estructura = [];
    
    // Analizar tabla inscripciones
    $sqlInscripciones = "SHOW COLUMNS FROM inscripciones";
    $columnasInscripciones = $conexion->consultar($sqlInscripciones);
    
    $estructura['inscripciones'] = [
        'primaria' => 'id',
        'usuario' => 'id_usuario',
        'curso' => 'id_curso',
        'campos' => []
    ];
    
    foreach ($columnasInscripciones as $columna) {
        $campo = $columna['Field'];
        $estructura['inscripciones']['campos'][] = $campo;
        
        if ($columna['Key'] === 'PRI') {
            $estructura['inscripciones']['primaria'] = $campo;
        }
        
        // Detectar campo de usuario
        if (in_array($campo, ['usuario_id', 'id_usuario'])) {
            $estructura['inscripciones']['usuario'] = $campo;
        }
        
        // Detectar campo de curso
        if (in_array($campo, ['curso_id', 'id_curso'])) {
            $estructura['inscripciones']['curso'] = $campo;
        }
    }
    
    return $estructura;
}
?>
