<?php
/**
 * Clase para manejo de conexión a base de datos MySQL
 */

class Conexion {
    private $host;
    private $usuario;
    private $password;
    private $baseDatos;
    private $conexion;
    private $charset;
    
    public function __construct($host = 'localhost', $usuario = 'root', $password = '', $baseDatos = 'casatelmex', $charset = 'utf8mb4') {
        $this->host = $host;
        $this->usuario = $usuario;
        $this->password = $password;
        $this->baseDatos = $baseDatos;
        $this->charset = $charset;
        $this->conectar();
    }
    
    private function conectar() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->baseDatos};charset={$this->charset}";
            $opciones = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
            ];
            
            $this->conexion = new PDO($dsn, $this->usuario, $this->password, $opciones);
            
        } catch (PDOException $e) {
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }
    
    public function consultar($sql, $parametros = []) {
        try {
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute($parametros);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en consulta SQL: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("Error en consulta: " . $e->getMessage());
        }
    }
    
    public function ejecutar($sql, $parametros = []) {
        try {
            $stmt = $this->conexion->prepare($sql);
            $resultado = $stmt->execute($parametros);
            return $resultado;
        } catch (PDOException $e) {
            error_log("Error al ejecutar SQL: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("Error al ejecutar: " . $e->getMessage());
        }
    }
    
    public function insertar($sql, $parametros = []) {
        try {
            $stmt = $this->conexion->prepare($sql);
            $resultado = $stmt->execute($parametros);
            if ($resultado) {
                return $this->conexion->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error al insertar: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("Error al insertar: " . $e->getMessage());
        }
    }
    
    public function obtenerUltimoId() {
        return $this->conexion->lastInsertId();
    }
    
    public function iniciarTransaccion() {
        return $this->conexion->beginTransaction();
    }
    
    public function confirmarTransaccion() {
        return $this->conexion->commit();
    }
    
    public function cancelarTransaccion() {
        return $this->conexion->rollBack();
    }
    
    public function obtenerConexion() {
        return $this->conexion;
    }
    
    public function cerrarConexion() {
        $this->conexion = null;
    }
}

/**
 * Función global para obtener una instancia de conexión
 */
function obtenerConexion() {
    static $conexion = null;
    
    if ($conexion === null) {
        // Configuración de la base de datos
        $host = 'localhost';
        $usuario = 'root';
        $password = '';
        $baseDatos = 'casatelmex';
        
        $conexion = new Conexion($host, $usuario, $password, $baseDatos);
    }
    
    return $conexion;
}
?>
