<?php
namespace Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            $config = Config::get('db');
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['name']);
            try {
                self::$pdo = new PDO($dsn, $config['user'], $config['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo 'Error de conexión a BD: ' . htmlspecialchars($e->getMessage());
                exit;
            }
        }
        return self::$pdo;
    }
}