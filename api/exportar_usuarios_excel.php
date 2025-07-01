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
    echo 'table { border-collapse: collapse; width: 100%; }';
    echo 'th, td { border: 1px solid #000; padding: 8px; text-align: left; }';
    echo 'th { background-color: #f2f2f2; font-weight: bold; }';
    echo '.header { text-align: center; margin-bottom: 20px; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    echo '<div class="header">';
    echo '<h1>Casa Telmex</h1>';
    echo '<h2>Lista de Usuarios</h2>';
    echo '<p>Fecha de exportación: ' . date('d/m/Y H:i:s') . '</p>';
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
                        COALESCE(horario, 'Por definir') as horario
                     FROM cursos 
                     WHERE $columnaPrimariaCursos = ?";
        
        $resultadoCurso = $conexion->consultar($sqlCurso, [$idCurso]);
        
        if (empty($resultadoCurso)) {
            echo '<p>Curso no encontrado</p>';
            echo '</body></html>';
            exit;
        }
        
        $curso = $resultadoCurso[0];
        
        echo '<h3>Curso: ' . htmlspecialchars($curso['nombre_curso']) . '</h3>';
        echo '<p><strong>Horario:</strong> ' . htmlspecialchars($curso['horario']) . '</p>';
        echo '<p><strong>Edad:</strong> ' . $curso['edad_min'] . '-' . $curso['edad_max'] . ' años</p>';
        
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
                            u.tutor,
                            COALESCE(u.numero_tutor, 'N/A') as numero_tutor,
                            COALESCE(u.numero_usuario, 'N/A') as numero_usuario,
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
                            tutor,
                            COALESCE(numero_tutor, 'N/A') as numero_tutor,
                            COALESCE(u.numero_usuario, 'N/A') as numero_usuario,
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
    echo '<tr>';
    echo '<th>No.</th>';
    echo '<th>ID</th>';
    echo '<th>Nombre Completo</th>';
    echo '<th>CURP</th>';
    echo '<th>Fecha Nacimiento</th>';
    echo '<th>Edad</th>';
    echo '<th>Meses</th>';
    echo '<th>Salud</th>';
    echo '<th>Tutor</th>';
    echo '<th>Teléfono Tutor</th>';
    echo '<th>Teléfono Usuario</th>';
    echo '<th>Fecha Registro</th>';
    if ($idCurso) {
        echo '<th>Fecha Inscripción</th>';
    }
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($usuarios as $index => $usuario) {
        echo '<tr>';
        echo '<td>' . ($index + 1) . '</td>';
        echo '<td>' . htmlspecialchars($usuario['id_usuario']) . '</td>';
        echo '<td>' . htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']) . '</td>';
        echo '<td>' . htmlspecialchars($usuario['curp']) . '</td>';
        
        // Formatear fecha de nacimiento
        $fechaNacimiento = '';
        if (!empty($usuario['fecha_nacimiento'])) {
            try {
                $fecha = new DateTime($usuario['fecha_nacimiento']);
                $fechaNacimiento = $fecha->format('d/m/Y');
            } catch (Exception $e) {
                $fechaNacimiento = $usuario['fecha_nacimiento'];
            }
        }
        echo '<td>' . htmlspecialchars($fechaNacimiento) . '</td>';
        
        echo '<td>' . htmlspecialchars($usuario['edad']) . ' años</td>';
        echo '<td>' . htmlspecialchars($usuario['meses']) . ' meses</td>';
        echo '<td>' . htmlspecialchars($usuario['salud']) . '</td>';
        echo '<td>' . htmlspecialchars($usuario['tutor']) . '</td>';
        echo '<td>' . htmlspecialchars($usuario['numero_tutor']) . '</td>';
        echo '<td>' . htmlspecialchars($usuario['numero_usuario']) . '</td>';
        
        // Formatear fecha de registro
        $fechaRegistro = '';
        if (!empty($usuario['fecha_registro'])) {
            try {
                $fecha = new DateTime($usuario['fecha_registro']);
                $fechaRegistro = $fecha->format('d/m/Y H:i');
            } catch (Exception $e) {
                $fechaRegistro = $usuario['fecha_registro'];
            }
        }
        echo '<td>' . htmlspecialchars($fechaRegistro) . '</td>';
        
        if ($idCurso) {
            // Formatear fecha de inscripción
            $fechaInscripcion = '';
            if (!empty($usuario['fecha_inscripcion'])) {
                try {
                    $fecha = new DateTime($usuario['fecha_inscripcion']);
                    $fechaInscripcion = $fecha->format('d/m/Y H:i');
                } catch (Exception $e) {
                    $fechaInscripcion = $usuario['fecha_inscripcion'];
                }
            }
            echo '<td>' . htmlspecialchars($fechaInscripcion) . '</td>';
        }
        
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
