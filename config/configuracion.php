<?php
/**
 * Archivo de configuración general del sistema
 */

// Configuración de la aplicación
define('APP_NAME', 'Casa Telmex - Sistema de Gestión');
define('APP_VERSION', '1.0.0');

// Configuración de base de datos (si se necesita aquí)
define('DB_CHARSET', 'utf8mb4');

// Configuración de logging
define('LOG_ENABLED', true);
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR

/**
 * Función para limpiar datos de entrada
 */
function limpiarDatos($dato) {
    if (is_array($dato)) {
        return array_map('limpiarDatos', $dato);
    }
    
    $dato = trim($dato);
    $dato = stripslashes($dato);
    $dato = htmlspecialchars($dato, ENT_QUOTES, 'UTF-8');
    
    return $dato;
}

/**
 * Función para validar CURP
 */
function validarCURP($curp) {
    if (strlen($curp) !== 18) {
        return false;
    }
    
    $patron = '/^[A-Z]{4}\d{6}[HM][A-Z]{5}[0-9A-Z]\d$/';
    return preg_match($patron, $curp);
}

/**
 * Función para registrar logs
 */
function registrarLog($mensaje, $tipo = 'INFO') {
    if (!LOG_ENABLED) {
        return;
    }
    
    $fecha = date('Y-m-d H:i:s');
    $logMessage = "[$fecha] [$tipo] $mensaje" . PHP_EOL;
    
    $logFile = __DIR__ . '/../logs/sistema.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Función para formatear fechas
 */
function formatearFecha($fecha, $formato = 'd/m/Y') {
    if (empty($fecha)) {
        return 'Sin fecha';
    }
    
    try {
        $fechaObj = new DateTime($fecha);
        return $fechaObj->format($formato);
    } catch (Exception $e) {
        return 'Fecha inválida';
    }
}

/**
 * Función para generar respuesta JSON estándar
 */
function respuestaJSON($exito, $mensaje, $datos = null) {
    $respuesta = [
        'exito' => $exito,
        'mensaje' => $mensaje,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($datos !== null) {
        $respuesta['datos'] = $datos;
    }
    
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
}

/**
 * Función para validar email
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Función para generar contraseña segura
 */
function generarPassword($longitud = 8) {
    $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    
    for ($i = 0; $i < $longitud; $i++) {
        $password .= $caracteres[rand(0, strlen($caracteres) - 1)];
    }
    
    return $password;
}

/**
 * Función para validar edad
 */
function validarEdad($edad, $min = 0, $max = 120) {
    $edad = intval($edad);
    return $edad >= $min && $edad <= $max;
}

/**
 * Función para limpiar número de teléfono
 */
function limpiarTelefono($telefono) {
    // Remover todo excepto números
    $telefono = preg_replace('/[^0-9]/', '', $telefono);
    
    // Formatear si es necesario
    if (strlen($telefono) === 10) {
        return $telefono;
    }
    
    return $telefono;
}

/**
 * Función para validar CURP más estricta
 */
function validarCURPCompleto($curp) {
    if (!validarCURP($curp)) {
        return false;
    }
    
    // Validaciones adicionales si se necesitan
    $estados = [
        'AS', 'BC', 'BS', 'CC', 'CL', 'CM', 'CS', 'CH', 'DF', 'DG',
        'GT', 'GR', 'HG', 'JC', 'MC', 'MN', 'MS', 'NT', 'NL', 'OC',
        'PL', 'QT', 'QR', 'SP', 'SL', 'SR', 'TC', 'TS', 'TL', 'VZ',
        'YN', 'ZS', 'NE'
    ];
    
    $estadoCURP = substr($curp, 11, 2);
    return in_array($estadoCURP, $estados);
}

// Configurar zona horaria
date_default_timezone_set('America/Mexico_City');

// Configurar manejo de errores para producción
if (!defined('DEVELOPMENT_MODE')) {
    define('DEVELOPMENT_MODE', false);
}

if (!DEVELOPMENT_MODE) {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}
?>
