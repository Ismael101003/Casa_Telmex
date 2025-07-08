<?php
/**
 * API para búsqueda de usuarios con autocompletado - TODOS LOS DATOS
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
    
    $query = $_GET['q'] ?? '';
    $query = trim($query);
    
    if (strlen($query) < 2) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'La búsqueda debe tener al menos 2 caracteres',
            'usuarios' => []
        ]);
        exit;
    }
    
    // Detectar la columna primaria
    $sqlVerificarColumnas = "SHOW COLUMNS FROM usuarios";
    $columnas = $conexion->consultar($sqlVerificarColumnas);
    
    $columnaPrimaria = "id_usuario";
    foreach ($columnas as $columna) {
        if ($columna['Key'] === 'PRI') {
            $columnaPrimaria = $columna['Field'];
            break;
        }
    }
    
    // Búsqueda en múltiples campos con TODOS los datos del usuario
    $sqlBusqueda = "SELECT 
    u.*,
    CONCAT(u.nombre, ' ', u.apellidos) as nombre_completo,
    TIMESTAMPDIFF(YEAR, u.fecha_nacimiento, CURDATE()) as edad_calculada,
    DATE_FORMAT(u.fecha_nacimiento, '%d/%m/%Y') as fecha_nacimiento_formateada,
    DATE_FORMAT(u.fecha_registro, '%d/%m/%Y %H:%i') as fecha_registro_formateada,

    -- Calcular documentación completa
    (CASE WHEN u.doc_fotografias = 1 THEN 1 ELSE 0 END +
     CASE WHEN u.doc_acta_nacimiento = 1 THEN 1 ELSE 0 END +
     CASE WHEN u.doc_curp = 1 THEN 1 ELSE 0 END +
     CASE WHEN u.doc_comprobante_domicilio = 1 THEN 1 ELSE 0 END +
     CASE WHEN u.doc_ine = 1 THEN 1 ELSE 0 END +
     CASE WHEN u.doc_permiso_salida = 1 THEN 1 ELSE 0 END +
     CASE WHEN u.doc_ficha_registro = 1 THEN 1 ELSE 0 END +
     CASE WHEN u.doc_cedula_afiliacion = 1 THEN 1 
         ELSE 0 
     END) as documentos_completos,

    -- Total de documentos requeridos (7 básicos + 1 si es derechohabiente)
    (7 + CASE WHEN u.es_derechohabiente = 1 THEN 1 ELSE 0 END) as documentos_requeridos

FROM usuarios u
WHERE (u.nombre LIKE ? OR 
       u.apellidos LIKE ? OR 
       u.curp LIKE ? OR 
       CONCAT(u.nombre, ' ', u.apellidos) LIKE ?)
ORDER BY u.nombre, u.apellidos
LIMIT 10;
";
    
    $searchTerm = "%{$query}%";
    $resultados = $conexion->consultar($sqlBusqueda, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    
    if (empty($resultados)) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'No se encontraron usuarios que coincidan con la búsqueda',
            'usuarios' => []
        ]);
        exit;
    }
    
    $usuariosFormateados = [];
    foreach ($resultados as $usuario) {
        // Calcular porcentaje de documentación
        $porcentajeDocumentacion = 0;
        if ($usuario['documentos_requeridos'] > 0) {
            $porcentajeDocumentacion = round(($usuario['documentos_completos'] / $usuario['documentos_requeridos']) * 100);
        }
        
        $usuariosFormateados[] = [
            // Datos básicos
            'id' => $usuario[$columnaPrimaria],
            'id_usuario' => $usuario[$columnaPrimaria],
            'nombre' => $usuario['nombre'],
            'apellidos' => $usuario['apellidos'],
            'nombre_completo' => $usuario['nombre_completo'],
            'curp' => $usuario['curp'],
            'fecha_nacimiento' => $usuario['fecha_nacimiento'],
            'fecha_nacimiento_formateada' => $usuario['fecha_nacimiento_formateada'],
            'edad' => $usuario['edad_calculada'] ?: 0,
            'numero_usuario' => $usuario['numero_usuario'],
            'salud' => $usuario['salud'] ?: 'Sin especificar',
            
            // Datos del tutor
            'tutor' => $usuario['tutor'],
            'numero_tutor' => $usuario['numero_tutor'],
            
            // Derechohabiencia y seguro
            'es_derechohabiente' => $usuario['es_derechohabiente'],
            'tipo_seguro' => $usuario['tipo_seguro'],
            
            // Dirección
            'direccion_calle' => $usuario['direccion_calle'],
            'direccion_numero' => $usuario['direccion_numero'],
            'direccion_colonia' => $usuario['direccion_colonia'],
            'direccion_ciudad' => $usuario['direccion_ciudad'],
            'direccion_estado' => $usuario['direccion_estado'],
            'direccion_cp' => $usuario['direccion_cp'],
            
            // Documentos
            'doc_fotografias' => $usuario['doc_fotografias'],
'doc_acta_nacimiento' => $usuario['doc_acta_nacimiento'],
'doc_curp' => $usuario['doc_curp'],
'doc_comprobante_domicilio' => $usuario['doc_comprobante_domicilio'],
'doc_ine' => $usuario['doc_ine'],
'doc_cedula_afiliacion' => $usuario['doc_cedula_afiliacion'],
'doc_fotos_tutores' => $usuario['doc_fotos_tutores'],
'doc_ines_tutores' => $usuario['doc_ines_tutores'],
'doc_permiso_salida' => $usuario['doc_permiso_salida'],
'doc_ficha_registro' => $usuario['doc_ficha_registro'],

            
            // Estadísticas de documentación
            'documentos_completos' => $usuario['documentos_completos'],
            'documentos_requeridos' => $usuario['documentos_requeridos'],
            'porcentaje_documentacion' => $porcentajeDocumentacion,
            'documentacion_completa' => $porcentajeDocumentacion === 100,
            
            // Fechas formateadas
            'fecha_registro' => $usuario['fecha_registro'],
            'fecha_registro_formateada' => $usuario['fecha_registro_formateada']
        ];
    }
    
    echo json_encode([
        'exito' => true,
        'usuarios' => $usuariosFormateados,
        'total' => count($usuariosFormateados),
        'mensaje' => 'Usuarios encontrados exitosamente',
        'query' => $query
    ]);
    
} catch (Exception $e) {
    error_log("Error en búsqueda de usuarios: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error interno del servidor: ' . $e->getMessage(),
        'usuarios' => []
    ]);
}
?>
