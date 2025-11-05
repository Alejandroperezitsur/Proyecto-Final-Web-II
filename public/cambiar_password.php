<?php
require_once __DIR__ . '/../app/controllers/AuthController.php';

$auth = new AuthController();
$auth->requireAuth();

$mensaje = '';
$tipoMensaje = '';
$tokenCSRF = $auth->generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    if (!$auth->validateCSRFToken($postToken)) {
        $mensaje = 'Token CSRF inválido.';
        $tipoMensaje = 'danger';
    } else {
        $actual = (string)($_POST['actual'] ?? '');
        $nueva = (string)($_POST['nueva'] ?? '');
        $confirm = (string)($_POST['confirm'] ?? '');
        if ($nueva !== $confirm) {
            $mensaje = 'La confirmación no coincide con la nueva contraseña.';
            $tipoMensaje = 'danger';
        } else {
            $ok = $auth->changePassword($actual, $nueva);
            if ($ok) {
                $mensaje = 'Contraseña actualizada correctamente.';
                $tipoMensaje = 'success';
            } else {
                $mensaje = 'No se pudo actualizar la contraseña. Verifica la contraseña actual y los requisitos mínimos.';
                $tipoMensaje = 'danger';
            }
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SICEnet · ITSUR — Cambiar contraseña</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
<header class="institutional-header">
    <div class="container-fluid">
        <a href="dashboard.php" class="institutional-brand">
            <img src="assets/ITSUR-LOGO.webp" alt="ITSUR Logo" class="institutional-logo">
            <div class="institutional-text">
                <h1 class="institutional-title">SICEnet · ITSUR</h1>
                <p class="institutional-subtitle">Sistema Integral de Control Escolar</p>
            </div>
        </a>
    </div>
 </header>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="perfil.php"><i class="bi bi-person"></i> Mi Perfil</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a>
        </li>
      </ul>
    </div>
  </div>
 </nav>

<div class="app-shell">
  <main class="app-content">
    <h1 class="h3 mb-3">Cambiar contraseña</h1>
    <p class="text-muted">Actualiza tu contraseña para mantener tu cuenta segura.</p>

    <?php if ($mensaje !== ''): ?>
    <div class="alert alert-<?= htmlspecialchars($tipoMensaje) ?>">
        <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-body">
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="needs-validation" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($tokenCSRF) ?>">
          <div class="mb-3">
            <label for="actual" class="form-label">Contraseña actual</label>
            <input type="password" class="form-control" id="actual" name="actual" required>
            <div class="invalid-feedback">Ingresa tu contraseña actual.</div>
          </div>
          <div class="mb-3">
            <label for="nueva" class="form-label">Nueva contraseña</label>
            <input type="password" class="form-control" id="nueva" name="nueva" required minlength="8" pattern="^(?=.*[A-Za-z])(?=.*\d).{8,}$" aria-describedby="helpNueva">
            <div id="helpNueva" class="form-text">Mínimo 8 caracteres, con letras y números.</div>
            <div class="invalid-feedback">La nueva contraseña no cumple los requisitos.</div>
          </div>
          <div class="mb-3">
            <label for="confirm" class="form-label">Confirmar nueva contraseña</label>
            <input type="password" class="form-control" id="confirm" name="confirm" required>
            <div class="invalid-feedback">Confirma la nueva contraseña.</div>
          </div>
          <button type="submit" class="btn btn-primary"><i class="bi bi-shield-lock"></i> Guardar cambios</button>
          <a href="perfil.php" class="btn btn-outline-secondary ms-2">Cancelar</a>
        </form>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
// Validación del formulario
(function() {
  'use strict';
  const form = document.querySelector('.needs-validation');
  form.addEventListener('submit', function(event) {
    if (!form.checkValidity()) {
      event.preventDefault();
      event.stopPropagation();
    }
    if (form.nueva.value !== form.confirm.value) {
      event.preventDefault();
      event.stopPropagation();
      alert('La confirmación no coincide con la nueva contraseña.');
    }
    form.classList.add('was-validated');
  }, false);
})();
</script>
</body>
</html>