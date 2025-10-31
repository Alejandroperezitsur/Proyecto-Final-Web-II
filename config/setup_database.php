<?php
// Script para crear la base de datos y ejecutar las migraciones

$config = require_once __DIR__ . '/config.php';

try {
    // Separar host y puerto si vienen juntos (e.g. 127.0.0.1:3306)
    $hostPort = $config['db']['host'] ?? '127.0.0.1:3306';
    $hostOnly = $hostPort;
    $port = null;
    if (strpos($hostPort, ':') !== false) {
        list($hostOnly, $port) = explode(':', $hostPort, 2);
    }

    // Primero conectar sin seleccionar base de datos
    $dsn = "mysql:host={$hostOnly}";
    if ($port) {
        $dsn .= ";port={$port}";
    }
    $dsn .= ";charset=utf8mb4";

    $pdo = new PDO(
        $dsn,
        $config['db']['user'],
        $config['db']['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Crear la base de datos si no existe
    $dbName = $config['db']['name'];
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` 
                DEFAULT CHARACTER SET utf8mb4 
                COLLATE utf8mb4_unicode_ci;");
    
    echo "Base de datos '$dbName' creada o verificada correctamente.\n";
    
    // Seleccionar la base de datos
    $pdo->exec("USE `$dbName`;");
    
    // Leer y ejecutar el archivo de migración
    $migrationPath = __DIR__ . '/../migrations/control_escolar.sql';
    $sql = file_get_contents($migrationPath);
    if ($sql === false) {
        throw new RuntimeException("No se pudo leer el archivo de migración: {$migrationPath}");
    }
    $pdo->exec($sql);
    
    echo "Migraciones ejecutadas correctamente.\n";
    
} catch (Throwable $e) {
    die("Error: " . $e->getMessage() . "\n");
}