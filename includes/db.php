<?php
/*
 * DB.PHP - Capa de abstracción de base de datos
 * 
 * Propósito: Este archivo centraliza toda la conexión y operaciones con la base de datos
 * Es como el "puente" entre nuestro código PHP y MariaDB
 * 
 * 
 * Funciones disponibles:
 * - getDBConnection(): Crea la conexión a la BD
 * - query(): Ejecuta cualquier query con parámetros seguros
 * - fetchAll(): Obtiene múltiples filas (ej: lista de productos)
 * - fetchOne(): Obtiene solo una fila (ej: datos de un producto específico)
 * - execute(): Para INSERT, UPDATE, DELETE (retorna filas afectadas)
 * - lastInsertId(): Obtiene el ID del último registro insertado
 */

// Configuración de conexión a la base de datos - NOTE: cambia a las credenciales de tu base de datos
define('DB_HOST', 'localhost');           // Servidor de BD
define('DB_USER', 'notsy');               // Usuario de la BD
define('DB_PASS', 'cjnsd2829');           // Contraseña del usuario
define('DB_NAME', 'tienda_db');           // Nombre de la base de datos
define('DB_CHARSET', 'utf8mb4');

// Función que crea y retorna la conexión a la base de datos
function getDBConnection() {
    try {

        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

        // Opciones de PDO para hacerlo más seguro y fácil de usar
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // Lanza excepciones en errores
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // Retorna arrays asociativos
            PDO::ATTR_EMULATE_PREPARES => false,                // Usa prepared statements reales
        ];


        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;

    } catch (PDOException $e) {
        // Si falla la conexión, registra el error y muestra mensaje genérico
        error_log("Database Connection Error: " . $e->getMessage());
        die("Error de conexión a la base de datos");
    }
}

// Ejecuta un query SQL
function query($sql, $params = []) {
    $pdo = getDBConnection();              // Obtiene la conexión
    $stmt = $pdo->prepare($sql);           // Prepara el SQL (evita SQL injection)
    $stmt->execute($params);               // Ejecuta con los parámetros
    return $stmt;                          // Retorna el statement
}

// Obtiene TODAS las filas que coincidan con el query
function fetchAll($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetchAll();             // Retorna todos los resultados
}

// Obtiene solo UNA fila 
function fetchOne($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetch();                // Retorna solo la primera fila
}

// Ejecuta queries de modificación (INSERT, UPDATE, DELETE)
function execute($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->rowCount();             // Retorna cuántas filas se modificaron
}

// Obtiene el ID del último registro insertado
function lastInsertId($pdo = null) {
    if ($pdo === null) {
        $pdo = getDBConnection();
    }
    return $pdo->lastInsertId();
}
?>
