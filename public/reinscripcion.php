<?php
require_once __DIR__ . '/../app/controllers/AuthController.php';
// Cargar configuración
$config = require __DIR__ . '/../config/config.php';
// Cargar ventanas por carrera desde JSON
$academicJsonPath = __DIR__ . '/../config/academic.json';
$academicWindows = [];
if (file_exists($academicJsonPath)) {
  $jsonStr = file_get_contents($academicJsonPath);
  $decoded = json_decode($jsonStr, true);
  if (is_array($decoded)) { $academicWindows = $decoded; }
}

$auth = new AuthController();
$auth->requireAuth();
$user = $auth->getCurrentUser();
$role = $_SESSION['user_role'] ?? ($user['rol'] ?? null);
$matricula = (string)($user['matricula'] ?? '');
$careerKey = $matricula !== '' ? strtoupper($matricula[0]) : null;

// Cálculo de ventana de reinscripción amigable por fechas (enero/agosto)
if (!empty($config['app']['timezone'])) { date_default_timezone_set($config['app']['timezone']); }
$now = new DateTime('now');
$y = (int)$now->format('Y');
$m = (int)$now->format('n');
// Configuración global por defecto desde config.php
$winsGlobal = $config['academic']['reinscripcion_windows'] ?? [
  'enero' => ['inicio_dia' => 10, 'fin_dia' => 14, 'mes' => 'Enero'],
  'agosto' => ['inicio_dia' => 10, 'fin_dia' => 14, 'mes' => 'Agosto'],
];
// Resolver ventanas por carrera si existen en academic.json
function resolveWindow($monthKey, $careerKey, $winsGlobal, $academicWindows) {
  $monthKey = strtolower($monthKey);
  $g = $winsGlobal[$monthKey] ?? null;
  $c = (is_array($academicWindows) && isset($academicWindows[$monthKey]) && $careerKey && isset($academicWindows[$monthKey][$careerKey]))
    ? $academicWindows[$monthKey][$careerKey]
    : null;
  if ($c && !empty($c['habilitado'])) {
    return [
      'inicio_dia' => (int)$c['inicio_dia'],
      'fin_dia' => (int)$c['fin_dia'],
      'mes' => $monthKey === 'enero' ? 'Enero' : 'Agosto'
    ];
  }
  return $g ?: ['inicio_dia' => 10, 'fin_dia' => 14, 'mes' => ($monthKey === 'enero' ? 'Enero' : 'Agosto')];
}
// Selección de ventana basada en mes actual: Ene-Abr -> Enero; May-Ago -> Agosto; Sep-Dic -> Enero siguiente
if ($m >= 5 && $m <= 8) {
  $w = resolveWindow('agosto', $careerKey, $winsGlobal, $academicWindows);
  $ventana = sprintf('%d–%d de %s %d', $w['inicio_dia'], $w['fin_dia'], $w['mes'], $y);
  $startDate = new DateTime(sprintf('%d-08-%02d', $y, (int)$w['inicio_dia']));
  $endDate = new DateTime(sprintf('%d-08-%02d', $y, (int)$w['fin_dia']));
} elseif ($m >= 9) {
  $w = resolveWindow('enero', $careerKey, $winsGlobal, $academicWindows);
  $ventana = sprintf('%d–%d de %s %d', $w['inicio_dia'], $w['fin_dia'], $w['mes'], $y + 1);
  $startDate = new DateTime(sprintf('%d-01-%02d', $y + 1, (int)$w['inicio_dia']));
  $endDate = new DateTime(sprintf('%d-01-%02d', $y + 1, (int)$w['fin_dia']));
} else {
  $w = resolveWindow('enero', $careerKey, $winsGlobal, $academicWindows);
  $ventana = sprintf('%d–%d de %s %d', $w['inicio_dia'], $w['fin_dia'], $w['mes'], $y);
  $startDate = new DateTime(sprintf('%d-01-%02d', $y, (int)$w['inicio_dia']));
  $endDate = new DateTime(sprintf('%d-01-%02d', $y, (int)$w['fin_dia']));
}
$activo = ($now >= $startDate && $now <= $endDate);
$estatusDefault = $config['academic']['estatus_alumno_default'] ?? 'Inscrito';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reinscripción</title>
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
        <!-- Marca duplicada eliminada: header superior ya muestra el logo -->
          <!-- Theme toggle eliminado: tema fijo oscuro -->
  </div>
  <div class="container-fluid">
    <span class="navbar-text text-white"><?= $role === 'admin' ? 'Admin' : ($role === 'alumno' ? 'Alumno' : ucfirst((string)$role)) ?></span>
  </div>
</nav>

<div class="app-shell">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="app-content">
    <h1 class="h3 mb-3">Reinscripción</h1>
    <?php if ($role === 'alumno'): ?>
      <div class="alert alert-info">
        La reinscripción para tu carrera se habilitará del <strong><?= htmlspecialchars($ventana) ?></strong>.
        <?php if ($activo): ?>
          <span class="badge bg-success ms-2">Reinscripción activa</span>
        <?php else: ?>
          <span class="badge bg-secondary ms-2">Fuera de ventana</span>
        <?php endif; ?>
      </div>
      <div class="card">
        <div class="card-body">
          <h2 class="h5">Estatus actual</h2>
          <p class="mb-2">Estatus: <span class="badge bg-success"><?= htmlspecialchars($estatusDefault) ?></span></p>
          <p class="text-muted mb-2">Cuando la ventana esté activa, podrás seleccionar tus materias de acuerdo con tu plan académico y disponibilidad de grupos.</p>
          <?php if ($activo): ?>
            <a href="seleccion_materias.php" class="btn btn-primary"><i class="bi bi-list-check"></i> Ir a selección de materias</a>
          <?php endif; ?>
        </div>
      </div>
    <?php elseif ($role === 'admin'): ?>
      <p class="text-muted">Gestiona la reinscripción de alumnos por periodo.</p>

      <div class="card mb-4">
        <div class="card-body">
          <form class="row g-3" method="post">
            <div class="col-md-4">
              <label class="form-label">Matrícula</label>
              <input type="text" name="matricula" class="form-control" placeholder="Ej. ISC240001" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Periodo</label>
              <select name="periodo" class="form-select" required>
                <option value="2024-2">2024-2</option>
                <option value="2025-1">2025-1</option>
              </select>
            </div>
            <div class="col-md-4 align-self-end">
              <button class="btn btn-success" type="submit"><i class="bi bi-check2"></i> Reinscribir</button>
            </div>
          </form>
        </div>
      </div>
      <div class="card">
        <div class="card-header">Configurar ventanas por carrera</div>
        <div class="card-body">
          <?php
            // Manejo de POST para actualizar academic.json
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cfg_submit'])) {
              $careers = ['S','I','C','M','Q','E','A'];
              $newCfg = ['enero'=>[],'agosto'=>[]];
              foreach ($careers as $ck) {
                $e_inicio = max(1, (int)($_POST['enero_'.$ck.'_inicio'] ?? 10));
                $e_fin = max($e_inicio, (int)($_POST['enero_'.$ck.'_fin'] ?? 14));
                $a_inicio = max(1, (int)($_POST['agosto_'.$ck.'_inicio'] ?? 10));
                $a_fin = max($a_inicio, (int)($_POST['agosto_'.$ck.'_fin'] ?? 14));
                $e_hab = !empty($_POST['enero_'.$ck.'_hab']);
                $a_hab = !empty($_POST['agosto_'.$ck.'_hab']);
                $newCfg['enero'][$ck] = ['inicio_dia'=>$e_inicio,'fin_dia'=>$e_fin,'habilitado'=>$e_hab];
                $newCfg['agosto'][$ck] = ['inicio_dia'=>$a_inicio,'fin_dia'=>$a_fin,'habilitado'=>$a_hab];
              }
              file_put_contents($academicJsonPath, json_encode($newCfg, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
              $academicWindows = $newCfg;
              echo '<div class="alert alert-success">Ventanas actualizadas correctamente.</div>';
            }
            $curr = is_array($academicWindows) && !empty($academicWindows) ? $academicWindows : json_decode(file_get_contents($academicJsonPath), true);
            $def = function($month,$ck,$field,$fallback){
              return htmlspecialchars((string)($curr[$month][$ck][$field] ?? $fallback));
            };
          ?>
          <form method="post">
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead>
                  <tr>
                    <th>Carrera</th>
                    <th>Enero Inicio</th>
                    <th>Enero Fin</th>
                    <th>Enero Habilitado</th>
                    <th>Agosto Inicio</th>
                    <th>Agosto Fin</th>
                    <th>Agosto Habilitado</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach(['S','I','C','M','Q','E','A'] as $ck): ?>
                  <tr>
                    <td><?= $ck ?></td>
                    <td><input type="number" class="form-control form-control-sm" name="enero_<?= $ck ?>_inicio" value="<?= $def('enero',$ck,'inicio_dia',10) ?>" min="1" max="31"></td>
                    <td><input type="number" class="form-control form-control-sm" name="enero_<?= $ck ?>_fin" value="<?= $def('enero',$ck,'fin_dia',14) ?>" min="1" max="31"></td>
                    <td><input type="checkbox" class="form-check-input" name="enero_<?= $ck ?>_hab" <?= !empty($curr['enero'][$ck]['habilitado']) ? 'checked' : '' ?>></td>
                    <td><input type="number" class="form-control form-control-sm" name="agosto_<?= $ck ?>_inicio" value="<?= $def('agosto',$ck,'inicio_dia',10) ?>" min="1" max="31"></td>
                    <td><input type="number" class="form-control form-control-sm" name="agosto_<?= $ck ?>_fin" value="<?= $def('agosto',$ck,'fin_dia',14) ?>" min="1" max="31"></td>
                    <td><input type="checkbox" class="form-check-input" name="agosto_<?= $ck ?>_hab" <?= !empty($curr['agosto'][$ck]['habilitado']) ? 'checked' : '' ?>></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="text-end">
              <button type="submit" name="cfg_submit" value="1" class="btn btn-primary"><i class="bi bi-save"></i> Guardar ventanas</button>
            </div>
          </form>
        </div>
      </div>
    <?php else: ?>
      <div class="alert alert-warning">Acceso limitado. Consulta con el administrador para gestionar reinscripciones.</div>
    <?php endif; ?>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>