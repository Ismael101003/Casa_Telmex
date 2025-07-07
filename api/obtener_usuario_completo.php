<?php
/**
 * API para obtener datos completos de un usuario específico
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
    
    $idUsuario = $_GET['id'] ?? 0;
    
    if (!$idUsuario) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'ID de usuario requerido'
        ]);
        exit;
    }
    
    $sql = "SELECT 
                id_usuario,
                nombre,
                apellidos,
                CONCAT(nombre, ' ', apellidos) as nombre_completo,
                curp,
                fecha_nacimiento,
                TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) as edad,
                numero_usuario,
                salud,
                tutor,
                numero_tutor,
                es_derechohabiente,
                tipo_seguro,
                direccion_calle,
                direccion_numero,
                direccion_colonia,
                direccion_ciudad,
                direccion_estado,
                direccion_cp,
                doc_fotografias,
                doc_acta_nacimiento,
                doc_curp,
                doc_comprobante_domicilio,
                doc_ine,
                doc_cedula_afiliacion,
                doc_fotos_tutores,
                doc_ines_tutores,
                doc_ficha_registro,
                doc_permiso_salida,
                fecha_registro,
                DATE_FORMAT(fecha_registro, '%d/%m/%Y %H:%i') as fecha_registro_formateada
            FROM usuarios 
            WHERE id_usuario = ?";
    
    $resultado = $conexion->consultar($sql, [$idUsuario]);
    
    if (empty($resultado)) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Usuario no encontrado'
        ]);
        exit;
    }
    
    $usuario = $resultado[0];
    
    // Formatear fecha de nacimiento para input date
    if ($usuario['fecha_nacimiento']) {
        try {
            $fecha = new DateTime($usuario['fecha_nacimiento']);
            $usuario['fecha_nacimiento'] = $fecha->format('Y-m-d');
        } catch (Exception $e) {
            // Mantener formato original si hay error
        }
    }
    
    echo json_encode([
        'exito' => true,
        'usuario' => $usuario
    ]);
    
} catch (Exception $e) {
    error_log("Error en obtener_usuario_completo.php: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error interno del servidor'
    ]);
}
?>
