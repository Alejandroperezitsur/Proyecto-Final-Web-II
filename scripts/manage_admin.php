<?php
// Script CLI para gestionar el usuario admin según la solicitud:
// - Borra el admin con email "admin@local" si existe
// - Crea o actualiza el admin con email "admin@local.test" y contraseña "admin123"
// Uso: php scripts/manage_admin.php

if (PHP_SAPI !== 'cli') {
  fwrite(STDERR, "Este script debe ejecutarse por CLI.\n");
  exit(1);
}

require_once __DIR__ . '/../config/db.php';

// Parámetros deseados
$newEmail = 'admin@itsur.edu.mx';
$newPasswordPlain = 'admin123';

// Flags opcionales: --email=... --password=...
foreach ($argv as $arg) {
  if (strpos($arg, '--email=') === 0) {
    $e = substr($arg, strlen('--email='));
    if ($e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)) { $newEmail = $e; }
  } elseif (strpos($arg, '--password=') === 0) {
    $p = substr($arg, strlen('--password='));
    if ($p !== '') { $newPasswordPlain = $p; }
  }
}

$pdo = Database::getInstance()->getConnection();

try {
  $pdo->beginTransaction();

  // 1) Borrar admin@local si existe (exacto)
  $stmtDel = $pdo->prepare("DELETE FROM usuarios WHERE rol = 'admin' AND email = :email");
  $stmtDel->execute([':email' => 'admin@local']);
  $deleted = $stmtDel->rowCount();

  // 2) Buscar admin existente
  $stmtSel = $pdo->query("SELECT id, email FROM usuarios WHERE rol = 'admin' LIMIT 1");
  $admin = $stmtSel->fetch(PDO::FETCH_ASSOC);

  $hash = password_hash($newPasswordPlain, PASSWORD_DEFAULT);

  if ($admin && isset($admin['id'])) {
    // Actualizar email y contraseña
    $stmtUpd = $pdo->prepare("UPDATE usuarios SET email = :email, password = :pw, activo = 1 WHERE id = :id");
    $stmtUpd->execute([':email' => $newEmail, ':pw' => $hash, ':id' => (int)$admin['id']]);
    $msg = "Admin actualizado: ID=" . (int)$admin['id'] . ", email=" . $admin['email'] . " -> " . $newEmail;
  } else {
    // Insertar nuevo admin
    $stmtIns = $pdo->prepare("INSERT INTO usuarios (email, password, rol, activo) VALUES (:email, :pw, 'admin', 1)");
    $stmtIns->execute([':email' => $newEmail, ':pw' => $hash]);
    $newId = (int)$pdo->lastInsertId();
    $msg = "Admin creado: ID=" . $newId . ", email=" . $newEmail;
  }

  $pdo->commit();

  echo $msg . "\n";
  if ($deleted > 0) { echo "Registros eliminados de admin@local: {$deleted}\n"; }
  echo "Puedes iniciar sesión con: {$newEmail} / {$newPasswordPlain}\n";
} catch (Throwable $e) {
  $pdo->rollBack();
  fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
  exit(1);
}

?>