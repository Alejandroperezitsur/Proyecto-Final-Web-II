<?php
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/models/Grupo.php';
require_once __DIR__ . '/../app/models/Calificacion.php';
require_once __DIR__ . '/../app/models/Materia.php';

$auth = new AuthController();
$auth->requireRole(['alumno']);
$user = $auth->getCurrentUser();
$role = 'alumno';

// Cargar configuración y ventanas por carrera
$config = require __DIR__ . '/../config/config.php';
if (!empty($config['app']['timezone'])) { date_default_timezone_set($config['app']['timezone']); }
$academicJsonPath = __DIR__ . '/../config/academic.json';
$academicWindows = [];
if (file_exists($academicJsonPath)) {
  $decoded = json_decode(file_get_contents($academicJsonPath), true);
  if (is_array($decoded)) { $academicWindows = $decoded; }
}
$prereqMap = [];
if (!empty($academicWindows['prerrequisitos']) && is_array($academicWindows['prerrequisitos'])) {
  $prereqMap = $academicWindows['prerrequisitos'];
}

$now = new DateTime('now');
$y = (int)$now->format('Y');
$m = (int)$now->format('n');
$matricula = (string)($user['matricula'] ?? '');
$careerKey = $matricula !== '' ? strtoupper($matricula[0]) : null;
$winsGlobal = $config['academic']['reinscripcion_windows'] ?? [
  'enero' => ['inicio_dia' => 10, 'fin_dia' => 14, 'mes' => 'Enero'],
  'agosto' => ['inicio_dia' => 10, 'fin_dia' => 14, 'mes' => 'Agosto'],
];
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
if ($m >= 5 && $m <= 8) {
  $w = resolveWindow('agosto', $careerKey, $winsGlobal, $academicWindows);
  $startDate = new DateTime(sprintf('%d-08-%02d', $y, (int)$w['inicio_dia']));
  $endDate = new DateTime(sprintf('%d-08-%02d', $y, (int)$w['fin_dia']));
} elseif ($m >= 9) {
  $w = resolveWindow('enero', $careerKey, $winsGlobal, $academicWindows);
  $startDate = new DateTime(sprintf('%d-01-%02d', $y + 1, (int)$w['inicio_dia']));
  $endDate = new DateTime(sprintf('%d-01-%02d', $y + 1, (int)$w['fin_dia']));
} else {
  $w = resolveWindow('enero', $careerKey, $winsGlobal, $academicWindows);
  $startDate = new DateTime(sprintf('%d-01-%02d', $y, (int)$w['inicio_dia']));
  $endDate = new DateTime(sprintf('%d-01-%02d', $y, (int)$w['fin_dia']));
}
$activo = ($now >= $startDate && $now <= $endDate);

// Definir prefijos por carrera para filtrar materias
$prefixMap = [
  'S' => ['INF'],
  'E' => ['ELC'],
  'I' => ['IND'],
  'C' => ['CIV'],
  'M' => ['MEC'],
  'Q' => ['QUI'],
  'A' => ['AMB'],
];
$prefixes = $prefixMap[$careerKey] ?? [];

$grupoModel = new Grupo();
$calModel = new Calificacion();
$materiaModel = new Materia();
$ciclos = $grupoModel->getDistinctCiclos();
$cicloActual = count($ciclos) ? $ciclos[count($ciclos) - 1] : null;
$inscritosIds = $calModel->getGrupoIdsByAlumno((int)$user['id']);
$aprobadasClaves = $calModel->getMateriasAprobadasClavesByAlumno((int)$user['id']);

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grupo_id'])) {
  $token = $_POST['csrf_token'] ?? '';
  if (!$auth->validateCSRFToken($token)) {
    $mensaje = 'Token CSRF inválido.';
  } elseif (!$activo) {
    $mensaje = 'La ventana de reinscripción no está activa.';
  } else {
    $grupoId = (int)$_POST['grupo_id'];
    $accion = $_POST['accion'] ?? 'inscribir';
    // Validar que el grupo exista en el ciclo actual y filtro
    $gruposDisponibles = $cicloActual ? $grupoModel->getByCicloAndPrefixes($cicloActual, $prefixes) : [];
    $idsDisponibles = array_map(function($g){ return (int)$g['id']; }, $gruposDisponibles);
    if (!in_array($grupoId, $idsDisponibles)) {
      $mensaje = 'Grupo no disponible para tu selección.';
    } elseif ($accion === 'inscribir') {
      if (in_array($grupoId, $inscritosIds)) {
        $mensaje = 'Ya estás inscrito en este grupo.';
      } else {
        // Obtener el grupo y validar prerrequisitos
        $grupo = $grupoModel->find($grupoId);
        $materiaClave = null;
        if ($grupo && isset($grupo['materia_id'])) {
          $materia = $materiaModel->find((int)$grupo['materia_id']);
          $materiaClave = $materia['clave'] ?? null;
        }
        $reqs = ($materiaClave && isset($prereqMap[$materiaClave]) && is_array($prereqMap[$materiaClave])) ? $prereqMap[$materiaClave] : [];
        $faltantes = array_values(array_diff($reqs, $aprobadasClaves));
        if (!empty($faltantes)) {
          $mensaje = 'Faltan prerrequisitos: ' . implode(', ', $faltantes);
        } else {
          // Cupo y creación de inscripción
          $cupoGrupo = (int)($grupo['cupo'] ?? 30);
          $ocupados = $calModel->countByGrupo($grupoId);
          if ($ocupados >= $cupoGrupo) {
            $mensaje = 'El grupo está lleno.';
          } else {
            $created = $calModel->create(['alumno_id' => (int)$user['id'], 'grupo_id' => $grupoId]);
            if ($created) {
              $mensaje = 'Inscripción realizada correctamente.';
              $inscritosIds[] = $grupoId; // actualizar en memoria
            } else {
              $mensaje = 'No se pudo inscribir. Intenta de nuevo.';
            }
          }
        }
      }
    } elseif ($accion === 'baja') {
      if (!in_array($grupoId, $inscritosIds)) {
        $mensaje = 'No estás inscrito en este grupo.';
      } else {
        $ok = $calModel->deleteByAlumnoGrupo((int)$user['id'], $grupoId);
        if ($ok) {
          $mensaje = 'Baja realizada correctamente.';
          $inscritosIds = array_values(array_filter($inscritosIds, function($id) use ($grupoId) { return $id !== $grupoId; }));
        } else {
          $mensaje = 'No se pudo realizar la baja.';
        }
      }
    }
  }
}

// Listado de grupos disponibles según ciclo y carrera
$grupos = $cicloActual ? $grupoModel->getByCicloAndPrefixes($cicloActual, $prefixes) : [];

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SICEnet · ITSUR — Selección de materias</title>
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
          <img src="assets/ITSUR-LOGO.webp" alt="ITSUR" class="navbar-logo me-2">
          <span class="brand-text">SICEnet · ITSUR</span>
        </a>
  </div>
  <div class="container-fluid d-flex justify-content-end align-items-center">
    <button class="btn btn-outline-light btn-sm me-3" id="themeToggle" title="Cambiar tema">
      <i class="bi bi-moon-fill" id="theme-icon"></i>
    </button>
    <span class="navbar-text text-white">Alumno</span>
  </div>
</nav>

<div class="app-shell">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="app-content">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <div>
        <h1 class="h3 mb-0">Selección de materias</h1>
        <nav aria-label="breadcrumb" class="small">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="dashboard.php">Inicio</a></li>
            <li class="breadcrumb-item active" aria-current="page">Selección de materias</li>
          </ol>
        </nav>
      </div>
      <?php if ($activo): ?>
        <span class="badge bg-success">Ventana activa</span>
      <?php else: ?>
        <span class="badge bg-secondary">Fuera de ventana</span>
      <?php endif; ?>
    </div>
    <?php if ($mensaje): ?>
      <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    <?php if (!$cicloActual): ?>
      <div class="alert alert-warning">No hay ciclos disponibles.</div>
    <?php else: ?>
      <p class="text-muted">Ciclo actual: <strong><?= htmlspecialchars($cicloActual) ?></strong></p>
      <?php if (!$activo): ?>
        <div class="alert alert-secondary">La ventana de reinscripción no está activa para tu carrera. Consulta fechas en la sección de Reinscripción.</div>
      <?php endif; ?>
      <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
        <div class="flex-grow-1" style="max-width: 320px;">
          <input type="text" class="form-control" placeholder="Filtrar rápido en la tabla" data-quick-filter-for="#tabla-seleccion">
        </div>
        <div class="d-flex align-items-center gap-2">
           <button class="btn btn-outline-primary btn-sm" data-export="csv" data-target="#tabla-seleccion" data-filename="seleccion_materias.csv" data-timestamp="true"><i class="bi bi-filetype-csv"></i> Exportar CSV</button>
        <button class="btn btn-outline-secondary btn-sm" data-export="pdf" data-target="#tabla-seleccion"><i class="bi bi-filetype-pdf"></i> Exportar PDF</button>
        </div>
      </div>
      <div class="table-responsive">
        <table id="tabla-seleccion" class="table table-striped align-middle table-hover table-sort">
          <thead>
            <tr>
              <th>Clave</th>
              <th>Materia</th>
              <th>Grupo</th>
              <th>Profesor</th>
              <th>Cupo</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
          <?php if (count($grupos) === 0): ?>
            <tr class="empty-state-row"><td colspan="6" class="text-center text-muted">No hay grupos disponibles</td></tr>
          <?php endif; ?>
          <?php foreach ($grupos as $g): ?>
            <?php $ya = in_array((int)$g['id'], $inscritosIds); ?>
            <?php $ocupados = $calModel->countByGrupo((int)$g['id']); ?>
            <?php $reqs = (isset($prereqMap[$g['materia_clave']]) && is_array($prereqMap[$g['materia_clave']])) ? $prereqMap[$g['materia_clave']] : []; ?>
            <?php $faltantes = array_values(array_diff($reqs, $aprobadasClaves)); ?>
            <tr>
              <td><?= htmlspecialchars($g['materia_clave']) ?></td>
              <td><?= htmlspecialchars($g['materia_nombre']) ?></td>
              <td><?= htmlspecialchars($g['nombre']) ?></td>
              <td><?= htmlspecialchars($g['profesor_email'] ?: $g['profesor_matricula']) ?></td>
              <td><?= (int)$ocupados ?>/<?= (int)($g['cupo'] ?? 30) ?></td>
              <td>
                <?php if (!empty($faltantes)): ?>
                  <span class="badge bg-warning text-dark me-2">Prerrequisitos faltantes: <?= htmlspecialchars(implode(', ', $faltantes)) ?></span>
                <?php endif; ?>
                <?php if ($ya): ?>
                  <span class="badge bg-info me-2">Inscrito</span>
                  <form method="post" class="d-inline form-with-confirmation" data-confirm-message="¿Confirmas dar de baja la materia <?= htmlspecialchars($g['materia_nombre']) ?>?">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($auth->generateCSRFToken()) ?>">
                    <input type="hidden" name="grupo_id" value="<?= (int)$g['id'] ?>">
                    <input type="hidden" name="accion" value="baja">
                    <button type="submit" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Dar de baja esta materia">
                      <i class="bi bi-dash-circle"></i> Baja
                    </button>
                  </form>
                <?php elseif (!$activo): ?>
                  <button class="btn btn-sm btn-secondary" disabled>Inscribir</button>
                <?php elseif ($ocupados >= (int)($g['cupo'] ?? 30)): ?>
                  <button class="btn btn-sm btn-warning" disabled>Grupo lleno</button>
                <?php elseif (!empty($faltantes)): ?>
                  <button class="btn btn-sm btn-outline-secondary" disabled>Prerrequisitos</button>
                <?php else: ?>
                  <form method="post" class="d-inline form-with-confirmation" data-confirm-message="¿Confirmas inscribirte a <?= htmlspecialchars($g['materia_nombre']) ?> - Grupo <?= htmlspecialchars($g['nombre']) ?>?">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($auth->generateCSRFToken()) ?>">
                    <input type="hidden" name="grupo_id" value="<?= (int)$g['id'] ?>">
                    <input type="hidden" name="accion" value="inscribir">
                    <button type="submit" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Inscribirse a esta materia">
                      <i class="bi bi-plus-circle"></i> Inscribir
                    </button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="mt-3">
        <a href="mi_carga.php" class="btn btn-outline-secondary"><i class="bi bi-list-task"></i> Ver mi carga</a>
      </div>
    <?php endif; ?>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
// Inicializar tooltips
document.addEventListener('DOMContentLoaded', function() {
  // Tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
  
  // Confirmaciones elegantes para formularios
  document.querySelectorAll('.form-with-confirmation').forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      const message = this.dataset.confirmMessage || '¿Estás seguro de realizar esta acción?';
      
      if (confirm(message)) {
        // Deshabilitar botón para evitar doble envío
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Procesando...';
        }
        this.submit();
      }
    });
  });
  
  // Feedback visual para botones deshabilitados
  document.querySelectorAll('button[disabled]').forEach(btn => {
    btn.style.cursor = 'not-allowed';
  });
});
</script>
</body>
</html>