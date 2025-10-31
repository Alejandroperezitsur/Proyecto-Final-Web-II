<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/models/Grupo.php';
require_once __DIR__ . '/../app/models/Calificacion.php';

$auth = new AuthController();
$auth->requireAuth();

$currentUser = $auth->getCurrentUser();
$isAdmin = $_SESSION['user_role'] === 'admin';
// KPIs globales (solo admin)
$calModel = new Calificacion();
$stats = $calModel->getGlobalAggregates();
$avgByCiclo = $calModel->getAveragesByCiclo();
// Catálogo de ciclos y filtros
$grupoModel = new Grupo();
$ciclosCatalog = $grupoModel->getDistinctCiclos(null);
$cicloSel = trim((string)($_GET['ciclo'] ?? ''));
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
    <title>Dashboard - Control Escolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" 
          rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" 
          rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Control Escolar</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                    data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if ($isAdmin): ?>
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
        <h2 class="mb-4">Dashboard</h2>
        <?php if ($isAdmin): ?>
        <form class="row g-3 mb-3" method="get" action="dashboard.php">
            <div class="col-md-4">
                <label class="form-label">Filtrar por ciclo</label>
                <select name="ciclo" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($ciclosCatalog as $c): $val = trim((string)($c['ciclo'] ?? $c)); ?>
                      <option value="<?= htmlspecialchars($val) ?>" <?= $val === $cicloSel ? 'selected' : '' ?>><?= htmlspecialchars($val) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 align-self-end">
                <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Aplicar</button>
                <a href="dashboard.php" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
        <?php endif; ?>
        <div class="row g-4">
            <?php if ($isAdmin): ?>
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
                            <?= $isAdmin ? 'Administra los grupos y horarios.' : 
                                         'Ver tus grupos asignados.' ?>
                        </p>
                        <a href="grupos.php" class="btn btn-warning">
                            <?= $isAdmin ? 'Administrar' : 'Ver Grupos' ?>
                        </a>
                    </div>
                </div>
            </div>

            <?php if (!$isAdmin): ?>
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

            <?php if ($isAdmin): ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-header">KPIs Globales</div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <div class="p-3 border rounded">
                                    <div class="text-muted">Calificaciones</div>
                                    <div class="fs-4"><?= (int)$stats['total'] ?></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 border rounded">
                                    <div class="text-muted">Promedio final</div>
                                    <div class="fs-4"><?= number_format((float)$stats['promedio'], 2) ?></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 border rounded">
                                    <div class="text-muted">Aprobadas</div>
                                    <div class="fs-4"><?= (int)$stats['aprobados'] ?></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 border rounded">
                                    <div class="text-muted">Reprobadas</div>
                                    <div class="fs-4"><?= (int)$stats['reprobados'] ?></div>
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
                            <button class="btn btn-outline-primary" data-export="csv" data-target="#tabla-top-materias" data-filename="top_materias.csv"><i class="bi bi-filetype-csv"></i> Exportar CSV</button>
                            <button class="btn btn-outline-secondary" data-export="pdf"><i class="bi bi-filetype-pdf"></i> Exportar PDF</button>
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
                                $topMaterias = $cicloSel !== '' ? $calModel->getAveragesByMateriaForCiclo($cicloSel) : $calModel->getAveragesByMateria();
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