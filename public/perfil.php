<?php
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/models/Alumno.php';
require_once __DIR__ . '/../app/models/Usuario.php';

$auth = new AuthController();
$auth->requireAuth();
$user = $auth->getCurrentUser();
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
$isProfesor = ($_SESSION['user_role'] ?? '') === 'profesor';
$isAlumno = ($_SESSION['user_role'] ?? '') === 'alumno';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SICEnet · ITSUR — Mi Perfil</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
<!-- Header institucional compacto -->
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
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <img src="assets/ITSUR-LOGO.webp" alt="ITSUR Logo" class="navbar-logo me-2">
            <span class="brand-text">SICEnet · ITSUR</span>
        </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <button class="btn btn-outline-light btn-sm me-2" id="themeToggle" title="Cambiar tema">
            <i class="bi bi-sun-fill"></i>
          </button>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a>
        </li>
      </ul>
    </div>
  </div>
 </nav>

<div class="app-shell">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="app-content">
    <h1 class="h3 mb-3">Mi Perfil</h1>
    <p class="text-muted">Información de tu cuenta y acceso.</p>

    <div class="row g-4">
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-person-circle"></i> Datos</h5>
            <div class="table-responsive">
              <table class="table table-sm">
                <tbody>
                  <?php if ($isAlumno): ?>
                  <tr><th>Nombre</th><td><?= htmlspecialchars(($user['nombre'] ?? '') . ' ' . ($user['apellido'] ?? '')) ?></td></tr>
                  <tr><th>Matrícula</th><td><?= htmlspecialchars($user['matricula'] ?? '') ?></td></tr>
                  <tr><th>Email</th><td><?= htmlspecialchars($user['email'] ?? '') ?></td></tr>
                  <tr><th>Estado</th><td><?= (int)($user['activo'] ?? 0) ? 'Activo' : 'Inactivo' ?></td></tr>
                  <?php else: ?>
                  <tr><th>Rol</th><td><?= htmlspecialchars($_SESSION['user_role'] ?? '') ?></td></tr>
                  <tr><th>Matrícula</th><td><?= htmlspecialchars($user['matricula'] ?? '') ?></td></tr>
                  <tr><th>Email</th><td><?= htmlspecialchars($user['email'] ?? ($_SESSION['user_email'] ?? '')) ?></td></tr>
                  <tr><th>Último acceso</th><td><?= htmlspecialchars($user['last_login'] ?? '-') ?></td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
            <?php if ($isAlumno): ?>
            <a class="btn btn-primary" href="kardex.php?matricula=<?= urlencode($user['matricula'] ?? '') ?>">
              <i class="bi bi-journal-text"></i> Ver mi Kardex
            </a>
            <a class="btn btn-outline-primary ms-2" href="mi_carga.php">
              <i class="bi bi-list-check"></i> Mi Carga Académica
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-shield-lock"></i> Seguridad</h5>
            <p class="mb-2">Si notas actividad inusual, cambia tu contraseña o avisa al administrador.</p>
            <ul class="small text-muted mb-0">
              <li>No compartas tus credenciales.</li>
              <li>Cierra sesión en equipos compartidos.</li>
              <li>Usa contraseñas fuertes y únicas.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>