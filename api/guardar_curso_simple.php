<?php
/**
 * Versión ultra simplificada para evitar el error 500
 */

// Configuración mínima
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Headers básicos
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// Función de respuesta
function enviar($data) {
    echo json_encode($data);
    exit;
}

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    enviar(['exito' => false, 'mensaje' => 'Solo POST']);
}

try {
    // Log inicial
    error_log("SIMPLE: Iniciando guardar curso");
    error_log("SIMPLE: POST = " . json_encode($_POST));
    
    // Incluir archivos básicos
    require_once '../config/conexion.php';
    
    // Verificar función
    if (!function_exists('obtenerConexion')) {
        enviar(['exito' => false, 'mensaje' => 'Función no existe']);
    }
    
    // Conexión
    $conexion = obtenerConexion();
    if (!$conexion) {
        enviar(['exito' => false, 'mensaje' => 'Sin conexión BD']);
    }
    
    // Datos básicos
    $id_curso = (int)($_POST['id_curso'] ?? 0);
    $nombre = trim($_POST['nombre_curso'] ?? '');
    $edad_min = (int)($_POST['edad_min'] ?? 0);
    $edad_max = (int)($_POST['edad_max'] ?? 100);
    $cupo = (int)($_POST['cupo_maximo'] ?? 30);
    $horario = trim($_POST['horario'] ?? '');
    $activo = (int)($_POST['activo'] ?? 1);
    
    error_log("SIMPLE: Datos - ID:$id_curso, Nombre:$nombre, Activo:$activo");
    
    // Validación mínima
    if (empty($nombre)) {
        enviar(['exito' => false, 'mensaje' => 'Nombre requerido']);
    }
    
    if ($id_curso > 0) {
        // ACTUALIZAR
        error_log("SIMPLE: Actualizando curso $id_curso");
        
        $sql = "UPDATE cursos SET nombre_curso=?, edad_min=?, edad_max=?, cupo_maximo=?, horario=? WHERE id_curso=?";
        $params = [$nombre, $edad_min, $edad_max, $cupo, $horario, $id_curso];
        
        // Verificar si existe columna activo
        try {
            $test_activo = $conexion->consultar("SELECT activo FROM cursos WHERE id_curso = ? LIMIT 1", [$id_curso]);
            if ($test_activo !== false) {
                $sql = "UPDATE cursos SET nombre_curso=?, edad_min=?, edad_max=?, cupo_maximo=?, horario=?, activo=? WHERE id_curso=?";
                $params = [$nombre, $edad_min, $edad_max, $cupo, $horario, $activo, $id_curso];
                error_log("SIMPLE: Usando columna activo");
            }
        } catch (Exception $e) {
            error_log("SIMPLE: Sin columna activo: " . $e->getMessage());
        }
        
        $resultado = $conexion->ejecutar($sql, $params);
        error_log("SIMPLE: Resultado UPDATE = " . json_encode($resultado));
        
        if ($resultado && $resultado['exito']) {
            enviar(['exito' => true, 'mensaje' => 'Curso actualizado']);
        } else {
            enviar(['exito' => false, 'mensaje' => 'Error al actualizar']);
        }
        
    } else {
        // CREAR NUEVO
        error_log("SIMPLE: Creando nuevo curso");
        
        $sql = "INSERT INTO cursos (nombre_curso, edad_min, edad_max, cupo_maximo, horario) VALUES (?, ?, ?, ?, ?)";
        $params = [$nombre, $edad_min, $edad_max, $cupo, $horario];
        
        $resultado = $conexion->ejecutar($sql, $params);
        error_log("SIMPLE: Resultado INSERT = " . json_encode($resultado));
        
        if ($resultado && $resultado['exito'] && $resultado['ultimo_id']) {
            $nuevo_id = $resultado['ultimo_id'];
            
            // Crear tabla específica simple
            try {
                $tabla_nombre = "curso_" . $nuevo_id;
                $pdo = $conexion->obtenerPDO();
                if ($pdo) {
                    $sql_tabla = "CREATE TABLE IF NOT EXISTS `$tabla_nombre` (
                        id_registro INT AUTO_INCREMENT PRIMARY KEY,
                        id_usuario INT NOT NULL,
                        nombre VARCHAR(100),
                        apellidos VARCHAR(150),
                        curp VARCHAR(18),
                        fecha_inscripcion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )";
                    $pdo->exec($sql_tabla);
                    
                    // Actualizar referencia
                    $conexion->ejecutar("UPDATE cursos SET tabla_curso = ? WHERE id_curso = ?", [$tabla_nombre, $nuevo_id]);
                    error_log("SIMPLE: Tabla $tabla_nombre creada");
                }
            } catch (Exception $e) {
                error_log("SIMPLE: Error creando tabla: " . $e->getMessage());
            }
            
            enviar(['exito' => true, 'mensaje' => 'Curso creado', 'id_curso' => $nuevo_id]);
        } else {
            enviar(['exito' => false, 'mensaje' => 'Error al crear curso']);
        }
    }
    
} catch (Exception $e) {
    error_log("SIMPLE: Error = " . $e->getMessage());
    enviar(['exito' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
}
?>
