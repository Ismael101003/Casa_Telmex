<?php
/**
 * API para guardar cursos - VERSIÓN CORREGIDA PARA EVITAR MODIFICAR CURSOS EXISTENTES
 */

// Headers básicos
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Función de respuesta
function responder($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Manejar OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Solo POST
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
    $activo = (int)($_POST['activo'] ?? 1);
    
    // Log para debugging
    error_log("=== GUARDAR CURSO DEBUG ===");
    error_log("ID Curso recibido: " . $id_curso);
    error_log("Nombre: " . $nombre_curso);
    error_log("POST data: " . print_r($_POST, true));
    
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
    
    // Verificar si existe columna activo
    $columnas = $pdo->query("SHOW COLUMNS FROM cursos")->fetchAll();
    $tiene_activo = false;
    $tiene_tabla_curso = false;
    
    foreach ($columnas as $columna) {
        if ($columna['Field'] === 'activo') {
            $tiene_activo = true;
        }
        if ($columna['Field'] === 'tabla_curso') {
            $tiene_tabla_curso = true;
        }
    }
    
    // Agregar columnas si no existen
    if (!$tiene_activo) {
        try {
            $pdo->exec("ALTER TABLE cursos ADD COLUMN activo TINYINT(1) DEFAULT 1");
            $tiene_activo = true;
            error_log("Columna 'activo' agregada exitosamente");
        } catch (Exception $e) {
            error_log("Error agregando columna activo: " . $e->getMessage());
        }
    }
    
    if (!$tiene_tabla_curso) {
        try {
            $pdo->exec("ALTER TABLE cursos ADD COLUMN tabla_curso VARCHAR(100) DEFAULT NULL");
            $tiene_tabla_curso = true;
            error_log("Columna 'tabla_curso' agregada exitosamente");
        } catch (Exception $e) {
            error_log("Error agregando columna tabla_curso: " . $e->getMessage());
        }
    }
    
    // LÓGICA PRINCIPAL: DETERMINAR SI ES ACTUALIZACIÓN O CREACIÓN
    if ($id_curso > 0) {
        // ===== ACTUALIZAR CURSO EXISTENTE =====
        error_log("MODO: Actualizar curso existente ID: " . $id_curso);
        
        // Verificar que el curso existe
        $stmt = $pdo->prepare("SELECT id_curso, tabla_curso FROM cursos WHERE id_curso = ?");
        $stmt->execute([$id_curso]);
        $curso_existente = $stmt->fetch();
        
        if (!$curso_existente) {
            responder(['exito' => false, 'mensaje' => 'El curso no existe']);
        }
        
        // Preparar UPDATE
        if ($tiene_activo) {
            $sql = "UPDATE cursos SET nombre_curso=?, edad_min=?, edad_max=?, cupo_maximo=?, horario=?, activo=? WHERE id_curso=?";
            $params = [$nombre_curso, $edad_min, $edad_max, $cupo_maximo, $horario, $activo, $id_curso];
        } else {
            $sql = "UPDATE cursos SET nombre_curso=?, edad_min=?, edad_max=?, cupo_maximo=?, horario=? WHERE id_curso=?";
            $params = [$nombre_curso, $edad_min, $edad_max, $cupo_maximo, $horario, $id_curso];
        }
        
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute($params);
        
        if (!$resultado) {
            responder(['exito' => false, 'mensaje' => 'Error al actualizar el curso']);
        }
        
        // Verificar/crear tabla específica si no existe
        if (empty($curso_existente['tabla_curso'])) {
            $tabla_creada = crearTablaEspecifica($pdo, $id_curso, $nombre_curso);
            error_log("Tabla específica creada para curso existente: " . ($tabla_creada ? 'SÍ' : 'NO'));
        }
        
        error_log("Curso actualizado exitosamente");
        responder(['exito' => true, 'mensaje' => 'Curso actualizado correctamente', 'modo' => 'actualizar']);
        
    } else {
        // ===== CREAR NUEVO CURSO =====
        error_log("MODO: Crear nuevo curso");
        
        // Verificar que no existe un curso con el mismo nombre
        $stmt = $pdo->prepare("SELECT id_curso FROM cursos WHERE nombre_curso = ?");
        $stmt->execute([$nombre_curso]);
        $curso_duplicado = $stmt->fetch();
        
        if ($curso_duplicado) {
            responder(['exito' => false, 'mensaje' => 'Ya existe un curso con ese nombre']);
        }
        
        // Preparar INSERT
        if ($tiene_activo) {
            $sql = "INSERT INTO cursos (nombre_curso, edad_min, edad_max, cupo_maximo, horario, activo) VALUES (?, ?, ?, ?, ?, ?)";
            $params = [$nombre_curso, $edad_min, $edad_max, $cupo_maximo, $horario, $activo];
        } else {
            $sql = "INSERT INTO cursos (nombre_curso, edad_min, edad_max, cupo_maximo, horario) VALUES (?, ?, ?, ?, ?)";
            $params = [$nombre_curso, $edad_min, $edad_max, $cupo_maximo, $horario];
        }
        
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute($params);
        
        if (!$resultado) {
            responder(['exito' => false, 'mensaje' => 'Error al crear el curso']);
        }
        
        $nuevo_id = $pdo->lastInsertId();
        
        if ($nuevo_id <= 0) {
            responder(['exito' => false, 'mensaje' => 'No se pudo obtener el ID del nuevo curso']);
        }
        
        error_log("Nuevo curso creado con ID: " . $nuevo_id);
        
        // Crear tabla específica
        $tabla_creada = crearTablaEspecifica($pdo, $nuevo_id, $nombre_curso);
        
        error_log("Tabla específica creada: " . ($tabla_creada ? 'SÍ' : 'NO'));
        
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
 * Crear tabla específica del curso
 */
function crearTablaEspecifica($pdo, $id_curso, $nombre_curso = '') {
    $nombre_tabla = "curso_" . $id_curso;
    
    try {
        // Verificar si la tabla ya existe
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$nombre_tabla]);
        $tabla_existe = $stmt->fetch();
        
        if ($tabla_existe) {
            error_log("La tabla $nombre_tabla ya existe");
            return true;
        }
        
        // Crear la tabla específica del curso
        $sql_tabla = "CREATE TABLE `$nombre_tabla` (
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
            
            INDEX `idx_usuario` (`id_usuario`),
            INDEX `idx_curp` (`curp`),
            INDEX `idx_fecha_inscripcion` (`fecha_inscripcion`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql_tabla);
        
        // Actualizar campo tabla_curso en la tabla cursos
        $stmt = $pdo->prepare("UPDATE cursos SET tabla_curso = ? WHERE id_curso = ?");
        $stmt->execute([$nombre_tabla, $id_curso]);
        
        error_log("Tabla específica creada exitosamente: $nombre_tabla para curso ID: $id_curso");
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error creando tabla específica $nombre_tabla: " . $e->getMessage());
        return false;
    }
}
?>
