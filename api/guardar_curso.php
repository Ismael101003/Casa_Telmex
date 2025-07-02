<?php
/**
 * API para guardar cursos - VERSIÓN ACTUALIZADA CON SALA E INSTRUCTOR
 */

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
    // Conexión directa a la base de datos
    $host = 'localhost';
    $dbname = 'casatelmex';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
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
        $stmt = $pdo->prepare("SELECT id_curso, tabla_curso FROM cursos WHERE id_curso = ?");
        $stmt->execute([$id_curso]);
        $curso_existente = $stmt->fetch();
        
        if (!$curso_existente) {
            responder(['exito' => false, 'mensaje' => 'El curso no existe']);
        }

        $sql = "UPDATE cursos 
                SET nombre_curso=?, edad_min=?, edad_max=?, cupo_maximo=?, horario=?, sala=?, instructor=?, activo=? 
                WHERE id_curso=?";
        $params = [$nombre_curso, $edad_min, $edad_max, $cupo_maximo, $horario, $sala, $instructor, $activo, $id_curso];
        
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute($params);
        
        if (!$resultado) {
            responder(['exito' => false, 'mensaje' => 'Error al actualizar el curso']);
        }

        // Crear tabla si no tiene asignada
        if (empty($curso_existente['tabla_curso'])) {
            $tabla_creada = crearTablaEspecifica($pdo, $id_curso, $nombre_curso);
        }

        responder(['exito' => true, 'mensaje' => 'Curso actualizado correctamente', 'modo' => 'actualizar']);
        
    } else {
        // ===== CREAR NUEVO CURSO =====
        $stmt = $pdo->prepare("SELECT id_curso FROM cursos WHERE nombre_curso = ?");
        $stmt->execute([$nombre_curso]);
        if ($stmt->fetch()) {
            responder(['exito' => false, 'mensaje' => 'Ya existe un curso con ese nombre']);
        }

        $sql = "INSERT INTO cursos (nombre_curso, edad_min, edad_max, cupo_maximo, horario, sala, instructor, activo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [$nombre_curso, $edad_min, $edad_max, $cupo_maximo, $horario, $sala, $instructor, $activo];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $nuevo_id = $pdo->lastInsertId();
        if ($nuevo_id <= 0) {
            responder(['exito' => false, 'mensaje' => 'No se pudo obtener el ID del nuevo curso']);
        }

        // Crear tabla específica
        $tabla_creada = crearTablaEspecifica($pdo, $nuevo_id, $nombre_curso);

        responder([
            'exito' => true,
            'mensaje' => 'Curso creado correctamente',
            'id_curso' => $nuevo_id,
            'tabla_creada' => $tabla_creada,
            'modo' => 'crear'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Error BD en guardar_curso.php: " . $e->getMessage());
    responder(['exito' => false, 'mensaje' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Error general en guardar_curso.php: " . $e->getMessage());
    responder(['exito' => false, 'mensaje' => 'Error del servidor: ' . $e->getMessage()]);
}

/**
 * Crea tabla específica por curso
 */
function crearTablaEspecifica($pdo, $id_curso, $nombre_curso = '') {
    $nombre_tabla = "curso_" . $id_curso;

    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$nombre_tabla]);
        if ($stmt->fetch()) {
            return true; // Ya existe
        }

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
        
        $pdo->exec($sql);

        // Actualizar campo tabla_curso
        $stmt = $pdo->prepare("UPDATE cursos SET tabla_curso = ? WHERE id_curso = ?");
        $stmt->execute([$nombre_tabla, $id_curso]);

        return true;

    } catch (Exception $e) {
        error_log("Error creando tabla específica $nombre_tabla: " . $e->getMessage());
        return false;
    }
}
?>
