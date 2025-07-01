<?php
/**
 * API para obtener usuarios - VERSIÓN CORREGIDA FINAL
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

try {
    $conexion = obtenerConexion();
    
    // Verificar estructura de la tabla usuarios
    $sqlEstructura = "DESCRIBE usuarios";
    $estructura = $conexion->consultar($sqlEstructura);
    
    $columnas = array_column($estructura, 'Field');
    
    // Determinar columna primaria
    $columnaPrimaria = 'id';
    foreach ($estructura as $col) {
        if ($col['Key'] === 'PRI') {
            $columnaPrimaria = $col['Field'];
            break;
        }
    }
    
    // Verificar columnas disponibles
    $tieneNumeroTutor = in_array('numero_tutor', $columnas);
    $tieneNumeroUsuario = in_array('numero_usuario', $columnas);
    $tieneActivo = in_array('activo', $columnas);
    
    // Construir consulta con manejo seguro de NULLs usando COALESCE
    $selectColumns = [
        "u.$columnaPrimaria as id_usuario",
        "u.nombre",
        "u.apellidos",
        "u.curp",
        "u.fecha_nacimiento",
        "u.edad",
        "COALESCE(u.meses, 0) as meses",
        "COALESCE(u.salud, '') as salud",
        "u.tutor",
        "u.fecha_registro"
    ];

    // Manejar numero_tutor - usar COALESCE para convertir NULL a 'N/A'
    if ($tieneNumeroTutor) {
        $selectColumns[] = "COALESCE(NULLIF(TRIM(u.numero_tutor), ''), 'N/A') as numero_tutor";
    } else {
        $selectColumns[] = "'N/A' as numero_tutor";
    }

    // Manejar numero_usuario - usar COALESCE para convertir NULL a 'N/A'
    if ($tieneNumeroUsuario) {
        $selectColumns[] = "COALESCE(NULLIF(TRIM(u.numero_usuario), ''), 'N/A') as numero_usuario";
    } else {
        $selectColumns[] = "'N/A' as numero_usuario";
    }

    $selectClause = implode(", ", $selectColumns);
    
    // Condición WHERE solo si existe la columna activo
    $whereClause = $tieneActivo ? "WHERE u.activo = 1" : "";

    // Consulta final
    $sql = "SELECT $selectClause 
            FROM usuarios u 
            $whereClause 
            ORDER BY u.fecha_registro DESC";

    // Ejecutar consulta
    $usuarios = $conexion->consultar($sql);

    // Formatear datos para el frontend
    $usuariosFormateados = [];
    foreach ($usuarios as $usuario) {
        $usuarioFormateado = $usuario;
        
        // Formatear fecha de registro
        if (!empty($usuario['fecha_registro'])) {
            try {
                $fecha = new DateTime($usuario['fecha_registro']);
                $usuarioFormateado['fecha_registro'] = $fecha->format('d/m/Y H:i');
            } catch (Exception $e) {
                // Mantener formato original si hay error
            }
        }

        // Asegurar que los campos no sean NULL (doble verificación)
        $usuarioFormateado['numero_tutor'] = $usuario['numero_tutor'] ?: 'N/A';
        $usuarioFormateado['numero_usuario'] = $usuario['numero_usuario'] ?: 'N/A';
        $usuarioFormateado['salud'] = $usuario['salud'] ?: '';
        
        // Agregar alias para compatibilidad
        $usuarioFormateado['id'] = $usuario['id_usuario'];
        
        $usuariosFormateados[] = $usuarioFormateado;
    }

    // Devolver array de usuarios (no objeto con propiedades)
    echo json_encode($usuariosFormateados);

} catch (Exception $e) {
    error_log("ERROR en obtener_usuarios: " . $e->getMessage());
    
    // En caso de error, devolver array vacío para que el frontend no falle
    echo json_encode([]);
}
?>
