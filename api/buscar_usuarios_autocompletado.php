<?php
/**
 * API para autocompletado de búsqueda de usuarios
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
    
    $query = trim($_GET['q'] ?? '');
    
    if (strlen($query) < 2) {
        echo json_encode([
            'exito' => true,
            'usuarios' => [],
            'mensaje' => 'Búsqueda muy corta'
        ]);
        exit;
    }
    
    // Verificar la estructura de la tabla usuarios
    $sqlVerificarColumnas = "SHOW COLUMNS FROM usuarios";
    $columnas = $conexion->consultar($sqlVerificarColumnas);
    
    $columnaPrimaria = "id_usuario";
    $camposDisponibles = [];
    
    foreach ($columnas as $columna) {
        $camposDisponibles[] = $columna['Field'];
        if ($columna['Key'] === 'PRI') {
            $columnaPrimaria = $columna['Field'];
        }
    }
    
    // Construir SELECT dinámicamente
    $camposSelect = [$columnaPrimaria . ' as id'];
    $camposObligatorios = ['nombre', 'apellidos', 'curp'];
    
    foreach ($camposObligatorios as $campo) {
        if (in_array($campo, $camposDisponibles)) {
            $camposSelect[] = $campo;
        }
    }
    
    // Campos opcionales
    $camposOpcionales = ['fecha_nacimiento', 'edad', 'meses', 'salud', 'tutor', 'numero_tutor', 'numero_usuario', 'telefono_usuario', 'fecha_registro'];
    foreach ($camposOpcionales as $campo) {
        if (in_array($campo, $camposDisponibles)) {
            $camposSelect[] = $campo;
        }
    }
    
    // Agregar nombre completo si tenemos nombre y apellidos
    if (in_array('nombre', $camposDisponibles) && in_array('apellidos', $camposDisponibles)) {
        $camposSelect[] = "CONCAT(COALESCE(nombre, ''), ' ', COALESCE(apellidos, '')) as nombre_completo";
    }
    
    // Construir WHERE dinámicamente
    $condicionesWhere = [];
    $parametros = [];
    $searchTerm = "%$query%";
    
    if (in_array('nombre', $camposDisponibles)) {
        $condicionesWhere[] = "nombre LIKE ?";
        $parametros[] = $searchTerm;
    }
    
    if (in_array('apellidos', $camposDisponibles)) {
        $condicionesWhere[] = "apellidos LIKE ?";
        $parametros[] = $searchTerm;
    }
    
    if (in_array('curp', $camposDisponibles)) {
        $condicionesWhere[] = "curp LIKE ?";
        $parametros[] = $searchTerm;
    }
    
    // Si tenemos nombre y apellidos, buscar en nombre completo
    if (in_array('nombre', $camposDisponibles) && in_array('apellidos', $camposDisponibles)) {
        $condicionesWhere[] = "CONCAT(COALESCE(nombre, ''), ' ', COALESCE(apellidos, '')) LIKE ?";
        $parametros[] = $searchTerm;
    }
    
    if (empty($condicionesWhere)) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'No se encontraron campos válidos para búsqueda',
            'campos_disponibles' => $camposDisponibles
        ]);
        exit;
    }
    
    $sql = "SELECT " . implode(', ', $camposSelect) . " 
            FROM usuarios 
            WHERE (" . implode(' OR ', $condicionesWhere) . ") 
            ORDER BY nombre, apellidos
            LIMIT 10";
    
    $resultado = $conexion->consultar($sql, $parametros);
    
    // Formatear resultados
    $usuariosFormateados = [];
    foreach ($resultado as $usuario) {
        $usuarioFormateado = [
            'id' => $usuario['id'],
            'nombre' => $usuario['nombre'] ?? '',
            'apellidos' => $usuario['apellidos'] ?? '',
            'nombre_completo' => $usuario['nombre_completo'] ?? ($usuario['nombre'] . ' ' . $usuario['apellidos']),
            'curp' => $usuario['curp'] ?? '',
            'edad' => $usuario['edad'] ?? 0,
            'meses' => $usuario['meses'] ?? 0,
            'salud' => $usuario['salud'] ?? '',
            'tutor' => $usuario['tutor'] ?? '',
            'numero_tutor' => $usuario['numero_tutor'] ?? '',
            'numero_usuario' => $usuario['numero_usuario'] ?? '',
            'telefono_usuario' => $usuario['telefono_usuario'] ?? '',
            'fecha_registro' => $usuario['fecha_registro'] ?? null
        ];
        
        // Formatear fecha de nacimiento
        if (isset($usuario['fecha_nacimiento']) && $usuario['fecha_nacimiento']) {
            try {
                $fecha = new DateTime($usuario['fecha_nacimiento']);
                $usuarioFormateado['fecha_nacimiento'] = $fecha->format('Y-m-d');
            } catch (Exception $e) {
                $usuarioFormateado['fecha_nacimiento'] = $usuario['fecha_nacimiento'];
            }
        } else {
            $usuarioFormateado['fecha_nacimiento'] = '';
        }
        
        // Formatear fecha de registro
        if (isset($usuario['fecha_registro']) && $usuario['fecha_registro']) {
            try {
                $fecha = new DateTime($usuario['fecha_registro']);
                $usuarioFormateado['fecha_registro_formateada'] = $fecha->format('d/m/Y');
            } catch (Exception $e) {
                $usuarioFormateado['fecha_registro_formateada'] = 'Fecha inválida';
            }
        } else {
            $usuarioFormateado['fecha_registro_formateada'] = 'Sin fecha';
        }
        
        $usuariosFormateados[] = $usuarioFormateado;
    }
    
    echo json_encode([
        'exito' => true,
        'usuarios' => $usuariosFormateados,
        'total' => count($usuariosFormateados)
    ]);
    
} catch (Exception $e) {
    error_log("Error en autocompletado: " . $e->getMessage());
    error_log("Archivo: " . __FILE__ . " Línea: " . $e->getLine());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error interno del servidor',
        'error_detalle' => $e->getMessage()
    ]);
}
?>
