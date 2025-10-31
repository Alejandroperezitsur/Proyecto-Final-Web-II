<?php
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/models/Materia.php';
require_once __DIR__ . '/../app/models/Usuario.php';

$auth = new AuthController();
$auth->requireAuth();
$user = $auth->getCurrentUser();
$role = $_SESSION['user_role'] ?? '';

// Determinar carrera por prefijo de matrícula del alumno
$carreras = [
  'S' => ['label' => 'Ingeniería en Sistemas Computacionales', 'prefix' => ['INF','CSI','PROG','BD']],
  'I' => ['label' => 'Ingeniería Industrial', 'prefix' => ['IND','EST','ADM']],
  'C' => ['label' => 'Ingeniería Civil', 'prefix' => ['CIV','EST','MAT']],
  'M' => ['label' => 'Ingeniería Mecánica', 'prefix' => ['MEC','MAT','EST']],
  'Q' => ['label' => 'Ingeniería Química', 'prefix' => ['QUI','QIM','MAT']],
  'E' => ['label' => 'Ingeniería Electrónica', 'prefix' => ['ELE','ELC','DIG']],
  'A' => ['label' => 'Ingeniería Ambiental', 'prefix' => ['AMB','BIO','MAT']],
];

$careerKey = null; $career = null;
if ($role === 'alumno') {
  $mat = (string)($user['matricula'] ?? '');
  $careerKey = $mat !== '' ? strtoupper($mat[0]) : null;
  $career = $careerKey && isset($carreras[$careerKey]) ? $carreras[$careerKey] : null;
} elseif ($role === 'admin') {
  $sel = strtoupper(trim((string)($_GET['carrera'] ?? '')));
  if ($sel === '' || !isset($carreras[$sel])) {
    // valor por defecto para admin
    $sel = 'S';
  }
  $careerKey = $sel;
  $career = $carreras[$careerKey];
}

// Catálogo de materias
$materiaModel = new Materia();
$catalog = $materiaModel->getCatalog();

// Filtrar materias por carrera (prefijos)
function matchPrefix($clave, $prefixes) {
  $clave = (string)$clave;
  foreach ($prefixes as $p) {
    if (stripos($clave, $p) === 0) { return true; }
  }
  return false;
}

$materiasCarrera = [];
if ($career) {
  foreach ($catalog as $m) {
    if (matchPrefix($m['clave'] ?? '', $career['prefix'])) {
      $materiasCarrera[] = $m;
    }
  }
}

// Construir retícula: 8 semestres x 5 materias = 40
$semestres = [];
for ($s=1; $s<=8; $s++) { $semestres[$s] = []; }

// Relleno con materias disponibles; si faltan, marcar como pendiente
$idx = 0;
for ($s=1; $s<=8; $s++) {
  for ($i=0; $i<5; $i++) {
    $mat = $materiasCarrera[$idx] ?? null;
    if ($mat) {
      $semestres[$s][] = $mat;
      $idx++;
    } else {
      $semestres[$s][] = ['nombre' => 'Pendiente de alta', 'clave' => '—'];
    }
  }
}

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Retícula Académica</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/styles.css" rel="stylesheet">
  <style>
    .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    @media (min-width: 992px) { .grid { grid-template-columns: repeat(4, 1fr); } }
    @media (max-width: 991px) { .grid { grid-template-columns: repeat(2, 1fr); } }
    .sem-card { border: 1px solid #dee2e6; border-radius: 8px; }
    .sem-header { background: #f8f9fa; border-bottom: 1px solid #dee2e6; padding: 8px 12px; font-weight: 600; }
    .sem-body { padding: 8px 12px; }
    .pending { color: #6c757d; font-style: italic; }
    @media print {
      body * { visibility: hidden; }
      .print-area, .print-area * { visibility: visible; }
      .print-area { position: absolute; left:0; top:0; width:100%; }
      nav, .app-sidebar, .btn, .badge { display:none !important; }
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">Control Escolar</a>
  </div>
  <div class="container-fluid">
    <span class="navbar-text text-white">Retícula Académica</span>
  </div>
</nav>

  <div class="app-shell">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="app-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h3">Retícula Académica</h1>
      <div>
        <?php if ($role === 'alumno' && $career): ?>
          <span class="badge bg-primary">Carrera: <?= htmlspecialchars($career['label']) ?></span>
        <?php elseif ($role === 'admin'): ?>
          <form class="d-inline" method="get">
            <label class="form-label me-2 mb-0">Carrera</label>
            <select name="carrera" class="form-select d-inline w-auto" onchange="this.form.submit()">
              <?php foreach ($carreras as $key => $info): ?>
                <option value="<?= htmlspecialchars($key) ?>" <?= $key === $careerKey ? 'selected' : '' ?>><?= htmlspecialchars($info['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </form>
          <button class="btn btn-outline-secondary ms-2" onclick="window.print()"><i class="bi bi-printer"></i> Exportar PDF</button>
        <?php else: ?>
          <span class="badge bg-secondary">Carrera no determinada</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="alert alert-info">
      Para poder cursar <strong>Residencias Profesionales (9º semestre)</strong> es indispensable haber liberado los <strong>10 niveles de Inglés</strong> y el <strong>Servicio Social</strong>.
    </div>

    <?php
      // Resumen de conteos
      $totalPlan = 8 * 5; // 40 materias
      $totalReg = 0;
      foreach ($semestres as $materias) {
        foreach ($materias as $m) {
          if (($m['clave'] ?? '—') !== '—') { $totalReg++; }
        }
      }
      $totalPend = max(0, $totalPlan - $totalReg);
    ?>
    <?php if ($career): ?>
      <div class="mb-3">
        <span class="badge bg-success">Registradas: <?= (int)$totalReg ?>/<?= (int)$totalPlan ?></span>
        <span class="badge bg-warning text-dark ms-2">Pendientes: <?= (int)$totalPend ?></span>
      </div>

      <div class="grid print-area">
        <?php foreach ($semestres as $num => $materias): ?>
          <div class="sem-card">
            <?php $reg = 0; foreach ($materias as $m) { if (($m['clave'] ?? '—') !== '—') { $reg++; } } ?>
            <div class="sem-header">Semestre <?= (int)$num ?> (<?= (int)$reg ?>/5)</div>
            <div class="sem-body">
              <ul class="mb-0">
                <?php foreach ($materias as $m): ?>
                  <?php $isPending = ($m['clave'] ?? '—') === '—'; ?>
                  <li class="<?= $isPending ? 'pending' : '' ?>">
                    <?= htmlspecialchars($m['nombre'] ?? '') ?>
                    <?php if (!$isPending): ?>
                      <span class="text-muted">(<?= htmlspecialchars($m['clave'] ?? '') ?>)</span>
                    <?php else: ?>
                      <span class="text-muted">(no registrada)</span>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        <?php endforeach; ?>
        <div class="sem-card">
          <div class="sem-header">9º Semestre</div>
          <div class="sem-body">
            <ul class="mb-0">
              <li><strong>Residencias Profesionales</strong></li>
              <li class="text-muted">Requisitos: 10 niveles de Inglés y Servicio Social liberados.</li>
            </ul>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="alert alert-warning">No se pudo determinar la carrera del alumno. Asegúrate de que la matrícula tenga un prefijo válido (S, I, C, M, Q, E, A) y que existan materias en el catálogo.</div>
    <?php endif; ?>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>