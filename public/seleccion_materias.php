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
  <title>Selección de materias</title>
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
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h1 class="h3 mb-0">Selección de materias</h1>
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
      <div class="table-responsive">
        <table class="table table-striped align-middle">
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
                  <form method="post" class="d-inline" onsubmit="return confirm('¿Confirmas dar de baja este grupo?');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($auth->generateCSRFToken()) ?>">
                    <input type="hidden" name="grupo_id" value="<?= (int)$g['id'] ?>">
                    <input type="hidden" name="accion" value="baja">
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-dash-circle"></i> Baja</button>
                  </form>
                <?php elseif (!$activo): ?>
                  <button class="btn btn-sm btn-secondary" disabled>Inscribir</button>
                <?php elseif ($ocupados >= (int)($g['cupo'] ?? 30)): ?>
                  <button class="btn btn-sm btn-warning" disabled>Grupo lleno</button>
                <?php elseif (!empty($faltantes)): ?>
                  <button class="btn btn-sm btn-outline-secondary" disabled>Prerrequisitos</button>
                <?php else: ?>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($auth->generateCSRFToken()) ?>">
                    <input type="hidden" name="grupo_id" value="<?= (int)$g['id'] ?>">
                    <input type="hidden" name="accion" value="inscribir">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-plus-circle"></i> Inscribir</button>
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
</body>
</html>