<?php
namespace Core;

use PDO;

class Installer
{
    public static function ensureDatabaseReady(): void
    {
        $config = Config::get('db');
        // Intentar crear la BD si no existe
        try {
            $pdoRoot = new PDO('mysql:host=' . $config['host'] . ';charset=utf8mb4', $config['user'], $config['pass']);
            $pdoRoot->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdoRoot->exec('CREATE DATABASE IF NOT EXISTS `' . $config['name'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        } catch (\Throwable $e) {
            // continuar; puede que no tengamos permisos, pero intentaremos conectar igualmente
        }

        $pdo = Database::getConnection();

        // Comprobar si hay tablas
        $stmt = $pdo->query("SHOW TABLES LIKE 'alumnos'");
        $exists = $stmt->fetchColumn();

        if (!$exists) {
            $schemaPath = __DIR__ . '/../db/schema.sql';
            if (file_exists($schemaPath)) {
                $sql = file_get_contents($schemaPath);
                $pdo->exec($sql);
            }
            // Ejecutar seeder programático para datos de prueba
            $seedPath = __DIR__ . '/../db/seed.php';
            if (file_exists($seedPath)) {
                require_once $seedPath;
                if (function_exists('SICEnetRunSeed')) {
                    SICEnetRunSeed($pdo);
                }
            }
        }
    }
}