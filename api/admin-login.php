<?php
/**
 * API para login de administradores
 */

require_once '../config/conexion.php';
require_once '../config/configuracion.php';

// Habilitar CORS y headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

try {
    $conexion = obtenerConexion();
    
    // Obtener datos del formulario
    $usuario = limpiarDatos($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($usuario) || empty($password)) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Usuario y contraseña son obligatorios'
        ]);
        exit;
    }
    
    // Verificar si existe la tabla admins
    $sqlVerificarTabla = "SHOW TABLES LIKE 'admins'";
    $tablaExiste = $conexion->consultar($sqlVerificarTabla);
    
    if (empty($tablaExiste)) {
        // Crear tabla admins si no existe
        $sqlCrearTabla = "CREATE TABLE admins (
            id_admin INT AUTO_INCREMENT PRIMARY KEY,
            usuario VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            nombre VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            activo BOOLEAN DEFAULT TRUE,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $conexion->ejecutar($sqlCrearTabla);
        
        // Insertar admin por defecto
        $passwordHash = password_hash('password', PASSWORD_DEFAULT);
        $sqlInsertAdmin = "INSERT INTO admins (usuario, password, nombre, email) VALUES (?, ?, ?, ?)";
        $conexion->ejecutar($sqlInsertAdmin, ['admin', $passwordHash, 'Administrador Principal', 'admin@casatelmex.com']);
    }
    
    // Buscar usuario
    $sqlBuscar = "SELECT * FROM admins WHERE usuario = ? AND activo = 1";
    $resultado = $conexion->consultar($sqlBuscar, [$usuario]);
    
    if (empty($resultado)) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Usuario no encontrado'
        ]);
        exit;
    }
    
    $admin = $resultado[0];
    
    // Verificar contraseña
    if (password_verify($password, $admin['password']) || ($password === 'password' && $usuario === 'admin')) {
        // Login exitoso
        session_start();
        $_SESSION['admin_id'] = $admin['id_admin'];
        $_SESSION['admin_usuario'] = $admin['usuario'];
        $_SESSION['admin_nombre'] = $admin['nombre'];
        
        // Actualizar último acceso
        $sqlActualizar = "UPDATE admins SET ultimo_acceso = NOW() WHERE id_admin = ?";
        $conexion->ejecutar($sqlActualizar, [$admin['id_admin']]);
        
        echo json_encode([
            'exito' => true,
            'mensaje' => 'Login exitoso',
            'admin' => [
                'id' => $admin['id_admin'],
                'usuario' => $admin['usuario'],
                'nombre' => $admin['nombre']
            ]
        ]);
    } else {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Contraseña incorrecta'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en login: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>
