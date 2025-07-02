<?php
/**
 * API para obtener todos los usuarios con información completa
 * Incluye cálculo correcto de documentación completa
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/conexion.php';
require_once '../config/configuracion.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $conexion = obtenerConexion();
    
    // Verificar estructura de la tabla usuarios
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
    
    // Construir campos SELECT dinámicamente
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
    
    // Campos de documentación
    $camposDocumentacion = [
        'doc_fotografias',
        'doc_acta_nacimiento', 
        'doc_curp',
        'doc_comprobante_domicilio',
        'doc_ine',
        'doc_cedula_afiliacion',
        'doc_fotos_tutores',
        'doc_ines_tutores',
        'es_derechohabiente'
    ];
    
    // Agregar campos que existen
    foreach ($camposDocumentacion as $campo) {
        if (in_array($campo, $camposDisponibles)) {
            $camposSelect[] = $campo;
        }
    }
    
    // Filtrar campos que realmente existen
    $camposSelectFiltrados = [];
    foreach ($camposSelect as $campo) {
        if (strpos($campo, ' as ') !== false) {
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
    
    $sql = "SELECT " . implode(', ', $camposSelectFiltrados) . " FROM usuarios ORDER BY fecha_registro DESC";
    
    $usuarios = $conexion->consultar($sql);
    
    // Procesar cada usuario para calcular documentación
    foreach ($usuarios as &$usuario) {
        // Formatear fecha de nacimiento
        if (isset($usuario['fecha_nacimiento']) && $usuario['fecha_nacimiento']) {
            try {
                $fecha = new DateTime($usuario['fecha_nacimiento']);
                $usuario['fecha_nacimiento_formateada'] = $fecha->format('d/m/Y');
            } catch (Exception $e) {
                $usuario['fecha_nacimiento_formateada'] = 'Fecha inválida';
            }
        } else {
            $usuario['fecha_nacimiento_formateada'] = 'Sin fecha';
        }
        
        // Formatear fecha de registro
        if (isset($usuario['fecha_registro']) && $usuario['fecha_registro']) {
            try {
                $fecha = new DateTime($usuario['fecha_registro']);
                $usuario['fecha_registro_formateada'] = $fecha->format('d/m/Y H:i');
            } catch (Exception $e) {
                $usuario['fecha_registro_formateada'] = 'Fecha inválida';
            }
        } else {
            $usuario['fecha_registro_formateada'] = 'Sin fecha';
        }
        
        // **CÁLCULO CORREGIDO DE DOCUMENTACIÓN**
        $documentosRequeridos = [
            'doc_fotografias',
            'doc_acta_nacimiento', 
            'doc_curp',
            'doc_comprobante_domicilio',
            'doc_ine',
            'doc_fotos_tutores',
            'doc_ines_tutores'
        ];
        
        // Si es derechohabiente, agregar cédula de afiliación
        $esDerechohabiente = isset($usuario['es_derechohabiente']) ? (int)$usuario['es_derechohabiente'] : 0;
        if ($esDerechohabiente) {
            $documentosRequeridos[] = 'doc_cedula_afiliacion';
        }
        
        $documentosCompletos = 0;
        $totalRequeridos = 0;
        
        foreach ($documentosRequeridos as $doc) {
            if (in_array($doc, $camposDisponibles)) {
                $totalRequeridos++;
                $valor = isset($usuario[$doc]) ? (int)$usuario[$doc] : 0;
                if ($valor == 1) {
                    $documentosCompletos++;
                }
            }
        }
        
        // Calcular porcentaje y estado
        $porcentajeCompleto = $totalRequeridos > 0 ? round(($documentosCompletos / $totalRequeridos) * 100) : 0;
        $documentacionCompleta = ($documentosCompletos == $totalRequeridos && $totalRequeridos > 0);
        
        $usuario['documentacion_completa'] = $documentacionCompleta;
        $usuario['documentacion_porcentaje'] = $porcentajeCompleto;
        $usuario['documentos_completos'] = $documentosCompletos;
        $usuario['documentos_requeridos'] = $totalRequeridos;
        $usuario['es_derechohabiente_texto'] = $esDerechohabiente ? 'Sí' : 'No';
        
        // Estado de documentación para mostrar
        if ($documentacionCompleta) {
            $usuario['documentacion_estado'] = 'COMPLETA';
            $usuario['documentacion_clase'] = 'completa';
        } elseif ($porcentajeCompleto >= 50) {
            $usuario['documentacion_estado'] = 'PARCIAL';
            $usuario['documentacion_clase'] = 'parcial';
        } else {
            $usuario['documentacion_estado'] = 'INCOMPLETA';
            $usuario['documentacion_clase'] = 'incompleta';
        }
    }
    
    echo json_encode([
        'exito' => true,
        'usuarios' => $usuarios,
        'total' => count($usuarios),
        'campos_disponibles' => $camposDisponibles
    ]);
    
} catch (Exception $e) {
    error_log("Error en obtener_usuarios.php: " . $e->getMessage());
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al obtener usuarios: ' . $e->getMessage(),
        'usuarios' => []
    ]);
}
?>
