<?php
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        $config = require_once __DIR__ . '/config.php';
        try {
            $host = $config['db']['host'];
            $dbname = $config['db']['name'];
            $user = $config['db']['user'];
            $pass = $config['db']['pass'];
            
            // First create the database if it doesn't exist
            $tempConn = new PDO("mysql:host=$host", $user, $pass);
            $tempConn->exec("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            
            // Now connect to the database
            $this->conn = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>