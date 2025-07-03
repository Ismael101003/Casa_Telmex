<?php
/**
 * API para guardar cursos - VERSIÓN CON CLASE CONEXION
 */

// Incluir la clase de conexión
require_once '../config/conexion.php';

// Headers básicos
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Función de respuesta rápida
function responder($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Manejar OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(['exito' => false, 'mensaje' => 'Solo se permite POST']);
}

try {
    // Obtener conexión usando la clase
    $conexion = obtenerConexion();
    
    // Obtener y limpiar datos del formulario
    $id_curso = isset($_POST['id_curso']) ? (int)$_POST['id_curso'] : 0;
    $nombre_curso = trim($_POST['nombre_curso'] ?? '');
    $edad_min = (int)($_POST['edad_min'] ?? 0);
    $edad_max = (int)($_POST['edad_max'] ?? 100);
    $cupo_maximo = (int)($_POST['cupo_maximo'] ?? 30);
    $horario = trim($_POST['horario'] ?? '');
    $sala = trim($_POST['sala'] ?? '');
    $instructor = trim($_POST['instructor'] ?? '');
    $activo = (int)($_POST['activo'] ?? 1);
    
    // Validaciones básicas
    if (empty($nombre_curso)) {
        responder(['exito' => false, 'mensaje' => 'El nombre del curso es obligatorio']);
    }
    
    if ($edad_min < 0 || $edad_max < 0 || $edad_min > $edad_max) {
        responder(['exito' => false, 'mensaje' => 'Las edades no son válidas']);
    }
    
    if ($cupo_maximo < 1 || $cupo_maximo > 200) {
        responder(['exito' => false, 'mensaje' => 'El cupo debe estar entre 1 y 200']);
    }

    if ($id_curso > 0) {
        // ===== ACTUALIZAR CURSO EXISTENTE =====
        $curso_existente = $conexion->consultar("SELECT id_curso, tabla_curso FROM cursos WHERE id_curso = ?", [$id_curso]);
        
        if (empty($curso_existente)) {
            responder(['exito' => false, 'mensaje' => 'El curso no existe']);
        }
        
        $curso_existente = $curso_existente[0]; // Obtener el primer resultado

        $sql = "UPDATE cursos 
                SET nombre_curso=?, edad_min=?, edad_max=?, cupo_maximo=?, horario=?, sala=?, instructor=?, activo=? 
                WHERE id_curso=?";
        $params = [$nombre_curso, $edad_min, $edad_max, $cupo_maximo, $horario, $sala, $instructor, $activo, $id_curso];
        
        $resultado = $conexion->ejecutar($sql, $params);
        
        if (!$resultado) {
            responder(['exito' => false, 'mensaje' => 'Error al actualizar el curso']);
        }

        // Crear tabla si no tiene asignada
        if (empty($curso_existente['tabla_curso'])) {
            $tabla_creada = crearTablaEspecifica($conexion, $id_curso, $nombre_curso);
        }

        responder(['exito' => true, 'mensaje' => 'Curso actualizado correctamente', 'modo' => 'actualizar']);
        
    } else {
        // ===== CREAR NUEVO CURSO =====
        $curso_existente = $conexion->consultar("SELECT id_curso FROM cursos WHERE nombre_curso = ?", [$nombre_curso]);
        if (!empty($curso_existente)) {
            responder(['exito' => false, 'mensaje' => 'Ya existe un curso con ese nombre']);
        }

        $sql = "INSERT INTO cursos (nombre_curso, edad_min, edad_max, cupo_maximo, horario, sala, instructor, activo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [$nombre_curso, $edad_min, $edad_max, $cupo_maximo, $horario, $sala, $instructor, $activo];

        $nuevo_id = $conexion->insertar($sql, $params);
        
        if ($nuevo_id <= 0) {
            responder(['exito' => false, 'mensaje' => 'No se pudo crear el curso']);
        }

        // Crear tabla específica
        $tabla_creada = crearTablaEspecifica($conexion, $nuevo_id, $nombre_curso);

        responder([
            'exito' => true,
            'mensaje' => 'Curso creado correctamente',
            'id_curso' => $nuevo_id,
            'tabla_creada' => $tabla_creada,
            'modo' => 'crear'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en guardar_curso.php: " . $e->getMessage());
    responder(['exito' => false, 'mensaje' => 'Error del servidor: ' . $e->getMessage()]);
}

/**
 * Crea tabla específica por curso usando la clase Conexion
 */
function crearTablaEspecifica($conexion, $id_curso, $nombre_curso = '') {
    $nombre_tabla = "curso_" . $id_curso;

    try {
        // Verificar si la tabla ya existe
        $tablas_existentes = $conexion->consultar("SHOW TABLES LIKE ?", [$nombre_tabla]);
        if (!empty($tablas_existentes)) {
            return true; // Ya existe
        }

        // Crear la tabla específica del curso
        $sql = "CREATE TABLE `$nombre_tabla` (
            `id_registro` INT AUTO_INCREMENT PRIMARY KEY,
            `id_usuario` INT NOT NULL,
            `nombre` VARCHAR(100) NOT NULL,
            `apellidos` VARCHAR(150) NOT NULL,
            `curp` VARCHAR(18) NOT NULL,
            `fecha_nacimiento` DATE NOT NULL,
            `edad` INT NOT NULL,
            `meses` INT DEFAULT 0,
            `salud` TEXT,
            `tutor` VARCHAR(200) NOT NULL,
            `numero_tutor` VARCHAR(10) NOT NULL,
            `numero_usuario` VARCHAR(10) DEFAULT '',
            `fecha_inscripcion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `activo` BOOLEAN DEFAULT TRUE,
            INDEX (`id_usuario`),
            INDEX (`curp`),
            INDEX (`fecha_inscripcion`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        // Usar la conexión directa para CREATE TABLE
        $pdo = $conexion->obtenerConexion();
        $pdo->exec($sql);

        // Actualizar campo tabla_curso
        $conexion->ejecutar("UPDATE cursos SET tabla_curso = ? WHERE id_curso = ?", [$nombre_tabla, $id_curso]);

        return true;

    } catch (Exception $e) {
        error_log("Error creando tabla específica $nombre_tabla: " . $e->getMessage());
        return false;
    }
}
?>
