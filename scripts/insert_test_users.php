<?php
require_once __DIR__ . '/../config/config.php';

try {
    $config = include __DIR__ . '/../config/config.php';
    $hostPort = $config['db']['host'] ?? '127.0.0.1:3306';
    $hostOnly = $hostPort;
    $port = null;
    
    if (strpos($hostPort, ':') !== false) {
        list($hostOnly, $port) = explode(':', $hostPort, 2);
    }

    $dsn = "mysql:host={$hostOnly}";
    if ($port) {
        $dsn .= ";port={$port}";
    }
    $dsn .= ";dbname={$config['db']['name']};charset=utf8mb4";

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

    // Limpiar tablas existentes
    $pdo->exec("DELETE FROM calificaciones");
    $pdo->exec("DELETE FROM grupos");
    $pdo->exec("DELETE FROM alumnos");
    $pdo->exec("DELETE FROM usuarios WHERE rol = 'profesor'");

    // Insertar alumnos desde test_users.html
    $html = file_get_contents(__DIR__ . '/../public/test_users.html');
    if ($html === false) {
        throw new RuntimeException("No se pudo leer el archivo test_users.html");
    }

    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $tables = $dom->getElementsByTagName('table');

    // Insertar alumnos (primera tabla)
    $rows = $tables->item(0)->getElementsByTagName('tr');
    foreach ($rows as $i => $row) {
        if ($i === 0) continue; // Skip header row
        $cols = $row->getElementsByTagName('td');
        
        $matricula = $cols->item(0)->nodeValue;
        $nombre = $cols->item(1)->nodeValue;
        $apellido = $cols->item(2)->nodeValue;
        $email = $cols->item(3)->nodeValue;
        $password = $cols->item(4)->nodeValue;
        
        $stmt = $pdo->prepare("INSERT INTO alumnos (matricula, nombre, apellido, email, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $matricula,
            $nombre,
            $apellido,
            $email,
            password_hash($password, PASSWORD_DEFAULT)
        ]);
    }

    // Insertar profesores (segunda tabla)
    $rows = $tables->item(1)->getElementsByTagName('tr');
    foreach ($rows as $i => $row) {
        if ($i === 0) continue; // Skip header row
        $cols = $row->getElementsByTagName('td');
        
        $matricula = $cols->item(0)->nodeValue;
        $email = $cols->item(1)->nodeValue;
        $password = $cols->item(2)->nodeValue;
        
        $stmt = $pdo->prepare("INSERT INTO usuarios (matricula, email, password, rol, activo) VALUES (?, ?, ?, 'profesor', 1)");
        $stmt->execute([
            $matricula,
            $email,
            password_hash($password, PASSWORD_DEFAULT)
        ]);
    }

    echo "Usuarios insertados correctamente.\n";

} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}