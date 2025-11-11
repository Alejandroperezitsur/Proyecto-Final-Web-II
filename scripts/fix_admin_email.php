<?php
// Script CLI para corregir el email del usuario admin.
// Uso:
//   php scripts/fix_admin_email.php --email=admin@itsur.edu.mx [--create] [--password=...] [--dry-run]
// Opciones:
//   --email       Email objetivo para el usuario admin (obligatorio)
//   --create      Si no existe admin, lo crea con la contraseña indicada o por defecto
//   --password    Contraseña para el admin al crearlo (por defecto: "admin123")
//   --dry-run     Muestra lo que haría sin aplicar cambios

if (PHP_SAPI !== 'cli') {
  fwrite(STDERR, "Este script debe ejecutarse por CLI.\n");
  exit(1);
}

require_once __DIR__ . '/../config/db.php';

// Defaults
$newEmail = null;
$createIfMissing = false;
$passwordPlain = 'admin123';
$dryRun = false;

// Parse args
foreach ($argv as $arg) {
  if (strpos($arg, '--email=') === 0) {
    $candidate = substr($arg, strlen('--email='));
    if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
      $newEmail = $candidate;
    }
  } elseif ($arg === '--create') {
    $createIfMissing = true;
  } elseif (strpos($arg, '--password=') === 0) {
    $candidatePw = substr($arg, strlen('--password='));
    if ($candidatePw !== '') { $passwordPlain = $candidatePw; }
  } elseif ($arg === '--dry-run') {
    $dryRun = true;
  }
}

if (!$newEmail) {
  fwrite(STDERR, "Error: Debes especificar --email con un valor válido.\n");
  fwrite(STDERR, "Ejemplo: php scripts/fix_admin_email.php --email=admin@itsur.edu.mx\n");
  exit(1);
}

try {
  $pdo = Database::getInstance()->getConnection();
} catch (Throwable $e) {
  fwrite(STDERR, "Error de conexión a la base de datos: " . $e->getMessage() . "\n");
  exit(1);
}

// Utilidades
$echo = function(string $msg) { echo $msg . "\n"; };
$warn = function(string $msg) { fwrite(STDERR, $msg . "\n"); };

// 1) Comprobar si ya existe un admin
$stmtSel = $pdo->query("SELECT id, email, activo FROM usuarios WHERE rol = 'admin' ORDER BY id ASC");
$admins = $stmtSel->fetchAll(PDO::FETCH_ASSOC);
$countAdmins = is_array($admins) ? count($admins) : 0;

// 2) Acciones en dry-run
if ($dryRun) {
  $echo("[DRY-RUN] Admins encontrados: " . $countAdmins);
  foreach ($admins as $a) {
    $echo("[DRY-RUN] Admin ID=" . (int)$a['id'] . " email=" . $a['email'] . " activo=" . (int)$a['activo']);
  }
  if ($countAdmins > 0) {
    $echo("[DRY-RUN] Actualizaré el email del primer admin a: " . $newEmail);
  } else {
    if ($createIfMissing) {
      $echo("[DRY-RUN] No hay admin. Lo crearía con email=" . $newEmail . " y password proporcionada.");
    } else {
      $echo("[DRY-RUN] No hay admin y no se ha indicado --create. No haré cambios.");
    }
  }
  // También mostraría la eliminación de placeholders
  $echo("[DRY-RUN] Eliminaría cuentas admin con emails placeholders: admin@local, admin@local.test");
  exit(0);
}

try {
  $pdo->beginTransaction();

  // 3) Eliminar placeholders conocidos para evitar confusión
  $stmtDel = $pdo->prepare("DELETE FROM usuarios WHERE rol = 'admin' AND email IN ('admin@local', 'admin@local.test')");
  $stmtDel->execute();
  $deleted = (int)$stmtDel->rowCount();

  // 4) Verificar colisión de email: si ya existe otro usuario con el email objetivo y no es el admin que vamos a actualizar
  $stmtEmail = $pdo->prepare("SELECT id, rol FROM usuarios WHERE email = :email LIMIT 1");
  $stmtEmail->execute([':email' => $newEmail]);
  $existingEmailUser = $stmtEmail->fetch(PDO::FETCH_ASSOC);

  if ($countAdmins > 0) {
    $admin = $admins[0];
    if ($existingEmailUser && (int)$existingEmailUser['id'] !== (int)$admin['id']) {
      // Colisión: ya existe otro usuario con ese email
      throw new RuntimeException("Ya existe un usuario con el email objetivo (ID=" . (int)$existingEmailUser['id'] . ", rol=" . $existingEmailUser['rol'] . ")");
    }

    // 5) Actualizar email y activar
    $stmtUpd = $pdo->prepare("UPDATE usuarios SET email = :email, activo = 1 WHERE id = :id");
    $stmtUpd->execute([':email' => $newEmail, ':id' => (int)$admin['id']]);
    $pdo->commit();
    $echo("Admin actualizado: ID=" . (int)$admin['id'] . " email=" . $admin['email'] . " -> " . $newEmail);
    if ($deleted > 0) { $echo("Registros eliminados de placeholders: {$deleted}"); }
  } else {
    if (!$createIfMissing) {
      // No hay admin y no se solicitó crear
      $pdo->rollBack();
      $warn("No existe usuario admin y no se indicó --create. No se realizaron cambios.");
      exit(2);
    }

    // 6) Crear admin nuevo
    if ($existingEmailUser) {
      throw new RuntimeException("No puedo crear admin: el email objetivo ya está en uso por otro usuario (ID=" . (int)$existingEmailUser['id'] . ")");
    }

    $hash = password_hash($passwordPlain, PASSWORD_DEFAULT);
    $stmtIns = $pdo->prepare("INSERT INTO usuarios (email, password, rol, activo) VALUES (:email, :pw, 'admin', 1)");
    $stmtIns->execute([':email' => $newEmail, ':pw' => $hash]);
    $newId = (int)$pdo->lastInsertId();
    $pdo->commit();
    $echo("Admin creado: ID=" . $newId . " email=" . $newEmail);
    $echo("Credenciales: " . $newEmail . " / " . $passwordPlain);
  }
} catch (Throwable $e) {
  if ($pdo->inTransaction()) { $pdo->rollBack(); }
  fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
  exit(1);
}

exit(0);

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