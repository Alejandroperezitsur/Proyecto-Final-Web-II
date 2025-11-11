<?php
// Actualiza el email del admin a un dominio institucional.
// Uso: php scripts/fix_admin_email.php [--email=admin@itsur.edu.mx]

if (PHP_SAPI !== 'cli') {
  fwrite(STDERR, "Este script debe ejecutarse por CLI.\n");
  exit(1);
}

require_once __DIR__ . '/../config/db.php';

$newEmail = 'admin@itsur.edu.mx';
foreach ($argv as $arg) {
  if (strpos($arg, '--email=') === 0) { $newEmail = substr($arg, strlen('--email=')); }
}

$pdo = Database::getInstance()->getConnection();

try {
  $pdo->beginTransaction();
  // Encontrar admin actual (si existe)
  $stmtSel = $pdo->query("SELECT id, email FROM usuarios WHERE rol = 'admin' LIMIT 1");
  $admin = $stmtSel->fetch(PDO::FETCH_ASSOC);
  if (!$admin) { throw new RuntimeException('No existe usuario admin en la base de datos'); }

  // Actualizar email
  $stmtUpd = $pdo->prepare("UPDATE usuarios SET email = :email WHERE id = :id");
  $stmtUpd->execute([':email' => $newEmail, ':id' => (int)$admin['id']]);
  $pdo->commit();

  echo "Email del admin actualizado: {$admin['email']} -> {$newEmail}\n";
  echo "Ahora puedes iniciar sesión con: {$newEmail}\n";
} catch (Throwable $e) {
  $pdo->rollBack();
  fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
  exit(1);
}

?>