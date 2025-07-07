<?php
/**
 * API para actualizar usuario completo con todos los campos
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['exito' => false, 'mensaje' => 'Solo se permite POST']);
    exit;
}

// Incluir conexión centralizada
require_once '../config/conexion.php';

try {
    $conexion = obtenerConexion();

    $id_usuario = (int)($_POST['id_usuario'] ?? 0);
    
    if ($id_usuario <= 0) {
        throw new Exception('ID de usuario no válido');
    }

    // Obtener datos del formulario
    $datos = [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'apellidos' => trim($_POST['apellidos'] ?? ''),
        'curp' => strtoupper(trim($_POST['curp'] ?? '')),
        'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? '',
        'numero_usuario' => trim($_POST['numero_usuario'] ?? ''),
        'salud' => trim($_POST['salud'] ?? ''),
        'tutor' => trim($_POST['tutor'] ?? ''),
        'numero_tutor' => trim($_POST['numero_tutor'] ?? ''),
        'es_derechohabiente' => (int)($_POST['es_derechohabiente'] ?? 0),
        'tipo_seguro' => trim($_POST['tipo_seguro'] ?? ''),
        'direccion_calle' => trim($_POST['direccion_calle'] ?? ''),
        'direccion_numero' => trim($_POST['direccion_numero'] ?? ''),
        'direccion_colonia' => trim($_POST['direccion_colonia'] ?? ''),
        'direccion_ciudad' => trim($_POST['direccion_ciudad'] ?? ''),
        'direccion_estado' => trim($_POST['direccion_estado'] ?? ''),
        'direccion_cp' => trim($_POST['direccion_cp'] ?? ''),
        'doc_fotografias' => (int)($_POST['doc_fotografias'] ?? 0),
        'doc_acta_nacimiento' => (int)($_POST['doc_acta_nacimiento'] ?? 0),
        'doc_curp' => (int)($_POST['doc_curp'] ?? 0),
        'doc_comprobante_domicilio' => (int)($_POST['doc_comprobante_domicilio'] ?? 0),
        'doc_ine' => (int)($_POST['doc_ine'] ?? 0),
        'doc_cedula_afiliacion' => (int)($_POST['doc_cedula_afiliacion'] ?? 0),
        'doc_fotos_tutores' => (int)($_POST['doc_fotos_tutores'] ?? 0),
        'doc_ines_tutores' => (int)($_POST['doc_ines_tutores'] ?? 0),
        'doc_ficha_registro' => (int)($_POST['doc_ficha_registro'] ?? 0),
        'doc_permiso_salida' => (int)($_POST['doc_permiso_salida'] ?? 0)
    ];

    // Validaciones básicas
    if (empty($datos['nombre']) || empty($datos['apellidos']) || empty($datos['fecha_nacimiento'])) {
        throw new Exception('Los campos nombre, apellidos y fecha de nacimiento son obligatorios');
    }

    // Calcular edad
    $fechaNacimiento = new DateTime($datos['fecha_nacimiento']);
    $hoy = new DateTime();
    $edad = $hoy->diff($fechaNacimiento)->y;
    $meses = $hoy->diff($fechaNacimiento)->m;

    // Calcular si la documentación está completa
    $documentosRequeridos = [
        'doc_fotografias', 'doc_acta_nacimiento', 'doc_curp', 
        'doc_comprobante_domicilio', 'doc_ine', 'doc_fotos_tutores', 
        'doc_ines_tutores', 'doc_ficha_registro', 'doc_permiso_salida'
    ];
    
    if ($datos['es_derechohabiente']) {
        $documentosRequeridos[] = 'doc_cedula_afiliacion';
    }

    $documentosCompletos = true;
    foreach ($documentosRequeridos as $doc) {
        if (!$datos[$doc]) {
            $documentosCompletos = false;
            break;
        }
    }

    // Verificar si existe otro usuario con el mismo CURP (excluyendo el actual)
    if (!empty($datos['curp'])) {
        $sqlVerificar = "SELECT id_usuario FROM usuarios WHERE curp = ? AND id_usuario != ? LIMIT 1";
        $usuarioExistente = $conexion->consultar($sqlVerificar, [$datos['curp'], $id_usuario]);
        
        if (!empty($usuarioExistente)) {
            throw new Exception('Ya existe otro usuario con este CURP');
        }
    }

    // Actualizar usuario
    $sql = "UPDATE usuarios SET 
            nombre = ?, apellidos = ?, curp = ?, fecha_nacimiento = ?, edad = ?, meses = ?,
            numero_usuario = ?, salud = ?, tutor = ?, numero_tutor = ?,
            es_derechohabiente = ?, tipo_seguro = ?,
            direccion_calle = ?, direccion_numero = ?, direccion_colonia = ?, 
            direccion_ciudad = ?, direccion_estado = ?, direccion_cp = ?,
            doc_fotografias = ?, doc_acta_nacimiento = ?, doc_curp = ?, 
            doc_comprobante_domicilio = ?, doc_ine = ?, doc_cedula_afiliacion = ?,
            doc_fotos_tutores = ?, doc_ines_tutores = ?, doc_ficha_registro = ?, doc_permiso_salida = ?, documentacion_completa = ?
            WHERE id_usuario = ?";

    $params = [
        $datos['nombre'], $datos['apellidos'], $datos['curp'], $datos['fecha_nacimiento'], 
        $edad, $meses, $datos['numero_usuario'], $datos['salud'], $datos['tutor'], 
        $datos['numero_tutor'], $datos['es_derechohabiente'], $datos['tipo_seguro'],
        $datos['direccion_calle'], $datos['direccion_numero'], $datos['direccion_colonia'],
        $datos['direccion_ciudad'], $datos['direccion_estado'], $datos['direccion_cp'],
        $datos['doc_fotografias'], $datos['doc_acta_nacimiento'], $datos['doc_curp'],
        $datos['doc_comprobante_domicilio'], $datos['doc_ine'], $datos['doc_cedula_afiliacion'],
        $datos['doc_fotos_tutores'], $datos['doc_ines_tutores'], $datos['doc_ficha_registro'], $datos['doc_permiso_salida'], $documentosCompletos ? 1 : 0,
        $id_usuario
    ];

    $conexion->ejecutar($sql, $params);

    echo json_encode([
        'exito' => true,
        'mensaje' => 'Usuario actualizado exitosamente',
        'id_usuario' => $id_usuario
    ]);

} catch (Exception $e) {
    error_log("Error en actualizar_usuario_completo.php: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al actualizar usuario: ' . $e->getMessage()
        
    ]);
}
?>
