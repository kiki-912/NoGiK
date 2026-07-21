<?php
// NoGiK - Database Connector with Auto-detect and Graceful Recovery

$host = 'localhost';
$db   = 'nogik';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // 1. Try to connect to MySQL without selecting database first to check server status
    $pdo_server = new PDO("mysql:host=$host;charset=$charset", $user, $pass, $options);
    
    // Check if database exists
    $stmt = $pdo_server->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db'");
    if (!$stmt->fetch()) {
        define('DB_STATUS', 'MISSING_DB');
    } else {
        // Connect with database selected
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, $options);
        
        // Check if users table exists
        $stmt_table = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt_table->rowCount() == 0) {
            define('DB_STATUS', 'MISSING_TABLES');
        } else {
            define('DB_STATUS', 'OK');
        }
    }
} catch (\PDOException $e) {
    define('DB_STATUS', 'CONNECTION_FAILED');
    define('DB_ERROR_MESSAGE', $e->getMessage());
}
?>

