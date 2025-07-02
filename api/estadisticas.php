<?php
/**
 * API para obtener estadísticas del dashboard
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Incluir conexión centralizada
require_once '../config/conexion.php';

try {
    $conexion = obtenerConexion();
    
    // Total de usuarios
    $totalUsuarios = $conexion->consultar("SELECT COUNT(*) as total FROM usuarios")[0]['total'];
    
    // Total de cursos activos
    $totalCursos = $conexion->consultar("SELECT COUNT(*) as total FROM cursos WHERE activo = 1")[0]['total'];
    
    // Total de inscripciones
    $totalInscripciones = $conexion->consultar("SELECT COUNT(*) as total FROM inscripciones")[0]['total'];
    
    // Usuarios sin documentación completa (cálculo correcto)
    $usuariosSinDoc = 0;
    $usuarios = $conexion->consultar("SELECT 
        id_usuario,
        es_derechohabiente,
        doc_fotografias,
        doc_acta_nacimiento,
        doc_curp,
        doc_comprobante_domicilio,
        doc_ine,
        doc_cedula_afiliacion,
        doc_fotos_tutores,
        doc_ines_tutores
    FROM usuarios");
    
    foreach ($usuarios as $usuario) {
        // Documentos básicos requeridos
        $documentosRequeridos = [
            $usuario['doc_fotografias'],
            $usuario['doc_acta_nacimiento'],
            $usuario['doc_curp'],
            $usuario['doc_comprobante_domicilio'],
            $usuario['doc_ine'],
            $usuario['doc_fotos_tutores'],
            $usuario['doc_ines_tutores']
        ];
        
        // Si es derechohabiente, agregar cédula de afiliación
        if ($usuario['es_derechohabiente'] == 1) {
            $documentosRequeridos[] = $usuario['doc_cedula_afiliacion'];
        }
        
        // Verificar si todos los documentos están completos
        $documentacionCompleta = true;
        foreach ($documentosRequeridos as $doc) {
            if ($doc != 1) {
                $documentacionCompleta = false;
                break;
            }
        }
        
        if (!$documentacionCompleta) {
            $usuariosSinDoc++;
        }
    }
    
    echo json_encode([
        'exito' => true,
        'estadisticas' => [
            'total_usuarios' => (int)$totalUsuarios,
            'total_cursos' => (int)$totalCursos,
            'total_inscripciones' => (int)$totalInscripciones,
            'usuarios_sin_documentacion' => (int)$usuariosSinDoc
        ]
    ]);

} catch (Exception $e) {
    error_log("Error en estadisticas.php: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al obtener estadísticas: ' . $e->getMessage(),
        'estadisticas' => [
            'total_usuarios' => 0,
            'total_cursos' => 0,
            'total_inscripciones' => 0,
            'usuarios_sin_documentacion' => 0
        ]
    ]);
}
?>
