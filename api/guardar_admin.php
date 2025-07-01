<?php
/**
 * API para guardar administradores
 */

require_once '../config/conexion.php';
require_once '../config/configuracion.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

try {
    $conexion = obtenerConexion();
    
    $id_admin = (int)($_POST['id_admin'] ?? 0);
    $usuario = limpiarDatos($_POST['usuario'] ?? '');
    $nombre = limpiarDatos($_POST['nombre'] ?? '');
    $email = limpiarDatos($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validaciones
    $errores = [];
    
    if (empty($usuario)) $errores[] = "El usuario es obligatorio";
    if (empty($nombre)) $errores[] = "El nombre es obligatorio";
    if (empty($email)) $errores[] = "El email es obligatorio";
    if ($id_admin == 0 && empty($password)) $errores[] = "La contraseña es obligatoria";
    if (!empty($password) && strlen($password) < 6) $errores[] = "La contraseña debe tener al menos 6 caracteres";
    
    // Verificar email válido
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El email no es válido";
    }
    
    // Verificar usuario único
    $sqlVerificar = "SELECT COUNT(*) as total FROM admins WHERE usuario = ? AND id_admin != ?";
    $resultado = $conexion->consultar($sqlVerificar, [$usuario, $id_admin]);
    if ($resultado[0]['total'] > 0) {
        $errores[] = "Ya existe un administrador con este usuario";
    }
    
    // Verificar email único
    $sqlVerificarEmail = "SELECT COUNT(*) as total FROM admins WHERE email = ? AND id_admin != ?";
    $resultadoEmail = $conexion->consultar($sqlVerificarEmail, [$email, $id_admin]);
    if ($resultadoEmail[0]['total'] > 0) {
        $errores[] = "Ya existe un administrador con este email";
    }
    
    if (!empty($errores)) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Errores de validación',
            'errores' => $errores
        ]);
        exit;
    }
    
    if ($id_admin > 0) {
        // Actualizar administrador existente
        if (!empty($password)) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE admins SET usuario = ?, nombre = ?, email = ?, password = ? WHERE id_admin = ?";
            $params = [$usuario, $nombre, $email, $passwordHash, $id_admin];
        } else {
            $sql = "UPDATE admins SET usuario = ?, nombre = ?, email = ? WHERE id_admin = ?";
            $params = [$usuario, $nombre, $email, $id_admin];
        }
        
        $conexion->ejecutar($sql, $params);
        
        echo json_encode([
            'exito' => true,
            'mensaje' => 'Administrador actualizado correctamente'
        ]);
    } else {
        // Crear nuevo administrador
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO admins (usuario, nombre, email, password, fecha_creacion) VALUES (?, ?, ?, ?, NOW())";
        
        $resultado = $conexion->ejecutar($sql, [$usuario, $nombre, $email, $passwordHash]);
        
        echo json_encode([
            'exito' => true,
            'mensaje' => 'Administrador creado correctamente',
            'id_admin' => $resultado['ultimo_id']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error al guardar administrador: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error interno del servidor'
    ]);
}
?>
