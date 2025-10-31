<?php
// Script para crear la base de datos y ejecutar las migraciones

$config = require_once __DIR__ . '/config.php';

try {
    // Primero conectar sin seleccionar base de datos
    $pdo = new PDO(
        "mysql:host={$config['db']['host']};charset=utf8mb4",
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
    $sql = file_get_contents(__DIR__ . '/../migrations/001_create_schema.sql');
    $pdo->exec($sql);
    
    echo "Migraciones ejecutadas correctamente.\n";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}