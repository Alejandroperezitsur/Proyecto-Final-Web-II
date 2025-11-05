<?php
// Cargar configuración para toggles de módulos
$role = $_SESSION['user_role'] ?? '';
$userEmail = $_SESSION['user_email'] ?? ($_SESSION['user_identifier'] ?? '');
$config = [];
try {
  $config = require __DIR__ . '/../../config/config.php';
} catch (Throwable $e) {
  $config = [];
}
$modules = $config['modules'] ?? [
  'dashboard' => true,
  'alumnos' => true,
  'profesores' => true,
  'materias' => true,
  'grupos' => true,
  'calificaciones' => true,
  'kardex' => true,
  'mi_carga' => true,
  'reticula' => true,
  'reinscripcion' => true,
  'monitoreo_grupos' => true,
];
?>
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
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <?php $currentScript = basename($_SERVER['SCRIPT_NAME'] ?? ''); ?>
      <?php if ($currentScript === 'dashboard.php'): ?>
        <span class="navbar-brand mb-0 h1">Dashboard</span>
        <ul class="navbar-nav me-auto"></ul>
      <?php else: ?>
      <ul class="navbar-nav me-auto">
        <?php if ($role === 'admin'): ?>
          <?php if (!empty($modules['alumnos'])): ?><li class="nav-item"><a class="nav-link" href="alumnos.php"><i class="bi bi-people"></i> Alumnos</a></li><?php endif; ?>
          <?php if (!empty($modules['profesores'])): ?><li class="nav-item"><a class="nav-link" href="profesores.php"><i class="bi bi-person-badge"></i> Profesores</a></li><?php endif; ?>
          <?php if (!empty($modules['materias'])): ?><li class="nav-item"><a class="nav-link" href="materias.php"><i class="bi bi-book"></i> Materias</a></li><?php endif; ?>
          <li class="nav-item"><a class="nav-link" href="admin_dashboard.php"><i class="bi bi-tools"></i> Panel Admin</a></li>
          <li class="nav-item"><a class="nav-link" href="verify_seed.php"><i class="bi bi-check2-circle"></i> Verificación Seed</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_seed.php"><i class="bi bi-rocket"></i> Datos Demo</a></li>
        <?php endif; ?>
        <?php if (!empty($modules['grupos'])): ?><li class="nav-item"><a class="nav-link" href="grupos.php"><i class="bi bi-grid-3x3"></i> Grupos</a></li><?php endif; ?>
        <?php if (!empty($modules['calificaciones']) && $role === 'profesor'): ?><li class="nav-item"><a class="nav-link" href="calificaciones.php"><i class="bi bi-card-checklist"></i> Calificaciones</a></li><?php endif; ?>
        <?php if ($role === 'profesor'): ?>
          <li class="nav-item"><a class="nav-link" href="profesor_grupos.php"><i class="bi bi-collection"></i> Mis Grupos</a></li>
        <?php endif; ?>
        <?php if ($role === 'alumno'): ?>
          <?php if (!empty($modules['kardex'])): ?><li class="nav-item"><a class="nav-link" href="kardex.php"><i class="bi bi-journal-text"></i> Kardex</a></li><?php endif; ?>
          <?php if (!empty($modules['mi_carga'])): ?><li class="nav-item"><a class="nav-link" href="mi_carga.php"><i class="bi bi-list-check"></i> Mi Carga</a></li><?php endif; ?>
          <?php if (!empty($modules['reticula'])): ?><li class="nav-item"><a class="nav-link" href="reticula.php"><i class="bi bi-diagram-3"></i> Retícula</a></li><?php endif; ?>
          <?php if (!empty($modules['reinscripcion'])): ?><li class="nav-item"><a class="nav-link" href="reinscripcion.php"><i class="bi bi-arrow-repeat"></i> Reinscripción</a></li><?php endif; ?>
        <?php endif; ?>
        <?php if ($role === 'admin' && !empty($modules['monitoreo_grupos'])): ?>
          <li class="nav-item"><a class="nav-link" href="monitoreo_grupos.php"><i class="bi bi-activity"></i> Monitoreo</a></li>
        <?php endif; ?>
        <?php if (!empty($modules['dashboard'])): ?><li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li><?php endif; ?>
      </ul>
      <?php endif; ?>
      <ul class="navbar-nav">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle"></i>
            <?= htmlspecialchars($userEmail ?? '', ENT_QUOTES, 'UTF-8') ?>
            <?php if ($role): ?><span class="badge bg-secondary ms-2"><?= htmlspecialchars($role) ?></span><?php endif; ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
            <li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person"></i> Mi Perfil</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>