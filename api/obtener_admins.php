<?php
/**
 * API para obtener administradores
 */

require_once '../config/conexion.php';
require_once '../config/configuracion.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $conexion = obtenerConexion();
    
    $sql = "SELECT 
                id_admin,
                usuario,
                nombre,
                email,
                activo,
                ultimo_acceso,
                fecha_creacion
            FROM admins 
            ORDER BY fecha_creacion DESC";
    
    $admins = $conexion->consultar($sql);
    
    // Formatear fechas
    foreach ($admins as &$admin) {
        if ($admin['ultimo_acceso']) {
            $fecha = new DateTime($admin['ultimo_acceso']);
            $admin['ultimo_acceso'] = $fecha->format('d/m/Y H:i');
        }
        
        if ($admin['fecha_creacion']) {
            $fecha = new DateTime($admin['fecha_creacion']);
            $admin['fecha_creacion'] = $fecha->format('d/m/Y H:i');
        }
    }
    
    echo json_encode([
        'exito' => true,
        'admins' => $admins
    ]);
    
} catch (Exception $e) {
    error_log("Error al obtener administradores: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al obtener administradores'
    ]);
}
?>
