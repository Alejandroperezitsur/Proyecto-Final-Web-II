<?php
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/models/Alumno.php';
require_once __DIR__ . '/../app/models/Usuario.php';

$auth = new AuthController();
$auth->requireAuth();
$auth->requireRole(['alumno']);
$user = $auth->getCurrentUser();
if (!$user || !isset($user['id'])) {
  http_response_code(403);
  echo 'Acceso denegado';
  exit;
}

// Cargar carga académica del alumno actual
$pdo = (new Usuario())->getDb();
$stmt = $pdo->prepare(
  "SELECT c.id AS calificacion_id,
          c.parcial1, c.parcial2, c.final,
          g.id AS grupo_id, g.nombre AS grupo_nombre, g.ciclo AS grupo_ciclo,
          m.nombre AS materia_nombre, m.clave AS materia_clave,
          u.email AS profesor_email, u.matricula AS profesor_matricula
   FROM calificaciones c
   JOIN grupos g ON c.grupo_id = g.id
   JOIN materias m ON g.materia_id = m.id
   JOIN usuarios u ON g.profesor_id = u.id
   WHERE c.alumno_id = :alumno
   ORDER BY g.ciclo, m.nombre" 
);
$stmt->execute([':alumno' => (int)$user['id']]);
$rows = $stmt->fetchAll();

// Agrupar por ciclo
$porCiclo = [];
foreach ($rows as $r) {
  $ciclo = (string)($r['grupo_ciclo'] ?? '');
  if (!isset($porCiclo[$ciclo])) { $porCiclo[$ciclo] = []; }
  $porCiclo[$ciclo][] = $r;
}

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SICEnet · ITSUR — Mi Carga Académica</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
<!-- Header institucional compacto -->
<header class="institutional-header">
  <div class="container-fluid">
    <!-- Marca duplicada eliminada: header superior ya muestra el logo -->
  </div>
</header>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
          <img src="assets/ITSUR-LOGO.webp" alt="ITSUR Logo" class="navbar-logo me-2">
          <span class="brand-text">SICEnet · ITSUR</span>
        </a>
          <!-- Theme toggle eliminado: tema fijo oscuro -->
  </div>
  <div class="container-fluid">
    <span class="navbar-text text-white">Alumno</span>
  </div>
</nav>

<div class="app-shell">
  <!-- Sidebar eliminado: accesos centralizados en dashboard -->
  <main class="app-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h3 mb-0">Mi Carga Académica</h1>
        <nav aria-label="breadcrumb" class="small">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="dashboard.php">Inicio</a></li>
            <li class="breadcrumb-item active" aria-current="page">Mi Carga Académica</li>
          </ol>
        </nav>
        <div class="text-muted small">Total materias: <?= count($rows) ?></div>
      </div>
      <div>
        <a href="perfil.php" class="btn btn-outline-secondary">Perfil</a>
        <a href="kardex.php" class="btn btn-primary">Ver mi Kardex</a>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <?php if (empty($rows)): ?>
          <div class="alert alert-info">No tienes grupos inscritos aún. Contacta a control escolar.</div>
        <?php else: ?>
          <?php foreach ($porCiclo as $ciclo => $lista): ?>
            <?php $cicloId = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string)$ciclo); ?>
            <h2 class="h5 mt-3">Ciclo: <?= htmlspecialchars($ciclo) ?></h2>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="flex-grow-1" style="max-width: 320px;">
                <input type="text" class="form-control" placeholder="Filtrar rápido en la tabla" data-quick-filter-for="#tabla-ciclo-<?= htmlspecialchars($cicloId) ?>">
              </div>
              <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-primary btn-sm" data-export="csv" data-target="#tabla-ciclo-<?= htmlspecialchars($cicloId) ?>" data-filename="mi_carga_<?= htmlspecialchars($cicloId) ?>.csv" data-timestamp="true"><i class="bi bi-filetype-csv"></i> Exportar CSV</button>
                <button class="btn btn-outline-secondary btn-sm" data-export="pdf" data-target="#tabla-ciclo-<?= htmlspecialchars($cicloId) ?>"><i class="bi bi-filetype-pdf"></i> Exportar PDF</button>
              </div>
            </div>
            <div class="table-responsive">
              <table id="tabla-ciclo-<?= htmlspecialchars($cicloId) ?>" class="table table-striped table-sort align-middle table-hover">
                <thead>
                  <tr>
                    <th>Materia</th>
                    <th>Clave</th>
                    <th>Grupo</th>
                    <th>Profesor</th>
                    <th>Parcial 1</th>
                    <th>Parcial 2</th>
                    <th>Final</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($lista as $r): ?>
                  <tr>
                    <td><?= htmlspecialchars($r['materia_nombre'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['materia_clave'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['grupo_nombre'] ?? '') ?></td>
                    <td><?= htmlspecialchars(($r['profesor_email'] ?? '') . ' (' . ($r['profesor_matricula'] ?? '') . ')') ?></td>
                    <td><?= htmlspecialchars((string)($r['parcial1'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($r['parcial2'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($r['final'] ?? '')) ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
              <div class="empty-state small text-muted d-none">No hay resultados que coincidan.</div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>