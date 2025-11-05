<?php
require_once __DIR__ . '/../app/controllers/AuthController.php';

$auth = new AuthController();
$auth->requireAuth();
$auth->requireRole(['profesor','admin']);
$user = $auth->getCurrentUser();
$csrf = $auth->generateCSRFToken();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
  <title>SICEnet · ITSUR — Mis Grupos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/styles.css" rel="stylesheet">
  <link href="assets/css/desktop-fixes.css" rel="stylesheet">
  <style>
    .group-card { cursor: pointer; transition: box-shadow .2s ease; }
    .group-card:hover { box-shadow: 0 .25rem .75rem rgba(0,0,0,.08); }
    .saving { opacity: .6; position: relative; }
    .saving::after { content: ""; position: absolute; right: .5rem; top: 50%; width: 1rem; height: 1rem; border: 2px solid #0d6efd; border-top-color: transparent; border-radius: 50%; animation: spin .8s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .success { border-color: #198754 !important; box-shadow: 0 0 0 .2rem rgba(25,135,84,.25); }
    .error { border-color: #dc3545 !important; box-shadow: 0 0 0 .2rem rgba(220,53,69,.25); }
  </style>
</head>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>

<div class="app-shell">
  <main class="app-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <?php $pageTitle = 'Mis Grupos'; ?>
        <h1 class="h3 mb-0"><?= $pageTitle ?></h1>
        <?php $breadcrumbs = [ ['label' => 'Inicio', 'url' => 'dashboard.php'], ['label' => $pageTitle, 'url' => null] ]; ?>
        <?php require __DIR__ . '/partials/breadcrumb.php'; ?>
      </div>
      <div>
        <a href="calificaciones.php" class="btn btn-outline-primary">Calificar</a>
      </div>
    </div>

    <div class="mb-4" id="stats-profesor"></div>

    <div class="row g-3 mb-4" id="grupos-activos">
      <!-- Mis Grupos Activos -->
    </div>

    <div class="row g-3" id="grupos-list">
      <!-- Grupos del profesor (2/3/4 columnas a md/xl/xxl) -->
    </div>

    <div class="card mt-4" id="alumnos-panel" style="display:none;">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <strong id="grupo-label">Grupo</strong>
          <div class="small text-muted">Calificaciones por parcial (0–100)</div>
        </div>
        <div>
          <a href="#" id="btn-cerrar" class="btn btn-outline-secondary btn-sm">Cerrar</a>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle" id="tabla-alumnos">
            <thead>
              <tr>
                <th>Alumno</th>
                <th>Matrícula</th>
                <th>Parcial 1</th>
                <th>Parcial 2</th>
                <th>Final</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script type="module">
  import { loadProfesorStats, loadActiveGroups } from './assets/js/profesores.js';
  loadProfesorStats('#stats-profesor');
  loadActiveGroups('#grupos-activos');
</script>
<script>
  (function(){
    const list = document.getElementById('grupos-list');
    const panel = document.getElementById('alumnos-panel');
    const tbody = document.querySelector('#tabla-alumnos tbody');
    const grupoLabel = document.getElementById('grupo-label');
    const btnCerrar = document.getElementById('btn-cerrar');
    let currentGrupoId = null;

    btnCerrar.addEventListener('click', (e)=>{ e.preventDefault(); panel.style.display='none'; currentGrupoId=null; });

    function renderGrupos(rows){
      list.innerHTML = '';
      rows.forEach(g => {
        const col = document.createElement('div');
        col.className = 'col-12 col-md-6 col-xl-4 col-xxl-3';
        const card = document.createElement('div');
        card.className = 'card group-card h-100';
        card.innerHTML = `
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h5 class="card-title mb-1">${escapeHtml(g.nombre)} <span class="badge text-bg-secondary">${escapeHtml(g.ciclo)}</span></h5>
                <div class="text-muted">${escapeHtml(g.materia_nombre)} · ${escapeHtml(g.materia_clave)}</div>
              </div>
              <div class="text-muted">#${g.id}</div>
            </div>
          </div>`;
        card.addEventListener('click', ()=>{ loadAlumnos(g.id, g); });
        col.appendChild(card);
        list.appendChild(col);
      });
    }

    function loadGrupos(){
      fetch('/api/profesores/grupos')
        .then(r => r.json())
        .then(json => {
          if (json && json.success) { renderGrupos(json.data || []); }
          else { list.innerHTML = '<div class="col-12"><div class="alert alert-danger">No se pudieron cargar los grupos</div></div>'; }
        })
        .catch(()=>{ list.innerHTML = '<div class="col-12"><div class="alert alert-danger">Error de red</div></div>'; });
    }

    function loadAlumnos(grupoId, g){
      currentGrupoId = grupoId;
      grupoLabel.textContent = `${g.nombre} · ${g.materia_nombre} (${g.materia_clave}) / ${g.ciclo}`;
      panel.style.display = '';
      tbody.innerHTML = '<tr><td colspan="5" class="text-center">Cargando...</td></tr>';
      fetch(`/api/profesores/grupos/${grupoId}/alumnos`)
        .then(r => r.json())
        .then(json => {
          if (!json || !json.success) { tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">No se pudieron cargar los alumnos</td></tr>'; return; }
          renderAlumnos(json.data || [], grupoId);
        })
        .catch(()=>{ tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error de red</td></tr>'; });
    }

    function renderAlumnos(rows, grupoId){
      tbody.innerHTML = '';
      rows.forEach(a => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${escapeHtml(a.apellido || '')} ${escapeHtml(a.nombre || '')}</td>
          <td>${escapeHtml(a.matricula || '')}</td>
          <td>${inputCal('parcial1', a.parcial1, a.id, grupoId)}</td>
          <td>${inputCal('parcial2', a.parcial2, a.id, grupoId)}</td>
          <td>${inputCal('final', a.final, a.id, grupoId)}</td>
        `;
        tbody.appendChild(tr);
      });
      bindInputs();
    }

    function inputCal(parcial, val, alumnoId, grupoId){
      const v = (typeof val === 'number' && !isNaN(val)) ? val : '';
      return `<input type="number" class="form-control" min="0" max="100" step="0.01" data-parcial="${parcial}" data-alumno="${alumnoId}" data-grupo="${grupoId}" value="${v}">`;
    }

    function bindInputs(){
      tbody.querySelectorAll('input[type="number"]').forEach(inp => {
        inp.addEventListener('change', ()=>{
          const alumnoId = parseInt(inp.getAttribute('data-alumno'), 10);
          const grupoId = parseInt(inp.getAttribute('data-grupo'), 10);
          const parcial = inp.getAttribute('data-parcial');
          const val = parseFloat(inp.value);
          if (isNaN(val) || val < 0 || val > 100) {
            inp.classList.remove('success'); inp.classList.add('error');
            return;
          }
          saveCalificacion(inp, { alumno_id: alumnoId, grupo_id: grupoId, parcial, calificacion: val });
        });
      });
    }

    function saveCalificacion(inputEl, payload){
      inputEl.classList.remove('error','success'); inputEl.classList.add('saving');
      const body = new URLSearchParams();
      body.set('alumno_id', String(payload.alumno_id));
      body.set('grupo_id', String(payload.grupo_id));
      body.set('parcial', String(payload.parcial));
      body.set('calificacion', String(payload.calificacion));
      fetch('/api/profesores/calificaciones', { method:'POST', body })
        .then(r => r.json())
        .then(json => {
          inputEl.classList.remove('saving');
          if (json && json.success) {
            inputEl.classList.add('success');
            setTimeout(()=> inputEl.classList.remove('success'), 1200);
          } else {
            inputEl.classList.add('error');
          }
        })
        .catch(()=>{ inputEl.classList.remove('saving'); inputEl.classList.add('error'); });
    }

    function escapeHtml(s){
      s = (s ?? '').toString();
      return s.replace(/[&<>"]+/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]));
    }

    loadGrupos();
  })();
</script>
</body>
</html>