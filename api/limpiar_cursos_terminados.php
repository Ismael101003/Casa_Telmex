<?php
/**
 * API para limpiar cursos terminados (eliminar inscripciones)
 */

require_once '../config/conexion.php';
require_once '../config/configuracion.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

try {
    $conexion = obtenerConexion();
    
    $cursos_a_limpiar = $_POST['cursos'] ?? [];
    
    if (empty($cursos_a_limpiar)) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'No se especificaron cursos para limpiar'
        ]);
        exit;
    }
    
    // Iniciar transacción
    $conexion->iniciarTransaccion();
    
    try {
        $total_eliminadas = 0;
        
        foreach ($cursos_a_limpiar as $id_curso) {
            // Eliminar inscripciones del curso
            $sqlEliminar = "DELETE FROM inscripciones WHERE id_curso = ?";
            $resultado = $conexion->ejecutar($sqlEliminar, [$id_curso]);
            $total_eliminadas += $resultado['filas_afectadas'];
        }
        
        // Confirmar transacción
        $conexion->confirmarTransaccion();
        
        echo json_encode([
            'exito' => true,
            'mensaje' => "Se eliminaron $total_eliminadas inscripciones de " . count($cursos_a_limpiar) . " cursos",
            'inscripciones_eliminadas' => $total_eliminadas,
            'cursos_limpiados' => count($cursos_a_limpiar)
        ]);
        
    } catch (Exception $e) {
        $conexion->cancelarTransaccion();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error al limpiar cursos: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al limpiar cursos terminados'
    ]);
}
?>
