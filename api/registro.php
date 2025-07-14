<?php
/**
 * API para registrar nuevos usuarios con múltiples cursos y validación de cupos
 */

require_once '../config/conexion.php';
require_once '../config/configuracion.php';

// Habilitar CORS y headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Deshabilitar display de errores para evitar HTML en respuesta JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Log para debugging
registrarLog("=== INICIO API REGISTRO ===");
registrarLog("Método: " . $_SERVER['REQUEST_METHOD']);

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
    
    // Log de datos recibidos para debugging
    registrarLog("Datos POST recibidos: " . json_encode($_POST));
    
    // Recopilar datos del formulario
    $datos = [
        'nombre' => limpiarDatos($_POST['nombre'] ?? ''),
        'apellidos' => limpiarDatos($_POST['apellidos'] ?? ''),
        'curp' => strtoupper(limpiarDatos($_POST['curp'] ?? '')),
        'fecha_nacimiento' => limpiarDatos($_POST['fecha_nacimiento'] ?? ''),
        'edad' => intval($_POST['edad'] ?? 0),
        'meses' => intval($_POST['meses'] ?? 0),
        'salud' => limpiarDatos($_POST['salud'] ?? ''),
        'tutor' => limpiarDatos($_POST['tutor'] ?? ''),
        'numero_tutor' => limpiarDatos($_POST['numero_tutor'] ?? ''),
        'numero_usuario' => limpiarDatos($_POST['numero_usuario'] ?? '')
    ];
    
    registrarLog("Datos procesados: " . json_encode($datos));
    
    // Validar datos obligatorios
    $camposObligatorios = ['nombre', 'apellidos', 'curp', 'fecha_nacimiento'];
    foreach ($camposObligatorios as $campo) {
        if (empty($datos[$campo])) {
            registrarLog("Campo obligatorio faltante: $campo", 'ERROR');
            throw new Exception("El campo '$campo' es obligatorio");
        }
    }
    
    // Validar CURP
    if (!validarCURP($datos['curp'])) {
        throw new Exception("El CURP no tiene un formato válido");
    }
    
    // Obtener cursos seleccionados
    $cursosSeleccionados = $_POST['cursos'] ?? [];
    if (empty($cursosSeleccionados)) {
        throw new Exception("Debe seleccionar al menos un curso");
    }
    
    registrarLog("Cursos seleccionados: " . json_encode($cursosSeleccionados));
    
    // Determinar la estructura de las tablas
    $estructuraTablas = analizarEstructuraTablas($conexion);
    
    // Verificar si es usuario existente
    $usuarioExistente = isset($_POST['actualizar_usuario']) && $_POST['actualizar_usuario'] === '1';
    $usuarioId = null;
    
    if ($usuarioExistente && isset($_POST['id_usuario_existente'])) {
        $usuarioId = intval($_POST['id_usuario_existente']);
        
        // Verificar que el usuario existe
        $columnaPrimaria = $estructuraTablas['usuarios']['primaria'];
        $sqlVerificar = "SELECT $columnaPrimaria FROM usuarios WHERE $columnaPrimaria = ? LIMIT 1";
        $resultado = $conexion->consultar($sqlVerificar, [$usuarioId]);
        
        if (empty($resultado)) {
            throw new Exception("El usuario especificado no existe");
        }
        
        registrarLog("Usuario existente seleccionado: ID $usuarioId");
        
    } else {
        // Verificar si ya existe un usuario con el mismo CURP
        $sqlExiste = "SELECT {$estructuraTablas['usuarios']['primaria']} FROM usuarios WHERE curp = ? LIMIT 1";
        $resultado = $conexion->consultar($sqlExiste, [$datos['curp']]);
        
        if (!empty($resultado)) {
            throw new Exception("Ya existe un usuario registrado con este CURP");
        }
        
        // Insertar nuevo usuario
        $usuarioId = insertarNuevoUsuario($conexion, $datos, $estructuraTablas);
        registrarLog("Nuevo usuario registrado: ID $usuarioId, CURP: {$datos['curp']}");
    }
    
    // Inscribir en cursos
    $cursosInscritos = [];
    $erroresCursos = [];
    
    foreach ($cursosSeleccionados as $cursoId) {
        try {
            $cursoId = intval($cursoId);
            
            // Verificar si ya está inscrito en este curso
            $campoUsuario = $estructuraTablas['inscripciones']['usuario'];
            $campoCurso = $estructuraTablas['inscripciones']['curso'];
            $columnaPrimariaInscripciones = $estructuraTablas['inscripciones']['primaria'];
            
            $sqlYaInscrito = "SELECT $columnaPrimariaInscripciones FROM inscripciones WHERE $campoUsuario = ? AND $campoCurso = ? LIMIT 1";
            $yaInscrito = $conexion->consultar($sqlYaInscrito, [$usuarioId, $cursoId]);
            
            if (!empty($yaInscrito)) {
                $erroresCursos[] = "Ya está inscrito en el curso ID $cursoId";
                continue;
            }
            
            // Inscribir en curso
            inscribirEnCurso($conexion, $usuarioId, $cursoId, $datos, $estructuraTablas);
            $cursosInscritos[] = $cursoId;
            
        } catch (Exception $e) {
            $erroresCursos[] = "Error en curso $cursoId: " . $e->getMessage();
            registrarLog("Error inscribiendo en curso $cursoId: " . $e->getMessage(), 'ERROR');
        }
    }
    
    // Verificar si se inscribió en al menos un curso
    if (empty($cursosInscritos)) {
        throw new Exception("No se pudo inscribir al usuario en ningún curso. Errores: " . implode(', ', $erroresCursos));
    }
    
    // Preparar respuesta
    $mensaje = $usuarioExistente ? 
        "Usuario inscrito exitosamente en " . count($cursosInscritos) . " curso(s)" :
        "Usuario registrado e inscrito exitosamente en " . count($cursosInscritos) . " curso(s)";
    
    if (!empty($erroresCursos)) {
        $mensaje .= ". Advertencias: " . implode(', ', $erroresCursos);
    }
    
    echo json_encode([
        'exito' => true,
        'mensaje' => $mensaje,
        'usuario_id' => $usuarioId,
        'total_cursos' => count($cursosInscritos),
        'cursos_inscritos' => $cursosInscritos,
        'errores_cursos' => $erroresCursos,
        'es_usuario_existente' => $usuarioExistente
    ]);
    
} catch (Exception $e) {
    registrarLog("Error en registro: " . $e->getMessage(), 'ERROR');
    echo json_encode([
        'exito' => false,
        'mensaje' => $e->getMessage()
    ]);
}

function analizarEstructuraTablas($conexion) {
    $estructura = [];
    
    // Analizar tabla usuarios
    $sqlUsuarios = "SHOW COLUMNS FROM usuarios";
    $columnasUsuarios = $conexion->consultar($sqlUsuarios);
    
    $estructura['usuarios'] = [
        'primaria' => 'id_usuario', // Por defecto
        'campos' => []
    ];
    
    foreach ($columnasUsuarios as $columna) {
        $estructura['usuarios']['campos'][] = $columna['Field'];
        if ($columna['Key'] === 'PRI') {
            $estructura['usuarios']['primaria'] = $columna['Field'];
        }
    }
    
    // Analizar tabla inscripciones
    $sqlInscripciones = "SHOW COLUMNS FROM inscripciones";
    $columnasInscripciones = $conexion->consultar($sqlInscripciones);
    
    $estructura['inscripciones'] = [
        'primaria' => 'id',
        'usuario' => 'id_usuario',
        'curso' => 'id_curso',
        'campos' => []
    ];
    
    foreach ($columnasInscripciones as $columna) {
        $campo = $columna['Field'];
        $estructura['inscripciones']['campos'][] = $campo;
        
        if ($columna['Key'] === 'PRI') {
            $estructura['inscripciones']['primaria'] = $campo;
        }
        
        // Detectar campo de usuario
        if (in_array($campo, ['usuario_id', 'id_usuario'])) {
            $estructura['inscripciones']['usuario'] = $campo;
        }
        
        // Detectar campo de curso
        if (in_array($campo, ['curso_id', 'id_curso'])) {
            $estructura['inscripciones']['curso'] = $campo;
        }
    }
    
    // Analizar tabla cursos
    $sqlCursos = "SHOW COLUMNS FROM cursos";
    $columnasCursos = $conexion->consultar($sqlCursos);
    
    $estructura['cursos'] = [
        'primaria' => 'id_curso',
        'campos' => []
    ];
    
    foreach ($columnasCursos as $columna) {
        $estructura['cursos']['campos'][] = $columna['Field'];
        if ($columna['Key'] === 'PRI') {
            $estructura['cursos']['primaria'] = $columna['Field'];
        }
    }
    
    registrarLog("Estructura de tablas detectada: " . json_encode($estructura));
    
    return $estructura;
}

function insertarNuevoUsuario($conexion, $datos, $estructuraTablas) {
    $camposDisponibles = $estructuraTablas['usuarios']['campos'];
    $columnaPrimaria = $estructuraTablas['usuarios']['primaria'];
    
    registrarLog("Campos disponibles en tabla usuarios: " . implode(', ', $camposDisponibles));
    registrarLog("Columna primaria detectada: " . $columnaPrimaria);
    
    // Construir INSERT dinámicamente
    $camposInsertar = [];
    $valoresInsertar = [];
    $parametros = [];
    
    $mapeosCampos = [
        'nombre' => 'nombre',
        'apellidos' => 'apellidos', 
        'curp' => 'curp',
        'fecha_nacimiento' => 'fecha_nacimiento',
        'edad' => 'edad',
        'meses' => 'meses',
        'salud' => 'salud',
        'tutor' => 'tutor',
        'numero_tutor' => 'numero_tutor',
        'numero_usuario' => 'numero_usuario'
    ];
    
    foreach ($mapeosCampos as $clave => $campo) {
        if (in_array($campo, $camposDisponibles) && isset($datos[$clave]) && $datos[$clave] !== '') {
            $camposInsertar[] = $campo;
            $valoresInsertar[] = '?';
            $parametros[] = $datos[$clave];
            registrarLog("Agregando campo $campo con valor: " . $datos[$clave]);
        }
    }
    
    // Agregar fecha de registro si el campo existe
    if (in_array('fecha_registro', $camposDisponibles)) {
        $camposInsertar[] = 'fecha_registro';
        $valoresInsertar[] = 'NOW()';
    }
    
    if (empty($camposInsertar)) {
        throw new Exception("No hay campos válidos para insertar");
    }
    
    $sql = "INSERT INTO usuarios (" . implode(', ', $camposInsertar) . ") 
            VALUES (" . implode(', ', $valoresInsertar) . ")";
    
    registrarLog("SQL INSERT: " . $sql);
    registrarLog("Parámetros: " . json_encode($parametros));
    
    $resultado = $conexion->ejecutar($sql, $parametros);
    
    if (!$resultado) {
        throw new Exception("Error al insertar usuario en la base de datos");
    }
    
    return $conexion->obtenerUltimoId();
}

function inscribirEnCurso($conexion, $usuarioId, $cursoId, $datosUsuario, $estructuraTablas) {
    // Verificar que el curso existe antes de inscribir
    $columnaPrimariaCursos = $estructuraTablas['cursos']['primaria'];
    $sqlVerificarCurso = "SELECT $columnaPrimariaCursos, nombre_curso FROM cursos WHERE $columnaPrimariaCursos = ? LIMIT 1";
    $cursoExiste = $conexion->consultar($sqlVerificarCurso, [$cursoId]);
    
    if (empty($cursoExiste)) {
        throw new Exception("El curso ID $cursoId no existe");
    }
    
    $camposInscripciones = $estructuraTablas['inscripciones']['campos'];
    $campoUsuario = $estructuraTablas['inscripciones']['usuario'];
    $campoCurso = $estructuraTablas['inscripciones']['curso'];
    
    registrarLog("Campos disponibles en tabla inscripciones: " . implode(', ', $camposInscripciones));
    registrarLog("Campo usuario: $campoUsuario, Campo curso: $campoCurso");
    
    // Construir INSERT para inscripciones dinámicamente
    $camposInsertar = [];
    $valoresInsertar = [];
    $parametros = [];
    
    // Agregar campo de usuario
    if (in_array($campoUsuario, $camposInscripciones)) {
        $camposInsertar[] = $campoUsuario;
        $valoresInsertar[] = '?';
        $parametros[] = $usuarioId;
    }
    
    // Agregar campo de curso
    if (in_array($campoCurso, $camposInscripciones)) {
        $camposInsertar[] = $campoCurso;
        $valoresInsertar[] = '?';
        $parametros[] = $cursoId;
    }
    
    // Agregar fecha de inscripción si existe
    if (in_array('fecha_inscripcion', $camposInscripciones)) {
        $camposInsertar[] = 'fecha_inscripcion';
        $valoresInsertar[] = 'NOW()';
    }
    
    if (empty($camposInsertar)) {
        throw new Exception("No se pudieron mapear los campos para la tabla inscripciones");
    }
    
    $sqlInscripcion = "INSERT INTO inscripciones (" . implode(', ', $camposInsertar) . ") 
                      VALUES (" . implode(', ', $valoresInsertar) . ")";
    
    registrarLog("SQL Inscripción: " . $sqlInscripcion);
    registrarLog("Parámetros inscripción: " . json_encode($parametros));
    
    $resultadoInscripcion = $conexion->ejecutar($sqlInscripcion, $parametros);
    
    if (!$resultadoInscripcion) {
        throw new Exception("Error al insertar en tabla inscripciones");
    }
    
    // Verificar si existe tabla específica del curso
    $nombreTablaCurso = "curso_$cursoId";
    $sqlVerificarTabla = "SHOW TABLES LIKE '$nombreTablaCurso'";
    $tablaExiste = $conexion->consultar($sqlVerificarTabla);
    
    if (!empty($tablaExiste)) {
        // Insertar en tabla específica del curso
        try {
            // Verificar estructura de la tabla específica del curso
            $sqlColumnasTabla = "SHOW COLUMNS FROM $nombreTablaCurso";
            $columnasTabla = $conexion->consultar($sqlColumnasTabla);
            
            $camposDisponiblesTabla = [];
            foreach ($columnasTabla as $columna) {
                $camposDisponiblesTabla[] = $columna['Field'];
            }
            
            registrarLog("Campos disponibles en tabla $nombreTablaCurso: " . implode(', ', $camposDisponiblesTabla));
            
            // Construir INSERT dinámicamente para la tabla del curso
            $camposInsertar = [];
            $valoresInsertar = [];
            $parametros = [];
            
            // Mapeo de campos para la tabla del curso
            $mapeosCampos = [
                'usuario_id' => $usuarioId,
                'id_usuario' => $usuarioId,
                'nombre' => $datosUsuario['nombre'],
                'apellidos' => $datosUsuario['apellidos'],
                'curp' => $datosUsuario['curp'],
                'fecha_nacimiento' => $datosUsuario['fecha_nacimiento'],
                'edad' => $datosUsuario['edad'],
                'meses' => $datosUsuario['meses'],
                'salud' => $datosUsuario['salud'],
                'tutor' => $datosUsuario['tutor'],
                'numero_tutor' => $datosUsuario['numero_tutor']
            ];
            
            foreach ($mapeosCampos as $campo => $valor) {
                if (in_array($campo, $camposDisponiblesTabla) && $valor !== '') {
                    $camposInsertar[] = $campo;
                    $valoresInsertar[] = '?';
                    $parametros[] = $valor;
                }
            }
            
            // Agregar fecha de inscripción si el campo existe
            if (in_array('fecha_inscripcion', $camposDisponiblesTabla)) {
                $camposInsertar[] = 'fecha_inscripcion';
                $valoresInsertar[] = 'NOW()';
            }
            
            if (!empty($camposInsertar)) {
                $sqlCurso = "INSERT INTO $nombreTablaCurso (" . implode(', ', $camposInsertar) . ") 
                            VALUES (" . implode(', ', $valoresInsertar) . ")";
                
                registrarLog("SQL tabla curso: " . $sqlCurso);
                registrarLog("Parámetros tabla curso: " . json_encode($parametros));
                
                $conexion->ejecutar($sqlCurso, $parametros);
                registrarLog("Usuario $usuarioId inscrito en tabla específica $nombreTablaCurso");
            }
            
        } catch (Exception $e) {
            registrarLog("Error insertando en tabla $nombreTablaCurso: " . $e->getMessage(), 'ERROR');
            // No lanzar excepción aquí para no interrumpir el proceso principal
        }
    }
    
    registrarLog("Usuario $usuarioId inscrito exitosamente en curso $cursoId");
}
?>
