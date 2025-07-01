<?php
/**
 * API corregida para obtener usuarios - maneja correctamente valores NULL en numero_usuario
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
    
    error_log("=== OBTENER USUARIOS CORREGIDO ===");

    // Verificar estructura de la tabla usuarios
    $sqlEstructura = "DESCRIBE usuarios";
    $estructura = $conexion->consultar($sqlEstructura);
    
    $columnas = array_column($estructura, 'Field');
    error_log("Columnas disponibles: " . implode(', ', $columnas));
    
    // Determinar columna primaria
    $columnaPrimaria = 'id';
    foreach ($estructura as $col) {
        if ($col['Key'] === 'PRI') {
            $columnaPrimaria = $col['Field'];
            break;
        }
    }
    error_log("Columna primaria: " . $columnaPrimaria);
    
    // Verificar columnas de teléfono
    $tieneNumeroTutor = in_array('numero_tutor', $columnas);
    $tieneNumeroUsuario = in_array('numero_usuario', $columnas);
    
    error_log("Tiene numero_tutor: " . ($tieneNumeroTutor ? 'sí' : 'no'));
    error_log("Tiene numero_usuario: " . ($tieneNumeroUsuario ? 'sí' : 'no'));
    
    // Construir consulta con manejo seguro de NULLs
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

    // Manejar numero_tutor con COALESCE para evitar NULLs
    if ($tieneNumeroTutor) {
        $selectColumns[] = "COALESCE(u.numero_tutor, 'N/A') as numero_tutor";
    } else {
        $selectColumns[] = "'N/A' as numero_tutor";
    }

    // Manejar numero_usuario con COALESCE para evitar NULLs
    if ($tieneNumeroUsuario) {
        $selectColumns[] = "COALESCE(NULLIF(u.numero_usuario, ''), 'N/A') as numero_usuario";
    } else {
        $selectColumns[] = "'N/A' as numero_usuario";
    }

    $selectClause = implode(", ", $selectColumns);
    
    // Verificar si existe columna activo
    $tieneActivo = in_array('activo', $columnas);
    $whereClause = $tieneActivo ? "WHERE u.activo = 1" : "";

    // Consulta simple sin JOINs para evitar problemas
    $sql = "SELECT $selectClause 
            FROM usuarios u 
            $whereClause 
            ORDER BY u.fecha_registro DESC";
    
    error_log("SQL Query: " . $sql);

    // Ejecutar consulta
    $usuarios = $conexion->consultar($sql);
    error_log("Usuarios encontrados: " . count($usuarios));

    // Formatear datos
    $usuariosFormateados = [];
    foreach ($usuarios as $usuario) {
        $usuarioFormateado = $usuario;
        
        // Formatear fecha de registro
        if (!empty($usuario['fecha_registro'])) {
            try {
                $fecha = new DateTime($usuario['fecha_registro']);
                $usuarioFormateado['fecha_registro'] = $fecha->format('d/m/Y H:i');
            } catch (Exception $e) {
                $usuarioFormateado['fecha_registro'] = $usuario['fecha_registro'];
            }
        }

        // Asegurar que los campos de teléfono no sean NULL
        $usuarioFormateado['numero_tutor'] = $usuario['numero_tutor'] ?: 'N/A';
        $usuarioFormateado['numero_usuario'] = $usuario['numero_usuario'] ?: 'N/A';
        
        // Agregar alias para compatibilidad
        $usuarioFormateado['id'] = $usuario['id_usuario'];
        
        $usuariosFormateados[] = $usuarioFormateado;
    }

    error_log("Usuarios formateados: " . count($usuariosFormateados));
    error_log("=== FIN OBTENER USUARIOS CORREGIDO ===");

    // Devolver array de usuarios
    echo json_encode($usuariosFormateados);

} catch (Exception $e) {
    error_log("ERROR en obtener_usuarios_corregido: " . $e->getMessage());
    
    echo json_encode([
        'error' => true,
        'mensaje' => 'Error al obtener usuarios: ' . $e->getMessage()
    ]);
}
?>
