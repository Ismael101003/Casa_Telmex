<?php
/**
 * Versión de debug para obtener usuarios con logging detallado
 */

require_once '../config/conexion.php';
require_once '../config/configuracion.php';

// Headers para CORS y JSON
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Habilitar logging de errores
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/debug_usuarios.log');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$debug = [];
$debug['inicio'] = date('Y-m-d H:i:s');

try {
    $debug['paso_1'] = 'Obteniendo conexión';
    $conexion = obtenerConexion();
    $debug['paso_1_resultado'] = 'Conexión exitosa';

    // Verificar base de datos
    $debug['paso_2'] = 'Verificando base de datos';
    $sqlDB = "SELECT DATABASE() as db";
    $dbResult = $conexion->consultar($sqlDB);
    $debug['paso_2_resultado'] = $dbResult[0]['db'];

    // Verificar tabla usuarios
    $debug['paso_3'] = 'Verificando tabla usuarios';
    $sqlTabla = "SHOW TABLES LIKE 'usuarios'";
    $tablaExiste = $conexion->consultar($sqlTabla);
    
    if (empty($tablaExiste)) {
        $debug['error'] = 'Tabla usuarios no existe';
        echo json_encode([
            'error' => true,
            'mensaje' => 'La tabla usuarios no existe',
            'debug' => $debug
        ]);
        exit;
    }
    $debug['paso_3_resultado'] = 'Tabla usuarios existe';

    // Verificar estructura
    $debug['paso_4'] = 'Verificando estructura';
    $sqlEstructura = "DESCRIBE usuarios";
    $estructura = $conexion->consultar($sqlEstructura);
    $columnas = array_column($estructura, 'Field');
    $debug['paso_4_resultado'] = $columnas;

    // Determinar columna primaria
    $columnaPrimaria = 'id';
    foreach ($estructura as $col) {
        if ($col['Key'] === 'PRI') {
            $columnaPrimaria = $col['Field'];
            break;
        }
    }
    $debug['columna_primaria'] = $columnaPrimaria;

    // Verificar si hay datos
    $debug['paso_5'] = 'Contando usuarios';
    $sqlCount = "SELECT COUNT(*) as total FROM usuarios";
    $count = $conexion->consultar($sqlCount);
    $debug['paso_5_resultado'] = $count[0]['total'];

    if ($count[0]['total'] == 0) {
        $debug['advertencia'] = 'No hay usuarios en la tabla';
        echo json_encode([
            'error' => false,
            'usuarios' => [],
            'mensaje' => 'No hay usuarios registrados',
            'debug' => $debug
        ]);
        exit;
    }

    // Verificar columnas de teléfono
    $tieneNumeroTutor = in_array('numero_tutor', $columnas);
    $tieneNumeroUsuario = in_array('numero_usuario', $columnas);
    $debug['columnas_telefono'] = [
        'numero_tutor' => $tieneNumeroTutor,
        'numero_usuario' => $tieneNumeroUsuario
    ];

    // Construir consulta
    $debug['paso_6'] = 'Construyendo consulta';
    
    $selectColumns = [
        "$columnaPrimaria as id_usuario",
        "nombre",
        "apellidos",
        "curp",
        "fecha_nacimiento",
        "edad",
        "tutor",
        "fecha_registro"
    ];

    if ($tieneNumeroTutor) {
        $selectColumns[] = "numero_tutor";
    } else {
        $selectColumns[] = "'N/A' as numero_tutor";
    }

    if ($tieneNumeroUsuario) {
        $selectColumns[] = "numero_usuario";
    } else {
        $selectColumns[] = "'N/A' as numero_usuario";
    }

    $selectClause = implode(", ", $selectColumns);
    
    // Verificar si existe columna activo
    $tieneActivo = in_array('activo', $columnas);
    $whereClause = $tieneActivo ? "WHERE activo = 1" : "";

    $sql = "SELECT $selectClause FROM usuarios $whereClause ORDER BY fecha_registro DESC LIMIT 50";
    $debug['consulta_sql'] = $sql;

    // Ejecutar consulta
    $debug['paso_7'] = 'Ejecutando consulta';
    $usuarios = $conexion->consultar($sql);
    $debug['paso_7_resultado'] = count($usuarios) . ' usuarios obtenidos';

    // Formatear datos
    $usuariosFormateados = [];
    foreach ($usuarios as $usuario) {
        $usuarioFormateado = $usuario;
        
        // Formatear fechas
        if (!empty($usuario['fecha_registro'])) {
            try {
                $fecha = new DateTime($usuario['fecha_registro']);
                $usuarioFormateado['fecha_registro'] = $fecha->format('d/m/Y H:i');
            } catch (Exception $e) {
                // Mantener formato original
            }
        }

        // Asegurar campos de teléfono
        $usuarioFormateado['numero_tutor'] = $usuario['numero_tutor'] ?? 'N/A';
        $usuarioFormateado['numero_usuario'] = $usuario['numero_usuario'] ?? 'N/A';
        
        // Agregar alias para compatibilidad
        $usuarioFormateado['id'] = $usuario['id_usuario'];
        
        $usuariosFormateados[] = $usuarioFormateado;
    }

    $debug['fin'] = date('Y-m-d H:i:s');
    $debug['total_formateados'] = count($usuariosFormateados);

    echo json_encode($usuariosFormateados);

} catch (Exception $e) {
    $debug['error_critico'] = [
        'mensaje' => $e->getMessage(),
        'archivo' => $e->getFile(),
        'linea' => $e->getLine(),
        'timestamp' => date('Y-m-d H:i:s')
    ];

    error_log("ERROR en obtener_usuarios_debug: " . json_encode($debug));

    echo json_encode([
        'error' => true,
        'mensaje' => 'Error crítico: ' . $e->getMessage(),
        'debug' => $debug
    ]);
}
?>
