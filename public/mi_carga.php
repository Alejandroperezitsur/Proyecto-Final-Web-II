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
  <title>Mi Carga Académica</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">Control Escolar</a>
  </div>
  <div class="container-fluid">
    <span class="navbar-text text-white">Alumno</span>
  </div>
</nav>

<div class="app-shell">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="app-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h3">Mi Carga Académica</h1>
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
            <h2 class="h5 mt-3">Ciclo: <?= htmlspecialchars($ciclo) ?></h2>
            <div class="table-responsive">
              <table class="table table-striped">
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
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>