<?php
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/models/Alumno.php';
require_once __DIR__ . '/../app/models/Grupo.php';
require_once __DIR__ . '/../app/models/Materia.php';
require_once __DIR__ . '/../app/models/SavedView.php';
require_once __DIR__ . '/../app/models/Calificacion.php';

$auth = new AuthController();
$auth->requireAuth();
$user = $auth->getCurrentUser();

$grupoModel = new Grupo();
$materiaModel = new Materia();
$savedViewModel = new SavedView();
$alumnoModel = new Alumno();
$calificacionModel = new Calificacion();

$message = '';
$role = $user['rol'] ?? 'alumno';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;

// Grupos disponibles para el profesor (o todos para admin)
if ($role === 'profesor') {
    $grupos = $grupoModel->getWithJoins($page, $limit, (int)$user['id']);
} else {
    $grupos = $grupoModel->getWithJoins($page, $limit, null);
}

// Manejo de alta/actualización de calificación por profesor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!$auth->validateCSRFToken($token)) {
        http_response_code(400);
        echo 'CSRF inválido';
        exit;
    }

    if ($role !== 'profesor') {
        http_response_code(403);
        echo 'Solo profesores pueden registrar calificaciones';
        exit;
    }

    $grupoId = (int)($_POST['grupo_id'] ?? 0);
    $alumnoMatricula = trim((string)($_POST['alumno_matricula'] ?? ''));
    $parcial1 = (float)($_POST['parcial1'] ?? 0);
    $parcial2 = (float)($_POST['parcial2'] ?? 0);
    $final = (float)($_POST['final'] ?? 0);

    // Validaciones básicas
    if ($grupoId <= 0 || $alumnoMatricula === '') {
        $message = 'Grupo y matrícula del alumno son obligatorios';
    } else {
        // Verificar que el grupo pertenezca al profesor
        $esDelProfesor = false;
        foreach ($grupos as $g) {
            if ((int)$g['id'] === $grupoId) { $esDelProfesor = true; break; }
        }
        if (!$esDelProfesor) {
            $message = 'No puedes calificar grupos que no te pertenecen';
        } else {
            $alumno = $alumnoModel->findByMatricula($alumnoMatricula);
            if (!$alumno) {
                $message = 'No se encontró alumno con esa matrícula';
            } else {
                // Normalizar a rango 0-100
                $parcial1 = max(0, min(100, $parcial1));
                $parcial2 = max(0, min(100, $parcial2));
                $final = max(0, min(100, $final));

                // Upsert de calificación
                $existente = $calificacionModel->findOne((int)$alumno['id'], $grupoId);
                try {
                    if ($existente) {
                        $calificacionModel->update((int)$existente['id'], [
                            'parcial1' => $parcial1,
                            'parcial2' => $parcial2,
                            'final' => $final,
                        ]);
                        $message = 'Calificación actualizada';
                    } else {
                        $calificacionModel->create([
                            'alumno_id' => (int)$alumno['id'],
                            'grupo_id' => $grupoId,
                            'parcial1' => $parcial1,
                            'parcial2' => $parcial2,
                            'final' => $final,
                        ]);
                        $message = 'Calificación registrada';
                    }
                } catch (Throwable $e) {
                    $message = 'Error al guardar calificación: ' . $e->getMessage();
                }
            }
        }
    }
}

$csrf = $auth->generateCSRFToken();

// Listado de calificaciones
if ($role === 'profesor') {
    $calificaciones = $calificacionModel->getByProfesor((int)$user['id'], $page, $limit);
    $total = $calificacionModel->countByProfesor((int)$user['id']);
} else {
    // Admin: ver todas las calificaciones (reutilizamos getByProfesor sin filtro usando un JOIN manual)
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 10;
    $sql = "SELECT c.*, a.matricula AS alumno_matricula, a.nombre AS alumno_nombre, a.apellido AS alumno_apellido,
                    g.nombre AS grupo_nombre, g.ciclo AS grupo_ciclo, m.nombre AS materia_nombre, m.clave AS materia_clave,
                    u.email AS profesor_email, u.matricula AS profesor_matricula
             FROM calificaciones c
             JOIN alumnos a ON c.alumno_id = a.id
             JOIN grupos g ON c.grupo_id = g.id
             JOIN materias m ON g.materia_id = m.id
             JOIN usuarios u ON g.profesor_id = u.id
             LIMIT {$limit} OFFSET " . (($page - 1) * $limit);
    $pdo = (new Usuario())->getDb(); // reutilizamos conexión de manera segura
    $stmt = $pdo->query($sql);
    $calificaciones = $stmt->fetchAll();
    $stmt = $pdo->query("SELECT COUNT(*) FROM calificaciones");
    $total = (int)$stmt->fetchColumn();
}

$totalPages = max(1, (int)ceil($total / $limit));

// Catálogos para filtros
$materiasCatalog = $materiaModel->getCatalog();
$ciclosCatalog = $grupoModel->getDistinctCiclos($role === 'profesor' ? (int)$user['id'] : null);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SICEnet · ITSUR — Calificaciones</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/styles.css" rel="stylesheet">
  <style>
  @media print {
    body * { visibility: hidden; }
    .print-list, .print-list * { visibility: visible; }
    .print-list { position: absolute; left:0; top:0; width:100%; }
    .btn, .form-control, .form-select, nav, header, .app-sidebar { display:none !important; }
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
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
          <img src="assets/ITSUR-LOGO.webp" alt="ITSUR Logo" class="navbar-logo me-2">
          <span class="brand-text">SICEnet · ITSUR</span>
        </a>
        <button class="btn btn-outline-light btn-sm ms-auto me-2" id="themeToggle" title="Cambiar tema">
          <i class="bi bi-sun-fill"></i>
        </button>
  </div>
  <div class="container-fluid">
    <span class="navbar-text text-white"><?= htmlspecialchars(ucfirst($role)) ?></span>
  </div>
</nav>

<div class="app-shell">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="app-content">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3">Calificaciones</h1>
        <a href="dashboard.php" class="btn btn-outline-secondary">Volver</a>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-info" role="alert"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <?php if ($role === 'profesor'): ?>
  <div class="card mb-4">
    <div class="card-header">Registrar/Actualizar Calificación</div>
    <div class="card-body">
      <form method="post" class="row g-3" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <div class="col-md-4">
          <label class="form-label">Grupo</label>
          <select name="grupo_id" class="form-select" required>
            <option value="">Seleccione...</option>
            <?php foreach ($grupos as $g): ?>
              <option value="<?= (int)$g['id'] ?>">
                <?= htmlspecialchars($g['nombre']) ?> (<?= htmlspecialchars($g['materia_nombre']) ?> - <?= htmlspecialchars($g['materia_clave']) ?> / <?= htmlspecialchars($g['ciclo']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Matrícula del Alumno</label>
          <input type="text" name="alumno_matricula" class="form-control" placeholder="A00XXYY" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Parcial 1</label>
          <input type="number" name="parcial1" class="form-control" min="0" max="100" step="0.01" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Parcial 2</label>
          <input type="number" name="parcial2" class="form-control" min="0" max="100" step="0.01" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Final</label>
          <input type="number" name="final" class="form-control" min="0" max="100" step="0.01" required>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header">Listado</div>
    <div class="card-body">
      <div class="row g-3 mb-3">
        <div class="col-md-3">
          <label class="form-label">Materia</label>
          <input list="dlc-materias" class="form-control" id="fc-materia" placeholder="Nombre o clave">
          <datalist id="dlc-materias">
            <?php foreach ($materiasCatalog as $m):
              $label = trim((string)($m['nombre'] ?? ''));
              $clave = trim((string)($m['clave'] ?? ''));
              $opt = $label . ($clave !== '' ? " ($clave)" : '');
              if ($opt === '') continue;
            ?>
              <option value="<?= htmlspecialchars($opt) ?>"></option>
            <?php endforeach; ?>
          </datalist>
        </div>
        <div class="col-md-2">
          <label class="form-label">Ciclo</label>
          <select class="form-select" id="fc-ciclo">
            <option value="">Todos</option>
            <?php foreach ($ciclosCatalog as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Alumno</label>
          <input type="text" class="form-control" id="fc-alumno" placeholder="Nombre o matrícula">
        </div>
        <?php if ($role !== 'profesor'): ?>
        <div class="col-md-2">
          <label class="form-label">Profesor</label>
          <input type="text" class="form-control" id="fc-profesor" placeholder="Email o matrícula">
        </div>
        <?php endif; ?>
        <div class="col-md-1">
          <label class="form-label">Final min</label>
          <input type="number" class="form-control" id="fc-min" min="0" max="100" step="0.01">
        </div>
        <div class="col-md-1">
          <label class="form-label">Final max</label>
          <input type="number" class="form-control" id="fc-max" min="0" max="100" step="0.01">
        </div>
        <div class="col-md-1 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="fc-aprobado">
            <label class="form-check-label" for="fc-aprobado">Aprobados (≥ 70)</label>
          </div>
        </div>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <input type="text" class="form-control" id="fc-nombre-vista" placeholder="Nombre de vista (opcional)">
        </div>
        <div class="col-md-2">
          <button class="btn btn-outline-primary w-100" id="fc-guardar-vista"><i class="bi bi-save"></i> Guardar filtros</button>
        </div>
        <div class="col-md-3">
          <select class="form-select" id="fc-vistas"><option value="">Cargar vista...</option></select>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button class="btn btn-outline-secondary flex-fill" id="fc-limpiar"><i class="bi bi-x-circle"></i> Limpiar</button>
          <button class="btn btn-outline-success flex-fill" data-bs-toggle="collapse" data-bs-target="#server-views">Vistas servidor</button>
        </div>
      </div>
      <div class="collapse" id="server-views">
        <div class="card card-body">
          <form id="server-views-form" method="post" class="row g-3" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="save_server_view" value="1">
            <div class="col-md-4">
              <label class="form-label">Nombre</label>
              <input type="text" class="form-control" name="label" placeholder="Ej. Aprobados por ciclo actual">
            </div>
            <div class="col-md-4">
              <label class="form-label">Vistas guardadas</label>
              <select class="form-select" id="fc-vistas-srv">
                <option value="">Selecciona...</option>
                <?php foreach ($serverViews as $sv): ?>
                  <option value='<?= htmlspecialchars($sv['data_json'] ?? '{}') ?>'><?= htmlspecialchars($sv['label'] ?? '') ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4 d-flex align-items-end gap-2">
              <button type="submit" class="btn btn-success"><i class="bi bi-cloud-check"></i> Guardar vista en servidor</button>
              <button type="button" class="btn btn-outline-primary" id="btn-cargar-srv"><i class="bi bi-cloud-download"></i> Cargar vista</button>
            </div>
          </form>
        </div>
      </div>
      <div class="alert alert-secondary" id="fc-totales" role="alert">
        <strong>Totales:</strong>
        Registros: <span id="fc-count">0</span> · Promedio final: <span id="fc-prom">0</span> · Aprobadas: <span id="fc-aprob">0</span> · Reprobadas: <span id="fc-reprob">0</span>
      </div>
      <div class="d-flex justify-content-end mb-3 gap-2">
        <button class="btn btn-outline-primary" data-export="csv" data-target="#tabla-calificaciones" data-filename="calificaciones.csv"><i class="bi bi-filetype-csv"></i> Exportar CSV</button>
        <button class="btn btn-outline-secondary" data-export="pdf"><i class="bi bi-filetype-pdf"></i> Exportar PDF</button>
      </div>
      <div class="table-responsive print-list">
        <table id="tabla-calificaciones" class="table table-striped table-hover">
          <thead>
            <tr>
              <th>Alumno</th>
              <th>Matrícula</th>
              <th>Materia</th>
              <th>Grupo</th>
              <th>Ciclo</th>
              <th>Parcial 1</th>
              <th>Parcial 2</th>
              <th>Final</th>
              <?php if ($role !== 'profesor'): ?>
              <th>Profesor</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($calificaciones as $c): ?>
            <tr>
              <td><?= htmlspecialchars(($c['alumno_nombre'] ?? '') . ' ' . ($c['alumno_apellido'] ?? '')) ?></td>
              <td><?= htmlspecialchars($c['alumno_matricula'] ?? '') ?></td>
              <td><?= htmlspecialchars(($c['materia_nombre'] ?? '') . ' (' . ($c['materia_clave'] ?? '') . ')') ?></td>
              <td><?= htmlspecialchars($c['grupo_nombre'] ?? '') ?></td>
              <td><?= htmlspecialchars($c['grupo_ciclo'] ?? '') ?></td>
              <td><?= htmlspecialchars((string)$c['parcial1']) ?></td>
              <td><?= htmlspecialchars((string)$c['parcial2']) ?></td>
              <td><?= htmlspecialchars((string)$c['final']) ?></td>
              <?php if ($role !== 'profesor'): ?>
              <td><?= htmlspecialchars(($c['profesor_email'] ?? '') . ' (' . ($c['profesor_matricula'] ?? '') . ')') ?></td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <nav>
        <ul class="pagination">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
          </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>
  </div>
</main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
  (function(){
    const tabla = document.getElementById('tabla-calificaciones');
    const inputs = {
      materia: document.getElementById('fc-materia'),
      ciclo: document.getElementById('fc-ciclo'),
      alumno: document.getElementById('fc-alumno'),
      profesor: document.getElementById('fc-profesor'),
      min: document.getElementById('fc-min'),
      max: document.getElementById('fc-max'),
      aprobado: document.getElementById('fc-aprobado')
    };
    const vistasSel = document.getElementById('fc-vistas');
    const nombreVista = document.getElementById('fc-nombre-vista');
    const storageKey = 'calificaciones_filters_global';

    // Inserta contenedor de totales y espacio para gráficas si no existen
    const totalesHtml = `
      <div class="alert alert-secondary" id="fc-totales" role="alert">
        Registros: <span id="fc-count">0</span> · Promedio final: <span id="fc-prom">0</span> · Aprobadas: <span id="fc-aprob">0</span> · Reprobadas: <span id="fc-reprob">0</span>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-md-6"><canvas id="fc-donut-chart" height="140"></canvas></div>
        <div class="col-md-6"><canvas id="fc-ciclo-chart" height="140"></canvas></div>
      </div>`;
    const listingCardBody = document.querySelector('.card .card-body');
    if (listingCardBody && !document.getElementById('fc-totales')) {
      listingCardBody.insertAdjacentHTML('beforeend', totalesHtml);
    }

    function filtrar() {
      let count = 0, sumFinal = 0, aprob = 0, reprob = 0;
      const materiaQ = (inputs.materia?.value || '').toLowerCase();
      const cicloQ = (inputs.ciclo?.value || '').toLowerCase();
      const alumnoQ = (inputs.alumno?.value || '').toLowerCase();
      const profesorQ = (inputs.profesor?.value || '').toLowerCase();
      const min = inputs.min?.value !== '' ? parseFloat(inputs.min.value) : -Infinity;
      const max = inputs.max?.value !== '' ? parseFloat(inputs.max.value) : Infinity;
      const onlyAprob = !!(inputs.aprobado && inputs.aprobado.checked);

      const rows = Array.from(tabla.querySelectorAll('tbody tr'));
      const sumPorCiclo = {};
      rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        const alumno = ((cells[0]?.textContent || '') + ' ' + (cells[1]?.textContent || '')).toLowerCase();
        const materia = (cells[2]?.textContent || '').toLowerCase();
        const grupo = (cells[3]?.textContent || '').toLowerCase();
        const ciclo = (cells[4]?.textContent || '').toLowerCase();
        const finalStr = (cells[7]?.textContent || '').trim();
        const final = finalStr === '' ? NaN : parseFloat(finalStr);
        const profesor = cells[8] ? (cells[8].textContent || '').toLowerCase() : '';

        let visible = true;
        if (materiaQ && !materia.includes(materiaQ)) visible = false;
        if (cicloQ && !ciclo.includes(cicloQ)) visible = false;
        if (alumnoQ && !alumno.includes(alumnoQ)) visible = false;
        if (profesorQ && !profesor.includes(profesorQ)) visible = false;
        if (!isNaN(final)) {
          if (final < min || final > max) visible = false;
          if (onlyAprob && final < 70) visible = false;
        } else {
          if (!isFinite(min) || !isFinite(max) || onlyAprob) visible = false;
        }

        row.style.display = visible ? '' : 'none';
        if (visible && !isNaN(final)) {
          count++; sumFinal += final; aprob += (final >= 70) ? 1 : 0; reprob += (final < 70) ? 1 : 0;
          const cicloKey = (cells[4]?.textContent || '').trim();
          if (!sumPorCiclo[cicloKey]) sumPorCiclo[cicloKey] = {count:0,sum:0};
          sumPorCiclo[cicloKey].count++; sumPorCiclo[cicloKey].sum += final;
        }
      });

      document.getElementById('fc-count').textContent = String(count);
      document.getElementById('fc-prom').textContent = count ? (sumFinal / count).toFixed(2) : '0';
      document.getElementById('fc-aprob').textContent = String(aprob);
      document.getElementById('fc-reprob').textContent = String(reprob);

      updateChart(aprob, reprob);
      updateBarChart(sumPorCiclo);
    }

    Object.values(inputs).forEach(el => el && el.addEventListener('input', filtrar));
    // poblar selects/datalist
    const materias = TableUtils.collectUniqueColumnValues(tabla, 2);
    const dl = document.getElementById('dlc-materias');
    materias.forEach(m => { const opt = document.createElement('option'); opt.value = m; dl.appendChild(opt); });
    const ciclos = TableUtils.collectUniqueColumnValues(tabla, 4);
    const cicloSel = document.getElementById('fc-ciclo');
    ciclos.forEach(c => { const opt = document.createElement('option'); opt.value = c; opt.textContent = c; cicloSel.appendChild(opt); });

    // ordenar por encabezado
    TableUtils.enableTableSort(tabla);

    // guardado de filtros
    document.getElementById('fc-guardar-vista').addEventListener('click', (e)=>{
      e.preventDefault();
      TableUtils.saveFilters(Object.values(inputs), storageKey, nombreVista.value.trim());
      refreshVistas();
    });
    document.getElementById('fc-limpiar').addEventListener('click', (e)=>{
      e.preventDefault(); Object.values(inputs).forEach(el=>{ if(el.type==='checkbox') el.checked=false; else el.value=''; }); filtrar();
    });
    vistasSel.addEventListener('change', ()=>{
      const list = TableUtils.loadFilterList(storageKey);
      const idx = parseInt(vistasSel.value);
      if (!isNaN(idx) && list[idx]) { TableUtils.applyFilters(Object.values(inputs), list[idx]); }
    });
    document.getElementById('btn-cargar-srv')?.addEventListener('click', ()=>{
      const sel = document.getElementById('fc-vistas-srv');
      try {
        const json = sel?.value || '{}';
        const data = JSON.parse(json);
        TableUtils.applyFilters(Object.values(inputs), { data });
      } catch {}
    });
    // Antes de enviar el formulario al servidor, inyecta los filtros actuales
    document.getElementById('server-views-form')?.addEventListener('submit', (e)=>{
      const form = e.target;
      const ensure = (name, value)=>{
        let input = form.querySelector(`[name="${name}"]`);
        if (!input) { input = document.createElement('input'); input.type = 'hidden'; input.name = name; form.appendChild(input); }
        input.value = value;
      };
      ensure('fc-materia', inputs.materia?.value || '');
      ensure('fc-ciclo', inputs.ciclo?.value || '');
      ensure('fc-alumno', inputs.alumno?.value || '');
      ensure('fc-profesor', inputs.profesor?.value || '');
      ensure('fc-min', inputs.min?.value || '');
      ensure('fc-max', inputs.max?.value || '');
      ensure('fc-aprobado', inputs.aprobado?.checked ? '1' : '0');
    });
    function refreshVistas(){
      const list = TableUtils.loadFilterList(storageKey);
      vistasSel.innerHTML = '<option value="">Cargar vista...</option>';
      list.forEach((rec, i) => {
        const opt = document.createElement('option'); opt.value = String(i); opt.textContent = rec.label || ('Vista ' + (i+1)); vistasSel.appendChild(opt);
      });
    }
    let chartInstance = null;
    let barInstance = null;
    function updateChart(aprob, reprob){
      const ctx = document.getElementById('fc-donut-chart');
      if (!ctx) return;
      const data = { labels:['Aprobadas','Reprobadas'], datasets:[{ data:[aprob,reprob], backgroundColor:['#28a745','#dc3545'] }] };
      if (chartInstance) { chartInstance.data = data; chartInstance.update(); }
      else { chartInstance = new Chart(ctx, { type:'doughnut', data }); }
    }

    function updateBarChart(sumPorCiclo){
      const ctx = document.getElementById('fc-ciclo-chart');
      if (!ctx) return;
      const labels = Object.keys(sumPorCiclo);
      const dataVals = labels.map(k => {
        const rec = sumPorCiclo[k];
        return rec.count ? (rec.sum / rec.count) : 0;
      });
      const data = { labels, datasets:[{ label:'Promedio por ciclo', data:dataVals, backgroundColor:'#0d6efd' }] };
      if (barInstance) { barInstance.data = data; barInstance.update(); }
      else { barInstance = new Chart(ctx, { type:'bar', data, options:{ scales:{ y:{ beginAtZero:true, max:100 } } } }); }
    }

    refreshVistas();
    filtrar();
  })();
</script>
</body>
</html>
// Guardado de vistas de filtros en servidor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_server_view'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!$auth->validateCSRFToken($token)) {
        http_response_code(400);
        echo 'CSRF inválido';
        exit;
    }
    $label = trim((string)($_POST['label'] ?? 'Vista'));
    // Recolectar filtros actuales desde POST
    $data = [
        'fc-materia' => (string)($_POST['fc-materia'] ?? ''),
        'fc-ciclo' => (string)($_POST['fc-ciclo'] ?? ''),
        'fc-alumno' => (string)($_POST['fc-alumno'] ?? ''),
        'fc-profesor' => (string)($_POST['fc-profesor'] ?? ''),
        'fc-min' => (string)($_POST['fc-min'] ?? ''),
        'fc-max' => (string)($_POST['fc-max'] ?? ''),
        'fc-aprobado' => isset($_POST['fc-aprobado']) ? '1' : '0',
    ];
    try {
        $savedViewModel->create([
            'user_id' => (int)$user['id'],
            'page_key' => 'calificaciones',
            'label' => $label !== '' ? $label : ('Vista ' . date('Y-m-d H:i')),
            'data_json' => json_encode($data, JSON_UNESCAPED_UNICODE),
        ]);
        $message = 'Vista guardada en servidor';
    } catch (Throwable $e) {
        $message = 'No se pudo guardar la vista';
    }
}
$serverViews = $savedViewModel->getByUserAndPage((int)$user['id'], 'calificaciones');