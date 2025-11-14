<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<h2 class="mb-4">Mi Tablero</h2>

<div class="row g-3">
  <div class="col-md-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <i class="fa-solid fa-chart-line fa-2x me-3 text-primary"></i>
          <div>
            <div class="small">Promedio General</div>
            <div class="h4 mb-0" id="stat-promedio">—</div>
          </div>
        </div>
        <div class="progress mt-3" style="height: 20px;">
          <div id="promedio-bar" class="progress-bar" role="progressbar" style="width: 0%">0%</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <i class="fa-solid fa-book-open fa-2x me-3 text-success"></i>
          <div>
            <div class="small">Materias Cursadas</div>
            <div class="h4 mb-0" id="stat-total">—</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <i class="fa-solid fa-hourglass-half fa-2x me-3 text-warning"></i>
          <div>
            <div class="small">Pendientes</div>
            <div class="h4 mb-0" id="stat-pendientes">—</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-3">
  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="card-title mb-0">Mi Carga Académica</h5>
          <input type="text" id="carga-filter" class="form-control form-control-sm" style="max-width: 240px" placeholder="Filtrar por materia/grupo">
        </div>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead><tr><th>Ciclo</th><th>Materia</th><th>Grupo</th><th class="text-end">Estado</th><th class="text-end">Calificación</th></tr></thead>
            <tbody id="carga-tbody"><tr><td colspan="5" class="text-muted">Cargando...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3">Gráfica de Rendimiento</h5>
        <canvas id="chart-rendimiento" height="120"></canvas>
      </div>
    </div>
  </div>
  <div class="col-12">
    <div id="no-records" class="alert alert-info d-none">No hay registros disponibles.</div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Cargar estadísticas
fetch('<?php echo $base; ?>/api/alumno/estadisticas').then(r=>r.json()).then(resp=>{
  const d = resp.data || {};
  const prom = Number(d.promedio ?? 0);
  document.getElementById('stat-promedio').textContent = prom.toFixed(2);
  document.getElementById('stat-total').textContent = d.total ?? 0;
  document.getElementById('stat-pendientes').textContent = d.pendientes ?? 0;
  const pct = Math.min(100, Math.max(0, Math.round(prom * 10)));
  const bar = document.getElementById('promedio-bar');
  bar.style.width = pct + '%';
  bar.textContent = pct + '%';
  bar.classList.remove('bg-danger','bg-warning','bg-success');
  bar.classList.add(pct < 60 ? 'bg-danger' : pct < 80 ? 'bg-warning' : 'bg-success');
});

// Cargar tabla Kardex/Carga
let cargaRows = [];
const tbody = document.getElementById('carga-tbody');
const noRec = document.getElementById('no-records');
function renderCarga(){
  const q = (document.getElementById('carga-filter').value || '').toLowerCase();
  const rows = cargaRows.filter(x => (x.materia||'').toLowerCase().includes(q) || (x.grupo||'').toLowerCase().includes(q));
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-muted">Sin resultados</td></tr>';
  } else {
    tbody.innerHTML = rows.map(x => `<tr>
      <td>${x.ciclo ?? ''}</td>
      <td>${x.materia ?? ''}</td>
      <td>${x.grupo ?? ''}</td>
      <td class="text-end">${x.estado ?? ''}</td>
      <td class="text-end">${x.calificacion ?? ''}</td>
    </tr>`).join('');
  }
}
fetch('<?php echo $base; ?>/api/alumno/carga').then(r=>r.json()).then(resp=>{
  const data = resp.data || [];
  cargaRows = Array.isArray(data) ? data : [];
  if (cargaRows.length === 0) { noRec.classList.remove('d-none'); }
  renderCarga();
});
document.getElementById('carga-filter').addEventListener('input', renderCarga);

// Gráfica Chart.js
fetch('<?php echo $base; ?>/api/alumno/chart').then(r=>r.json()).then(resp=>{
  const d = resp.data || { labels: [], data: [] };
  const labels = d.labels || [];
  const data = (d.data || []).map(Number);
  const ctx = document.getElementById('chart-rendimiento').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: { labels, datasets: [{ label: 'Promedio por ciclo', data, borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,0.2)' }] },
    options: { scales: { y: { beginAtZero: true, suggestedMax: 100 } } }
  });
});
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
