<?php
/**
 * API para obtener todos los usuarios con información completa
 * Versión corregida y optimizada
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/conexion.php';

try {
    $conexion = obtenerConexion();
    
    
    $sql = "SELECT 
                id_usuario,
                nombre,
                apellidos,
                CONCAT(nombre, ' ', apellidos) as nombre_completo,
                curp,
                fecha_nacimiento,
                TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) as edad,
                COALESCE(numero_tutor, 'N/A') as numero_tutor,
                COALESCE(numero_usuario, 'N/A') as numero_usuario,
                tutor,
                COALESCE(es_derechohabiente, 0) as es_derechohabiente,
                COALESCE(doc_fotografias, 0) as doc_fotografias,
                COALESCE(doc_acta_nacimiento, 0) as doc_acta_nacimiento,
                COALESCE(doc_curp, 0) as doc_curp,
                COALESCE(doc_comprobante_domicilio, 0) as doc_comprobante_domicilio,
                COALESCE(doc_ine, 0) as doc_ine,
                COALESCE(doc_cedula_afiliacion, 0) as doc_cedula_afiliacion,
                COALESCE(doc_fotos_tutores, 0) as doc_fotos_tutores,
                COALESCE(doc_ines_tutores, 0) as doc_ines_tutores,
                fecha_registro,
                DATE_FORMAT(fecha_registro, '%d/%m/%Y %H:%i') as fecha_registro_formateada
            FROM usuarios 
            ORDER BY nombre ASC, apellidos ASC, fecha_registro DESC";
    
    $usuarios = $conexion->consultar($sql);
    
    // Procesar cada usuario
    $usuariosFormateados = [];
    foreach ($usuarios as $usuario) {
        // Calcular documentación completa
        $documentosRequeridos = [
            'doc_fotografias',
            'doc_acta_nacimiento', 
            'doc_curp',
            'doc_comprobante_domicilio',
            'doc_ine',
            'doc_fotos_tutores',
            'doc_ines_tutores'
        ];
        
        // Si es derechohabiente, agregar cédula
        if ($usuario['es_derechohabiente'] == 1) {
            $documentosRequeridos[] = 'doc_cedula_afiliacion';
        }
        
        $documentosCompletos = 0;
        $totalRequeridos = count($documentosRequeridos);
        
        foreach ($documentosRequeridos as $doc) {
            if ($usuario[$doc] == 1) {
                $documentosCompletos++;
            }
        }
        
        $porcentaje = $totalRequeridos > 0 ? round(($documentosCompletos / $totalRequeridos) * 100) : 0;
        $documentacionCompleta = ($documentosCompletos == $totalRequeridos);
        
        // Formatear usuario
        $usuarioFormateado = [
            'id' => $usuario['id_usuario'],
            'id_usuario' => $usuario['id_usuario'],
            'nombre' => $usuario['nombre'],
            'apellidos' => $usuario['apellidos'],
            'nombre_completo' => $usuario['nombre_completo'],
            'curp' => $usuario['curp'],
            'edad' => $usuario['edad'],
            'numero_tutor' => $usuario['numero_tutor'],
            'numero_usuario' => $usuario['numero_usuario'],
            'tutor' => $usuario['tutor'],
            'es_derechohabiente' => $usuario['es_derechohabiente'],
            'fecha_registro' => $usuario['fecha_registro_formateada'],
            'fecha_registro_formateada' => $usuario['fecha_registro_formateada'],
            'documentacion_completa' => $documentacionCompleta,
            'documentacion_porcentaje' => $porcentaje,
            'documentos_completos' => $documentosCompletos,
            'documentos_requeridos' => $totalRequeridos
        ];
        
        $usuariosFormateados[] = $usuarioFormateado;
    }
    
    // Devolver array directo para compatibilidad con usuarios.html
    echo json_encode($usuariosFormateados);
    
} catch (Exception $e) {
    error_log("Error en obtener_usuarios.php: " . $e->getMessage());
    echo json_encode([]);
}
?>
