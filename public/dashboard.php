<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/capas/negocio/ControlGrupos.php';
require_once __DIR__ . '/../app/capas/negocio/ControlCalificaciones.php';

use App\Capas\Negocio\ControlGrupos;
use App\Capas\Negocio\ControlCalificaciones;

$controlGrupos = new ControlGrupos();
$controlCal = new ControlCalificaciones();

$controlAut = new \AuthController();
$controlAut->requireAuth();

$usuarioActual = $controlAut->getCurrentUser();
$esAdmin = $_SESSION['user_role'] === 'admin';
// KPIs globales (solo admin)
$estadisticas = $controlCal->obtenerAgregadosGlobales();
$promediosPorCiclo = $controlCal->obtenerPromediosPorCiclo();
// Catálogo de ciclos y filtros
$ciclosCatalog = $controlGrupos->obtenerCiclosDistintos(null);
$cicloSeleccionado = trim((string)($_GET['ciclo'] ?? ''));
if ($cicloSel !== '') {
    // Si hay ciclo seleccionado, recalculamos KPIs para ese ciclo
    foreach ($calModel->getAggregatesByCicloDetailed() as $row) {
        if ((string)$row['ciclo'] === $cicloSel) {
            $stats = [
                'total' => $row['total'],
                'promedio' => $row['promedio'],
                'aprobados' => $row['aprobados'],
                'reprobados' => $row['reprobados'],
            ];
            // Limitar gráfica de promedio por ciclo al ciclo seleccionado
            $found = false;
            foreach ($avgByCiclo as $rc) {
                if ((string)($rc['ciclo'] ?? '') === $cicloSel) { $avgByCiclo = [$rc]; $found = true; break; }
            }
            if (!$found) { $avgByCiclo = []; }
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <title>SICEnet · ITSUR — Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" 
          rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" 
          rel="stylesheet">
  <link href="assets/css/styles.css" rel="stylesheet">
  <link href="assets/css/desktop-fixes.css" rel="stylesheet">
    <style>
    /* impresión: solo listado/tabla en páginas con clase .print-list */
    @media print {
      body * { visibility: hidden; }
      .print-list, .print-list * { visibility: visible; }
      .print-list { position: absolute; left: 0; top: 0; width: 100%; }
      .btn, .form-control, .form-select, nav, header, .app-sidebar { display: none !important; }
    }
    </style>
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
      <!-- Marca duplicada eliminada: el header superior contiene la marca con logo -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                    data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if ($esAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="alumnos.php">
                            <i class="bi bi-people"></i> Alumnos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profesores.php">
                            <i class="bi bi-person-badge"></i> Profesores
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="materias.php">
                            <i class="bi bi-book"></i> Materias
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="grupos.php">
                            <i class="bi bi-grid-3x3"></i> Grupos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="calificaciones.php">
                            <i class="bi bi-card-checklist"></i> Calificaciones
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <button class="btn btn-outline-light btn-sm me-2" id="help-toggle" title="Centro de Ayuda" data-bs-toggle="modal" data-bs-target="#helpModal">
                            <i class="bi bi-question-circle"></i>
                        </button>
                    </li>
          <li class="nav-item">
            <!-- Theme toggle eliminado: tema fijo oscuro. Lógica comentada en assets/js/main.js -->
          </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" 
                           data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <?= htmlspecialchars($_SESSION['user_email'] ?? ($_SESSION['user_identifier'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="perfil.php">
                                    <i class="bi bi-person"></i> Mi Perfil
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item" href="logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="app-shell">
        <?php include __DIR__ . '/partials/sidebar.php'; ?>
        <main class="app-content">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h2 class="mb-0">Dashboard</h2>
          <div class="d-flex gap-2">
            <a href="seleccion_materias.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Inscribir Materias</a>
            <a href="kardex.php" class="btn btn-outline-primary"><i class="bi bi-journal-text"></i> Ver Kardex</a>
            <a href="perfil.php" class="btn btn-outline-secondary"><i class="bi bi-person"></i> Mi Perfil</a>
          </div>
        </div>
                    <?php if ($esAdmin): ?>
        <div class="card mb-4 border-0 shadow-sm">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-md-8">
                <h6 class="card-title mb-2">
                  <i class="bi bi-calendar3 me-2"></i>Filtro de Ciclo Académico
                </h6>
                <p class="text-muted small mb-0">
                  Selecciona un ciclo específico para analizar el rendimiento académico de ese período
                </p>
              </div>
              <div class="col-md-4">
                <form method="get" action="dashboard.php" class="d-flex gap-2">
                  <select name="ciclo" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">📊 Todos los ciclos</option>
                    <?php foreach ($ciclosCatalog as $c): $val = trim((string)($c['ciclo'] ?? $c)); ?>
                      <option value="<?= htmlspecialchars($val) ?>" <?= $val === $cicloSel ? 'selected' : '' ?>>
                        📅 <?= htmlspecialchars($val) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <?php if ($cicloSel): ?>
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm" title="Limpiar filtro">
                      <i class="bi bi-x-circle"></i>
                    </a>
                  <?php endif; ?>
                </form>
              </div>
            </div>
            <?php if ($cicloSel): ?>
              <div class="alert alert-info mt-3 mb-0">
                <i class="bi bi-info-circle me-2"></i>
                Mostrando datos del ciclo: <strong><?= htmlspecialchars($cicloSel) ?></strong>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
        <div class="row g-4">
                    <?php if ($esAdmin): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-people text-primary"></i> Alumnos
                        </h5>
                        <p class="card-text">Gestiona el registro de alumnos.</p>
                        <a href="alumnos.php" class="btn btn-primary">
                            Administrar
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-person-badge text-success"></i> Profesores
                        </h5>
                        <p class="card-text">Administra la plantilla docente.</p>
                        <a href="profesores.php" class="btn btn-success">
                            Administrar
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-book text-info"></i> Materias
                        </h5>
                        <p class="card-text">Gestiona el catálogo de materias.</p>
                        <a href="materias.php" class="btn btn-info">
                            Administrar
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="col-md-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-grid-3x3 text-warning"></i> Grupos
                        </h5>
                        <p class="card-text">
                            <?= $esAdmin ? 'Administra los grupos y horarios.' : 
                                         'Ver tus grupos asignados.' ?>
                        </p>
                        <a href="grupos.php" class="btn btn-warning">
                            <?= $esAdmin ? 'Administrar' : 'Ver Grupos' ?>
                        </a>
                    </div>
                </div>
            </div>

            <?php if (!$esAdmin): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-card-checklist text-danger"></i> Calificaciones
                        </h5>
                        <p class="card-text">Registra las calificaciones de tus grupos.</p>
                        <a href="calificaciones.php" class="btn btn-danger">
                            Calificar
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($esAdmin): ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-header">KPIs Globales</div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <div class="p-3 border rounded">
                                    <div class="text-muted">Calificaciones</div>
                                    <div class="fs-4"><?= (int)$estadisticas['total'] ?></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 border rounded">
                                    <div class="text-muted">Promedio final</div>
                                    <div class="fs-4"><?= number_format((float)$estadisticas['promedio'], 2) ?></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 border rounded">
                                    <div class="text-muted">Aprobadas</div>
                                    <div class="fs-4"><?= (int)$estadisticas['aprobados'] ?></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 border rounded">
                                    <div class="text-muted">Reprobadas</div>
                                    <div class="fs-4"><?= (int)$estadisticas['reprobados'] ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <h6>Aprobadas/Reprobadas</h6>
                                <canvas id="kpi-donut" height="160"></canvas>
                            </div>
                            <div class="col-md-6">
                                <h6>Promedio por ciclo</h6>
                                <canvas id="kpi-ciclo" height="160"></canvas>
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <h6 class="mb-0">Top 5 materias por promedio <?= $cicloSel !== '' ? '(' . htmlspecialchars($cicloSel) . ')' : '' ?></h6>
                          <div class="d-flex gap-2">
                             <button class="btn btn-outline-primary btn-sm" data-export="csv" data-target="#tabla-top-materias" data-filename="top_materias.csv" data-timestamp="true"><i class="bi bi-filetype-csv"></i> Exportar CSV</button>
                             <button class="btn btn-outline-secondary btn-sm" data-export="pdf" data-target="#tabla-top-materias"><i class="bi bi-filetype-pdf"></i> Exportar PDF</button>
                          </div>
                        </div>
                        <div class="table-responsive print-list">
                          <table id="tabla-top-materias" class="table table-striped table-hover">
                            <thead>
                              <tr>
                                <th>Materia</th>
                                <th>Clave</th>
                                <th>Promedio</th>
                                <th>Registros</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php
                                $topMaterias = $cicloSeleccionado !== '' ? $controlCal->promediosPorMateria($cicloSeleccionado) : $controlCal->promediosPorMateria(null);
                                usort($topMaterias, function($a,$b){ return ($b['promedio'] <=> $a['promedio']); });
                                $topMaterias = array_slice($topMaterias, 0, 5);
                                foreach ($topMaterias as $tm):
                              ?>
                                <tr>
                                  <td><?= htmlspecialchars($tm['nombre'] ?? '') ?></td>
                                  <td><?= htmlspecialchars($tm['clave'] ?? '') ?></td>
                                  <td><?= htmlspecialchars(number_format((float)($tm['promedio'] ?? 0), 2)) ?></td>
                                  <td><?= htmlspecialchars((string)($tm['count'] ?? 0)) ?></td>
                                </tr>
                              <?php endforeach; ?>
                            </tbody>
                          </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        </main>
    </div>

    <!-- Modal Centro de Ayuda -->
    <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="helpModalLabel">
              <i class="bi bi-question-circle me-2"></i>Centro de Ayuda - Dashboard
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6">
                <h6><i class="bi bi-speedometer2 me-2"></i>Indicadores Clave</h6>
                <ul class="list-unstyled">
                  <li><strong>Total de Calificaciones:</strong> Número total de evaluaciones registradas</li>
                  <li><strong>Promedio General:</strong> Promedio de todas las calificaciones</li>
                  <li><strong>Aprobados/Reprobados:</strong> Distribución de resultados académicos</li>
                </ul>
                
                <h6 class="mt-3"><i class="bi bi-graph-up me-2"></i>Gráficas</h6>
                <ul class="list-unstyled">
                  <li><strong>Gráfica Circular:</strong> Proporción de aprobados vs reprobados</li>
                  <li><strong>Promedio por Ciclo:</strong> Evolución del rendimiento académico</li>
                </ul>
              </div>
              <div class="col-md-6">
                <h6><i class="bi bi-funnel me-2"></i>Filtros</h6>
                <ul class="list-unstyled">
                  <li><strong>Ciclo Académico:</strong> Filtra datos por período específico</li>
                  <li><strong>Exportar:</strong> Descarga reportes en Excel</li>
                </ul>
                
                <h6 class="mt-3"><i class="bi bi-lightning me-2"></i>Acciones Rápidas</h6>
                <ul class="list-unstyled">
                  <li><strong>Inscribir Materias:</strong> Proceso de inscripción de asignaturas</li>
                  <li><strong>Ver Kardex:</strong> Historial académico completo</li>
                  <li><strong>Mi Perfil:</strong> Información personal y académica</li>
                </ul>
              </div>
            </div>
            
            <div class="alert alert-info mt-3">
              <i class="bi bi-info-circle me-2"></i>
              <strong>Tip:</strong> Usa el selector de ciclo para analizar el rendimiento de períodos específicos. 
              Los datos se actualizan automáticamente al cambiar el filtro.
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="assets/js/main.js"></script>
    <?php if ($isAdmin): ?>
    <script>
      (function(){
        const donutCtx = document.getElementById('kpi-donut');
        const cicloCtx = document.getElementById('kpi-ciclo');
        if (donutCtx) {
          const data = { labels:['Aprobadas','Reprobadas'], datasets:[{ data:[<?= (int)$stats['aprobados'] ?>, <?= (int)$stats['reprobados'] ?>], backgroundColor:['#28a745','#dc3545'] }] };
          new Chart(donutCtx, { type:'doughnut', data });
        }
        if (cicloCtx) {
          const src = <?= json_encode($avgByCiclo, JSON_UNESCAPED_UNICODE) ?>;
          const labels = src.map(r => r.ciclo);
          const vals = src.map(r => r.promedio);
          const data = { labels, datasets:[{ label:'Promedio por ciclo', data:vals, backgroundColor:'#0d6efd' }] };
          const options = { scales:{ y:{ beginAtZero:true, max:100 } } };
          new Chart(cicloCtx, { type:'bar', data, options });
        }
      })();
    </script>
    <?php endif; ?>
</body>
</html>