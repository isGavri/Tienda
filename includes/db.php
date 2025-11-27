<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'notsy');
define('DB_PASS', 'cjnsd2829');
define('DB_NAME', 'tienda_db');
define('DB_CHARSET', 'utf8mb4');

function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;

    } catch (PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        die("Error de conexión a la base de datos");
    }
}

function query($sql, $params = []) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function fetchAll($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetchAll();
}

function fetchOne($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetch();
}

function execute($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->rowCount();
}

function lastInsertId($pdo = null) {
    if ($pdo === null) {
        $pdo = getDBConnection();
    }
    return $pdo->lastInsertId();
}
?>
