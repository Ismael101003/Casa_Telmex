<?php
/**
 * Versión absolutamente mínima para guardar curso
 */

// Solo headers básicos
header('Content-Type: application/json');

// Función de respuesta simple
function responder($data) {
    echo json_encode($data);
    exit;
}

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(['error' => 'Solo POST permitido']);
}

// Verificar que tenemos datos
if (empty($_POST)) {
    responder(['error' => 'No hay datos POST']);
}

try {
    // Conexión directa sin archivos externos
    $host = 'localhost';
    $dbname = 'casatelmex';
    $username = 'root';
    $password = '';
    
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Obtener datos básicos
    $id_curso = (int)($_POST['id_curso'] ?? 0);
    $nombre = trim($_POST['nombre_curso'] ?? '');
    $edad_min = (int)($_POST['edad_min'] ?? 0);
    $edad_max = (int)($_POST['edad_max'] ?? 100);
    $cupo = (int)($_POST['cupo_maximo'] ?? 30);
    $horario = trim($_POST['horario'] ?? '');
    
    // Validación mínima
    if (empty($nombre)) {
        responder(['error' => 'Nombre del curso requerido']);
    }
    
    if ($id_curso > 0) {
        // ACTUALIZAR CURSO EXISTENTE
        $sql = "UPDATE cursos SET nombre_curso = ?, edad_min = ?, edad_max = ?, cupo_maximo = ?, horario = ? WHERE id_curso = ?";
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([$nombre, $edad_min, $edad_max, $cupo, $horario, $id_curso]);
        
        if ($resultado) {
            responder([
                'exito' => true,
                'mensaje' => 'Curso actualizado correctamente',
                'id_curso' => $id_curso,
                'accion' => 'actualizar'
            ]);
        } else {
            responder(['error' => 'No se pudo actualizar el curso']);
        }
        
    } else {
        // CREAR NUEVO CURSO
        $sql = "INSERT INTO cursos (nombre_curso, edad_min, edad_max, cupo_maximo, horario) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([$nombre, $edad_min, $edad_max, $cupo, $horario]);
        
        if ($resultado) {
            $nuevo_id = $pdo->lastInsertId();
            
            responder([
                'exito' => true,
                'mensaje' => 'Curso creado correctamente',
                'id_curso' => $nuevo_id,
                'accion' => 'crear'
            ]);
        } else {
            responder(['error' => 'No se pudo crear el curso']);
        }
    }
    
} catch (PDOException $e) {
    responder([
        'error' => 'Error de base de datos',
        'mensaje' => $e->getMessage(),
        'codigo' => $e->getCode()
    ]);
} catch (Exception $e) {
    responder([
        'error' => 'Error general',
        'mensaje' => $e->getMessage()
    ]);
}
?>
