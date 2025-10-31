<?php
// Script CLI para fijar contraseñas de prueba en usuarios y alumnos
// Uso: php scripts/set_test_passwords.php [--password=TuPassSegura]

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script debe ejecutarse por CLI.\n");
    exit(1);
}

require_once __DIR__ . '/../config/db.php';

// Password por defecto (cámbiala si deseas)
$password = 'Test123!';

// Permitir pasar --password=...
foreach ($argv as $arg) {
    if (strpos($arg, '--password=') === 0) {
        $password = substr($arg, strlen('--password='));
        break;
    }
}

// Generar hash
$hash = password_hash($password, PASSWORD_DEFAULT);

// Obtener conexión PDO
$pdo = Database::getInstance()->getConnection();

try {
    $pdo->beginTransaction();

    // Actualizar contraseñas para admin y profesores
    $stmtUsers = $pdo->prepare("UPDATE usuarios SET password = :hash WHERE rol IN ('admin','profesor')");
    $stmtUsers->execute([':hash' => $hash]);
    $updatedUsers = $stmtUsers->rowCount();

    // Actualizar contraseñas para todos los alumnos
    $stmtAlumnos = $pdo->prepare("UPDATE alumnos SET password = :hash");
    $stmtAlumnos->execute([':hash' => $hash]);
    $updatedAlumnos = $stmtAlumnos->rowCount();

    $pdo->commit();

    echo "Contraseñas actualizadas correctamente.\n";
    echo "Usuarios (admin/profesores) actualizados: {$updatedUsers}\n";
    echo "Alumnos actualizados: {$updatedAlumnos}\n";
    echo "Password de prueba aplicada: {$password}\n";
    echo "\nPara cambiar la contraseña de prueba, ejecuta: php scripts\\set_test_passwords.php --password=OtraPass\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Error al actualizar contraseñas: " . $e->getMessage() . "\n");
    exit(1);
}