<?php
ob_start();
?>
<h2 class="mb-4">Dashboard Administrador</h2>
<div class="row g-3">
  <div class="col-md-3">
    <div class="card text-bg-primary">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <i class="fa-solid fa-users fa-2x me-3"></i>
          <div>
            <div class="small">Total Alumnos</div>
            <div class="h5 mb-0" id="kpi-alumnos">—</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-success">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <i class="fa-solid fa-book fa-2x me-3"></i>
          <div>
            <div class="small">Materias</div>
            <div class="h5 mb-0" id="kpi-materias">—</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-warning">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <i class="fa-solid fa-people-group fa-2x me-3"></i>
          <div>
            <div class="small">Grupos Activos</div>
            <div class="h5 mb-0" id="kpi-grupos">—</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-info">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <i class="fa-solid fa-chart-line fa-2x me-3"></i>
          <div>
            <div class="small">Promedio General</div>
            <div class="h5 mb-0" id="kpi-promedio">—</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="mt-4">
  <a class="btn btn-outline-secondary me-2" href="/reports/export/csv"><i class="fa-solid fa-file-csv me-1"></i> Exportar CSV</a>
  <a class="btn btn-outline-primary" href="/dashboard"><i class="fa-solid fa-arrows-rotate me-1"></i> Refrescar</a>
</div>

<div class="row g-3 mt-3">
  <div class="col-md-3">
    <div class="card text-bg-secondary">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <i class="fa-solid fa-chalkboard-teacher fa-2x me-3"></i>
          <div>
            <div class="small">Profesores Activos</div>
            <div class="h5 mb-0" id="kpi-profesores">—</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-danger">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <i class="fa-solid fa-hourglass-half fa-2x me-3"></i>
          <div>
            <div class="small">Pendientes de Evaluación</div>
            <div class="h5 mb-0" id="kpi-pendientes">—</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6"></div>
</div>

<div class="mt-4">
  <div class="card">
    <div class="card-body">
      <h5 class="card-title mb-3">Promedios por Materia</h5>
      <canvas id="chart-materias" height="120"></canvas>
    </div>
  </div>
</div>

<?php include __DIR__ . '/admin_stats.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Cargar KPIs reales
fetch('/api/kpis/admin')
  .then(r=>r.json())
  .then(d=>{
    document.getElementById('kpi-alumnos').textContent = d.alumnos ?? '—';
    document.getElementById('kpi-materias').textContent = d.materias ?? '—';
    document.getElementById('kpi-grupos').textContent = d.grupos ?? '—';
    document.getElementById('kpi-promedio').textContent = d.promedio ?? '—';
    document.getElementById('kpi-profesores').textContent = d.profesores ?? '—';
    document.getElementById('kpi-pendientes').textContent = d.pendientes_evaluacion ?? '—';
  });

// Chart.js con datos reales
fetch('/api/charts/promedios-materias')
  .then(r=>r.json())
  .then(rows=>{
    const labels = rows.map(x=>x.materia);
    const data = rows.map(x=>Number(x.promedio));
    const ctx = document.getElementById('chart-materias').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: { labels, datasets: [{ label: 'Promedio final', data, backgroundColor: '#0d6efd' }] },
      options: { scales: { y: { beginAtZero: true, suggestedMax: 10 } } }
    });
  });
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>