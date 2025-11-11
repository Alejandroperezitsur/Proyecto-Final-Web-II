<?php
// Endpoint web para gestionar el admin sin usar CLI.
// Acciones:
// - Borra admin con email exacto "admin@local" si existe
// - Crea/actualiza admin con email "admin@local.test" y contraseña "admin123"
// Seguridad básica: sólo permite ejecución desde localhost.

require_once __DIR__ . '/../config/db.php';

// Permitir sólo desde localhost para evitar exposición en redes
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1','::1'])) {
  http_response_code(403);
  echo 'Acceso restringido a localhost';
  exit;
}

try {
  $pdo = Database::getInstance()->getConnection();
} catch (Throwable $e) {
  http_response_code(500);
  echo 'Error de conexión a la base de datos: ' . htmlspecialchars($e->getMessage());
  exit;
}

$newEmail = 'admin@itsur.edu.mx';
$newPasswordPlain = 'admin123';

// Permitir personalizar por querystring: ?email=...&password=...
if (isset($_GET['email'])) {
  $candidate = trim((string)$_GET['email']);
  if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
    $newEmail = $candidate;
  }
}
if (isset($_GET['password'])) {
  $candidatePw = (string)$_GET['password'];
  if ($candidatePw !== '') { $newPasswordPlain = $candidatePw; }
}

header('Content-Type: text/plain; charset=utf-8');

try {
  $pdo->beginTransaction();

  // Borrar admin@local
  $stmtDel = $pdo->prepare("DELETE FROM usuarios WHERE rol = 'admin' AND email = :email");
  $stmtDel->execute([':email' => 'admin@local']);
  $deleted = (int)$stmtDel->rowCount();

  // Buscar admin existente
  $stmtSel = $pdo->query("SELECT id, email FROM usuarios WHERE rol = 'admin' LIMIT 1");
  $admin = $stmtSel->fetch(PDO::FETCH_ASSOC);

  $hash = password_hash($newPasswordPlain, PASSWORD_DEFAULT);
  $msg = '';

  if ($admin && isset($admin['id'])) {
    $stmtUpd = $pdo->prepare("UPDATE usuarios SET email = :email, password = :pw, activo = 1 WHERE id = :id");
    $stmtUpd->execute([':email' => $newEmail, ':pw' => $hash, ':id' => (int)$admin['id']]);
    $msg = "Admin actualizado: ID=" . (int)$admin['id'] . ", email=" . $admin['email'] . " -> " . $newEmail;
  } else {
    $stmtIns = $pdo->prepare("INSERT INTO usuarios (email, password, rol, activo) VALUES (:email, :pw, 'admin', 1)");
    $stmtIns->execute([':email' => $newEmail, ':pw' => $hash]);
    $newId = (int)$pdo->lastInsertId();
    $msg = "Admin creado: ID=" . $newId . ", email=" . $newEmail;
  }

  $pdo->commit();

  echo $msg . "\n";
  if ($deleted > 0) { echo "Registros eliminados de admin@local: {$deleted}\n"; }
  echo "Credenciales: {$newEmail} / {$newPasswordPlain}\n";
  echo "Ahora puedes iniciar sesión desde la página de login.";
} catch (Throwable $e) {
  $pdo->rollBack();
  http_response_code(500);
  echo 'Error: ' . $e->getMessage();
}