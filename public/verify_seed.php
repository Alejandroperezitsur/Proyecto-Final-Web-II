<?php
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/models/Usuario.php';
require_once __DIR__ . '/../app/models/Alumno.php';

$auth = new AuthController();
$auth->requireAuth();
$auth->requireRole(['admin']);
$user = $auth->getCurrentUser();

$pdo = (new Usuario())->getDb();

function countTable(PDO $pdo, string $table): int {
  return (int)$pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}

$counts = [
  'alumnos' => countTable($pdo, 'alumnos'),
  'profesores' => (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'profesor' AND activo = 1")->fetchColumn(),
  'materias' => countTable($pdo, 'materias'),
  'grupos' => countTable($pdo, 'grupos'),
  'calificaciones' => countTable($pdo, 'calificaciones'),
  'alumnos_con_calificacion' => (int)$pdo->query("SELECT COUNT(DISTINCT alumno_id) FROM calificaciones")->fetchColumn(),
  'profesores_con_grupo' => (int)$pdo->query("SELECT COUNT(DISTINCT u.id) FROM usuarios u JOIN grupos g ON g.profesor_id = u.id WHERE u.rol = 'profesor' AND u.activo = 1")->fetchColumn(),
];

$alumnosSinCal = $pdo->query("SELECT a.matricula, a.nombre, a.apellido FROM alumnos a LEFT JOIN calificaciones c ON c.alumno_id = a.id WHERE c.id IS NULL LIMIT 10")->fetchAll();
$profesSinGrupo = $pdo->query("SELECT u.matricula, u.email FROM usuarios u LEFT JOIN grupos g ON g.profesor_id = u.id WHERE u.rol = 'profesor' AND u.activo = 1 AND g.id IS NULL LIMIT 10")->fetchAll();

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Verificación de Seed</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 mb-0">Verificación de Seed</h1>
      <div class="text-muted">Resumen de coherencia tras cargar usuarios y datos académicos</div>
    </div>
    <div>
      <a href="admin_dashboard.php" class="btn btn-outline-primary">Panel Admin</a>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Alumnos</div><div class="h4 mb-0"><?= (int)$counts['alumnos'] ?></div></div></div></div>
    <div class="col-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Profesores activos</div><div class="h4 mb-0"><?= (int)$counts['profesores'] ?></div></div></div></div>
    <div class="col-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Materias</div><div class="h4 mb-0"><?= (int)$counts['materias'] ?></div></div></div></div>
    <div class="col-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Grupos</div><div class="h4 mb-0"><?= (int)$counts['grupos'] ?></div></div></div></div>
    <div class="col-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Calificaciones</div><div class="h4 mb-0"><?= (int)$counts['calificaciones'] ?></div></div></div></div>
    <div class="col-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Alumnos con calificación</div><div class="h4 mb-0"><?= (int)$counts['alumnos_con_calificacion'] ?></div></div></div></div>
    <div class="col-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Profesores con grupo</div><div class="h4 mb-0"><?= (int)$counts['profesores_con_grupo'] ?></div></div></div></div>
  </div>

  <div class="row g-3">
    <div class="col-12 col-xl-6">
      <div class="card">
        <div class="card-header"><strong>Alumnos sin calificaciones (muestra)</strong></div>
        <div class="card-body">
          <?php if (!$alumnosSinCal): ?>
            <div class="alert alert-success mb-0">Todos los alumnos tienen al menos una calificación.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead><tr><th>Matrícula</th><th>Nombre</th><th>Apellido</th></tr></thead>
                <tbody>
                <?php foreach ($alumnosSinCal as $a): ?>
                  <tr>
                    <td><?= htmlspecialchars($a['matricula'] ?? '') ?></td>
                    <td><?= htmlspecialchars($a['nombre'] ?? '') ?></td>
                    <td><?= htmlspecialchars($a['apellido'] ?? '') ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-12 col-xl-6">
      <div class="card">
        <div class="card-header"><strong>Profesores sin grupo (muestra)</strong></div>
        <div class="card-body">
          <?php if (!$profesSinGrupo): ?>
            <div class="alert alert-success mb-0">Todos los profesores activos tienen al menos un grupo.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead><tr><th>Matrícula</th><th>Email</th></tr></thead>
                <tbody>
                <?php foreach ($profesSinGrupo as $p): ?>
                  <tr>
                    <td><?= htmlspecialchars($p['matricula'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['email'] ?? '') ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="alert alert-secondary mt-4">
    Sugerencia: ejecuta <code>php scripts/setup_from_test_users.php</code> para cargar usuarios y datos académicos antes de verificar.
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>