<?php
/**
 * API para buscar usuario existente por CURP o nombre
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
    
    $curpBusqueda = strtoupper(trim($_GET['curp'] ?? ''));
    $nombreBusqueda = trim($_GET['nombre'] ?? '');
    
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
    
    // Construir campos SELECT
    $camposSelect = [
        $columnaPrimaria . ' as id',
        'nombre',
        'apellidos',
        'curp',
        'fecha_nacimiento',
        'edad',
        'meses',
        'salud',
        'tutor',
        'numero_tutor',
        'numero_usuario',
        'fecha_registro',
        "CONCAT(nombre, ' ', apellidos) as nombre_completo"
    ];
    
    // Filtrar solo campos que existen
    $camposSelectFiltrados = [];
    foreach ($camposSelect as $campo) {
        if (strpos($campo, ' as ') !== false) {
            // Es un alias, verificar el campo base
            $partes = explode(' as ', $campo);
            $campoBase = trim($partes[0]);
            if (strpos($campoBase, 'CONCAT') !== false || in_array($campoBase, $camposDisponibles)) {
                $camposSelectFiltrados[] = $campo;
            }
        } else {
            if (in_array($campo, $camposDisponibles)) {
                $camposSelectFiltrados[] = $campo;
            }
        }
    }
    
    $sql = "SELECT " . implode(', ', $camposSelectFiltrados) . " FROM usuarios WHERE (";
    
    $params = [];
    $conditions = [];
    
    // Buscar por CURP si se proporciona
    if (!empty($curpBusqueda)) {
        if (validarCURP($curpBusqueda)) {
            $conditions[] = "curp = ?";
            $params[] = $curpBusqueda;
        } else {
            // Si no es un CURP válido, buscar como nombre
            $conditions[] = "(CONCAT(nombre, ' ', apellidos) LIKE ? OR nombre LIKE ? OR apellidos LIKE ?)";
            $searchTerm = "%$curpBusqueda%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
    }
    
    // Buscar por nombre si se proporciona
    if (!empty($nombreBusqueda)) {
        $conditions[] = "(CONCAT(nombre, ' ', apellidos) LIKE ? OR nombre LIKE ? OR apellidos LIKE ?)";
        $searchTerm = "%$nombreBusqueda%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (empty($conditions)) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Debe proporcionar CURP o nombre para buscar'
        ]);
        exit;
    }
    
    $sql .= implode(' OR ', $conditions) . ")";
    
    $resultado = $conexion->consultar($sql, $params);
    
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
    
    // Obtener cursos en los que está inscrito
    $sqlCursos = "SELECT c.id_curso, c.nombre_curso 
                  FROM cursos c 
                  INNER JOIN inscripciones i ON c.id_curso = i.id_curso 
                  WHERE i.id_usuario = ?";
    
    $cursosInscritos = $conexion->consultar($sqlCursos, [$usuario['id']]);
    
    echo json_encode([
        'exito' => true,
        'usuario' => [
            'id' => $usuario['id'],
            'nombre' => $usuario['nombre'],
            'apellidos' => $usuario['apellidos'],
            'nombre_completo' => $usuario['nombre_completo'],
            'curp' => $usuario['curp'],
            'fecha_nacimiento' => $usuario['fecha_nacimiento'],
            'edad' => $usuario['edad'] ?? 0,
            'meses' => $usuario['meses'] ?? 0,
            'salud' => $usuario['salud'] ?? '',
            'tutor' => $usuario['tutor'] ?? '',
            'numero_tutor' => $usuario['numero_tutor'] ?? '',
            'numero_usuario' => $usuario['numero_usuario'] ?? '',
            'fecha_registro' => $usuario['fecha_registro'],
            'cursos_inscritos' => $cursosInscritos
        ],
        'mensaje' => 'Usuario encontrado'
    ]);
    
} catch (Exception $e) {
    error_log("Error al buscar usuario: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error interno del servidor'
    ]);
}
?>
