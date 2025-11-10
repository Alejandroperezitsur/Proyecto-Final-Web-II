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

        // Reparación/aseguramiento del usuario admin (credenciales conocidas)
        // Garantiza que exista el usuario 'admin' y que su contraseña sea 'admin123'
        try {
            $stmt = $pdo->prepare("SELECT id, password_hash FROM admins WHERE usuario='admin' LIMIT 1");
            $stmt->execute();
            $adm = $stmt->fetch(\PDO::FETCH_ASSOC);
            $desiredPass = 'admin123';
            if ($adm) {
                // Si el hash actual no valida con la contraseña deseada, actualizar
                if (!password_verify($desiredPass, $adm['password_hash'])) {
                    $newHash = password_hash($desiredPass, PASSWORD_BCRYPT);
                    $upd = $pdo->prepare('UPDATE admins SET password_hash=? WHERE id=?');
                    $upd->execute([$newHash, $adm['id']]);
                }
            } else {
                // Crear usuario admin por defecto si no existe
                $newHash = password_hash($desiredPass, PASSWORD_BCRYPT);
                $ins = $pdo->prepare('INSERT INTO admins (usuario, nombre, password_hash) VALUES (?,?,?)');
                $ins->execute(['admin', 'Administrador', $newHash]);
            }
        } catch (\Throwable $e) {
            // Ignorar errores silenciosamente; no bloquear la app por esta reparación
        }
    }
}