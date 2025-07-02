<?php
/**
 * API para búsqueda de usuarios con autocompletado
 * Devuelve TODOS los datos del usuario para el formulario de actualización
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
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    if (empty($query)) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Parámetro de búsqueda requerido',
            'usuarios' => []
        ]);
        exit;
    }
    
    if (strlen($query) < 2) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Mínimo 2 caracteres para buscar',
            'usuarios' => []
        ]);
        exit;
    }
    
    $conexion = obtenerConexion();
    
    // Consulta completa con TODOS los datos del usuario
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
                COALESCE(es_derechohabiente, 0) as es_derechohabiente,
                tipo_seguro,
                direccion_calle,
                direccion_numero,
                direccion_colonia,
                direccion_ciudad,
                direccion_estado,
                direccion_cp,
                COALESCE(doc_fotografias, 0) as doc_fotografias,
                COALESCE(doc_acta_nacimiento, 0) as doc_acta_nacimiento,
                COALESCE(doc_curp, 0) as doc_curp,
                COALESCE(doc_comprobante_domicilio, 0) as doc_comprobante_domicilio,
                COALESCE(doc_ine, 0) as doc_ine,
                COALESCE(doc_cedula_afiliacion, 0) as doc_cedula_afiliacion,
                COALESCE(doc_fotos_tutores, 0) as doc_fotos_tutores,
                COALESCE(doc_ines_tutores, 0) as doc_ines_tutores,
                fecha_registro,
                DATE_FORMAT(fecha_registro, '%d/%m/%Y') as fecha_registro_formateada
            FROM usuarios 
            WHERE (
                nombre LIKE ? OR 
                apellidos LIKE ? OR 
                CONCAT(nombre, ' ', apellidos) LIKE ? OR
                curp LIKE ?
            )
            ORDER BY nombre, apellidos
            LIMIT 10";
    
    $searchTerm = "%{$query}%";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('ssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $usuarios = [];
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = [
            'id' => $row['id_usuario'],
            'id_usuario' => $row['id_usuario'],
            'nombre' => $row['nombre'],
            'apellidos' => $row['apellidos'],
            'nombre_completo' => $row['nombre_completo'],
            'curp' => $row['curp'],
            'fecha_nacimiento' => $row['fecha_nacimiento'],
            'edad' => $row['edad'],
            'numero_usuario' => $row['numero_usuario'],
            'salud' => $row['salud'],
            'tutor' => $row['tutor'],
            'numero_tutor' => $row['numero_tutor'],
            'es_derechohabiente' => $row['es_derechohabiente'],
            'tipo_seguro' => $row['tipo_seguro'],
            'direccion_calle' => $row['direccion_calle'],
            'direccion_numero' => $row['direccion_numero'],
            'direccion_colonia' => $row['direccion_colonia'],
            'direccion_ciudad' => $row['direccion_ciudad'],
            'direccion_estado' => $row['direccion_estado'],
            'direccion_cp' => $row['direccion_cp'],
            'doc_fotografias' => $row['doc_fotografias'],
            'doc_acta_nacimiento' => $row['doc_acta_nacimiento'],
            'doc_curp' => $row['doc_curp'],
            'doc_comprobante_domicilio' => $row['doc_comprobante_domicilio'],
            'doc_ine' => $row['doc_ine'],
            'doc_cedula_afiliacion' => $row['doc_cedula_afiliacion'],
            'doc_fotos_tutores' => $row['doc_fotos_tutores'],
            'doc_ines_tutores' => $row['doc_ines_tutores'],
            'fecha_registro' => $row['fecha_registro'],
            'fecha_registro_formateada' => $row['fecha_registro_formateada']
        ];
    }
    
    if (empty($usuarios)) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'No se encontraron usuarios que coincidan con la búsqueda',
            'usuarios' => []
        ]);
    } else {
        echo json_encode([
            'exito' => true,
            'mensaje' => 'Usuarios encontrados',
            'usuarios' => $usuarios,
            'total' => count($usuarios)
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en buscar_usuarios_autocompletado.php: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error en la búsqueda: ' . $e->getMessage(),
        'usuarios' => []
    ]);
}
?>
