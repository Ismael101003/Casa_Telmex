<?php
/**
 * API para eliminar cursos y sus tablas específicas - VERSIÓN CORREGIDA
 */

// Headers para CORS y JSON
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
    // Configuración de la base de datos
    $host = 'localhost';
    $dbname = 'casatelmex';
    $username = 'root';
    $password = '';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $id = (int)($_POST['id'] ?? 0);
    
    error_log("=== ELIMINAR CURSO ===");
    error_log("ID del curso a eliminar: " . $id);
    
    if ($id <= 0) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'ID de curso no válido'
        ]);
        exit;
    }
    
    // Obtener información del curso incluyendo la tabla específica
    $sqlObtenerCurso = "SELECT id_curso, nombre_curso, tabla_curso FROM cursos WHERE id_curso = ?";
    $stmtObtener = $pdo->prepare($sqlObtenerCurso);
    $stmtObtener->execute([$id]);
    $curso = $stmtObtener->fetch(PDO::FETCH_ASSOC);
    
    if (!$curso) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Curso no encontrado'
        ]);
        exit;
    }
    
    $nombreTablaCurso = $curso['tabla_curso'] ?? '';
    
    error_log("Curso encontrado: " . $curso['nombre_curso']);
    error_log("Tabla específica: " . $nombreTablaCurso);
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    try {
        // 1. Eliminar inscripciones primero (por la clave foránea)
        $sqlBorrarInscripciones = "DELETE FROM inscripciones WHERE id_curso = ?";
        $stmtInscripciones = $pdo->prepare($sqlBorrarInscripciones);
        $stmtInscripciones->execute([$id]);
        $inscripcionesEliminadas = $stmtInscripciones->rowCount();
        error_log("Inscripciones eliminadas: " . $inscripcionesEliminadas);
        
        // 2. Eliminar tabla específica del curso si existe
        if (!empty($nombreTablaCurso)) {
            // Verificar que la tabla existe
            $sqlVerificarTabla = "SHOW TABLES LIKE ?";
            $stmtVerificar = $pdo->prepare($sqlVerificarTabla);
            $stmtVerificar->execute([$nombreTablaCurso]);
            $tablaExiste = $stmtVerificar->fetch();
            
            if ($tablaExiste) {
                // Usar consulta directa para DROP TABLE (no se puede usar parámetros)
                $nombreTablaSeguro = preg_replace('/[^a-zA-Z0-9_]/', '', $nombreTablaCurso);
                $sqlEliminarTabla = "DROP TABLE IF EXISTS `$nombreTablaSeguro`";
                $pdo->exec($sqlEliminarTabla);
                error_log("Tabla específica eliminada: " . $nombreTablaSeguro);
            }
        }
        
        // 3. Eliminar curso
        $sqlBorrarCurso = "DELETE FROM cursos WHERE id_curso = ?";
        $stmtCurso = $pdo->prepare($sqlBorrarCurso);
        $stmtCurso->execute([$id]);
        $cursosEliminados = $stmtCurso->rowCount();
        error_log("Curso eliminado, filas afectadas: " . $cursosEliminados);
        
        // Confirmar transacción
        $pdo->commit();
        
        echo json_encode([
            'exito' => true,
            'mensaje' => 'Curso y su tabla específica eliminados correctamente',
            'detalles' => [
                'inscripciones_eliminadas' => $inscripcionesEliminadas,
                'tabla_eliminada' => !empty($nombreTablaCurso),
                'curso_eliminado' => $cursosEliminados > 0
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error en transacción: " . $e->getMessage());
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("ERROR CRÍTICO al eliminar curso: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}

error_log("=== FIN ELIMINAR CURSO ===");
?>
