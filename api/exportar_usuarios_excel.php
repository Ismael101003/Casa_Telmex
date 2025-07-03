<?php
/**
 * API para exportar lista de usuarios de un curso a Excel
 */

require_once '../config/conexion.php';
require_once '../config/configuracion.php';

// Headers para Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="lista_usuarios_curso_' . ($_GET['id_curso'] ?? 'todos') . '_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

try {
    $conexion = obtenerConexion();
    $idCurso = $_GET['id_curso'] ?? null;
    
    // Detectar estructura de tablas
    $estructuraCursos = $conexion->consultar("DESCRIBE cursos");
    $estructuraUsuarios = $conexion->consultar("DESCRIBE usuarios");
    $estructuraInscripciones = $conexion->consultar("DESCRIBE inscripciones");
    
    // Determinar columnas primarias y campos
    $columnaPrimariaCursos = 'id_curso';
    $columnaPrimariaUsuarios = 'id_usuario';
    $campoUsuarioInscripciones = 'id_usuario';
    $campoCursoInscripciones = 'id_curso';
    $campoFechaInscripcion = 'fecha_inscripcion';
    
    foreach ($estructuraCursos as $col) {
        if ($col['Key'] === 'PRI') {
            $columnaPrimariaCursos = $col['Field'];
            break;
        }
    }
    
    foreach ($estructuraUsuarios as $col) {
        if ($col['Key'] === 'PRI') {
            $columnaPrimariaUsuarios = $col['Field'];
            break;
        }
    }
    
    $campoFechaInscripcion = null; // No asumimos que existe
    
    foreach ($estructuraInscripciones as $col) {
        if (in_array($col['Field'], ['usuario_id', 'id_usuario', 'user_id'])) {
            $campoUsuarioInscripciones = $col['Field'];
        }
        if (in_array($col['Field'], ['curso_id', 'id_curso', 'course_id'])) {
            $campoCursoInscripciones = $col['Field'];
        }
        // Verificar si existe algún campo de fecha en inscripciones
        if (in_array($col['Field'], ['fecha_inscripcion', 'created_at', 'fecha_registro', 'fecha_creacion'])) {
            $campoFechaInscripcion = $col['Field'];
        }
    }
    
    // Iniciar HTML para Excel
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<title>Lista de Usuarios - Casa Telmex</title>';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; margin: 20px; }';
    echo 'table { border-collapse: collapse; width: 100%; margin-top: 20px; }';
    echo 'th, td { border: 1px solid #000; padding: 8px; text-align: left; font-size: 10px; }';
    echo 'th { background-color: #f2f2f2; font-weight: bold; }';
    echo '.header { text-align: center; margin-bottom: 20px; position: relative; min-height: 100px; }';
    echo '.logo-container { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }';
    echo '.logo-left, .logo-right { width: 80px; height: 80px; }';
    echo '.official-header { text-align: center; }';
    echo '.official-header h1 { font-size: 12px; font-weight: bold; margin: 2px 0; }';
    echo '.official-header h2 { font-size: 11px; font-weight: bold; margin: 2px 0; }';
    echo '.official-header h3 { font-size: 10px; font-weight: bold; margin: 2px 0; }';
    echo '.course-info { margin: 20px 0; text-align: left; }';
    echo '.course-info table { border: 1px solid #000; margin: 0; }';
    echo '.course-info td { border: 1px solid #000; padding: 5px; font-size: 10px; font-weight: bold; }';
    echo '.group-header { background-color: #9966CC; color: white; text-align: center; font-weight: bold; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    echo '<div class="header">';
    echo '<div class="logo-container">';
    echo '<div class="logo-left">CASA TELMEX</div>';
    echo '<div class="logo-right">SEMAR</div>';
    echo '</div>';
    echo '<div class="official-header">';
    echo '<h1>DIRECCIÓN GENERAL ADJUNTA DE SEGURIDAD Y BIENESTAR SOCIAL</h1>';
    echo '<h2>CENTRO EDUCATIVO CUEMANCO</h2>';
    echo '<h2>DEPARTAMENTO DE HABILIDADES PEDAGOGICAS Y EDUCATIVAS</h2>';
    echo '<h3>CURSO DE VERANO 2025</h3>';
    echo '<h3>ACTIVIDAD ESPECIAL, 27 DE JUNIO 2025</h3>';
    echo '</div>';
    echo '</div>';
    
    if ($idCurso) {
        // Exportar usuarios de un curso específico
        
        // Obtener información del curso
        $sqlCurso = "SELECT 
                        $columnaPrimariaCursos as id_curso,
                        nombre_curso,
                        edad_min,
                        edad_max,
                        COALESCE(cupo_maximo, 30) as cupo_maximo,
                        COALESCE(horario, 'Por definir') as horario,
                        COALESCE(instructor, 'Sin asignar') as instructor,
                        COALESCE(sala, 'Sin asignar') as sala
                     FROM cursos 
                     WHERE $columnaPrimariaCursos = ?";
        
        $resultadoCurso = $conexion->consultar($sqlCurso, [$idCurso]);
        
        if (empty($resultadoCurso)) {
            echo '<p>Curso no encontrado</p>';
            echo '</body></html>';
            exit;
        }
        
        $curso = $resultadoCurso[0];
        
        echo '<div class="course-info">';
        echo '<table style="width: 100%;">';
        echo '<tr>';
        echo '<td style="width: 15%;"><strong>Instructor:</strong> ' . htmlspecialchars($curso['instructor']) . '</td>';
        echo '<td style="width: 25%;"><strong>Instructor de Apoyo:</strong> 1er. Mtre. Soto</td>';
        echo '<td style="width: 15%;"></td>';
        echo '<td style="width: 10%;"><strong>HORARIO</strong></td>';
        echo '<td style="width: 15%;">viernes 27-06-2025</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td colspan="3"></td>';
        echo '<td></td>';
        echo '<td>' . htmlspecialchars($curso['horario']) . '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td colspan="3"></td>';
        echo '<td><strong>EDAD:</strong></td>';
        echo '<td>' . $curso['edad_min'] . '-' . $curso['edad_max'] . ' AÑOS</td>';
        echo '</tr>';
        echo '</table>';
        echo '</div>';
        
        // Construir campo de fecha según disponibilidad
        $campoFechaSQL = $campoFechaInscripcion ? "i.$campoFechaInscripcion" : "u.fecha_registro";
        
        $sqlUsuarios = "SELECT 
                            u.$columnaPrimariaUsuarios as id_usuario,
                            u.nombre,
                            u.apellidos,
                            u.curp,
                            u.fecha_nacimiento,
                            u.edad,
                            COALESCE(u.meses, 0) as meses,
                            COALESCE(u.salud, '') as salud,
                            COALESCE(u.tutor, '') as tutor,
                            COALESCE(u.numero_tutor, '') as numero_tutor,
                            COALESCE(u.numero_usuario, '') as numero_usuario,
                            u.fecha_registro,
                            $campoFechaSQL as fecha_inscripcion
                        FROM usuarios u
                        INNER JOIN inscripciones i ON u.$columnaPrimariaUsuarios = i.$campoUsuarioInscripciones
                        WHERE i.$campoCursoInscripciones = ?
                        ORDER BY u.nombre, u.apellidos";
        
        $usuarios = $conexion->consultar($sqlUsuarios, [$idCurso]);
        
    } else {
        // Exportar todos los usuarios
        echo '<h3>Todos los Usuarios Registrados</h3>';
        
        $sqlUsuarios = "SELECT 
                            $columnaPrimariaUsuarios as id_usuario,
                            nombre,
                            apellidos,
                            curp,
                            fecha_nacimiento,
                            edad,
                            COALESCE(meses, 0) as meses,
                            COALESCE(salud, '') as salud,
                            COALESCE(tutor, '') as tutor,
                            COALESCE(numero_tutor, '') as numero_tutor,
                            COALESCE(numero_usuario, '') as numero_usuario,
                            fecha_registro,
                            NULL as fecha_inscripcion
                        FROM usuarios
                        ORDER BY nombre, apellidos";
        
        $usuarios = $conexion->consultar($sqlUsuarios);
    }
    
    echo '<p><strong>Total de usuarios:</strong> ' . count($usuarios) . '</p>';
    
    // Crear tabla
    echo '<table>';
    echo '<thead>';
    echo '<tr class="group-header">';
    echo '<td colspan="8" style="text-align: center; font-size: 12px;">GRUPO DELTA</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th>No.</th>';
    echo '<th>NOMBRE COMPLETO ALUMNO</th>';
    echo '<th>NOMBRE COMPLETO PADRE O TUTOR</th>';
    echo '<th>EDAD</th>';
    echo '<th>TELÉFONO</th>';
    echo '<th>PADECIMIENTO/ALERGIA</th>';
    echo '<th>ASISTENCIA</th>';
    echo '<th>DOCUMENTACIÓN</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($usuarios as $index => $usuario) {
        echo '<tr>';
        echo '<td>' . ($index + 1) . '</td>';
        echo '<td>' . strtoupper(htmlspecialchars(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''))) . '</td>';
        echo '<td>' . strtoupper(htmlspecialchars($usuario['tutor'] ?? '')) . '</td>';
        echo '<td>' . htmlspecialchars($usuario['edad'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($usuario['numero_tutor'] ?? '') . '</td>';
        echo '<td>' . strtoupper(htmlspecialchars($usuario['salud'] ?? '')) . '</td>';
        echo '<td></td>'; // Columna vacía para asistencia
        echo '<td></td>'; // Columna vacía para documentación
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    echo '<div style="margin-top: 30px; text-align: center; font-size: 12px;">';
    echo '<p>Casa Telmex - Sistema de Gestión de Cursos</p>';
    echo '<p>Exportado el ' . date('d/m/Y \a \l\a\s H:i:s') . '</p>';
    echo '</div>';
    
    echo '</body>';
    echo '</html>';
    
} catch (Exception $e) {
    error_log("Error en exportar_usuarios_excel: " . $e->getMessage());
    echo '<html><body>';
    echo '<h1>Error al exportar</h1>';
    echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</body></html>';
}
?>
