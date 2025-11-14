<?php
$role = $_SESSION['role'] ?? '';
$csrf = $_SESSION['csrf_token'] ?? '';
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Reportes Avanzados</h3>
    <a href="<?php echo $base; ?>/dashboard" class="btn btn-outline-secondary">Volver</a>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <form method="get" action="<?php echo $base; ?>/reports" class="row g-2" id="filtersForm">
        <div class="col-md-3">
          <label class="form-label">Ciclo</label>
          <input class="form-control" name="ciclo" placeholder="2024-1" pattern="^\\d{4}-(1|2)$" value="<?= htmlspecialchars($_GET['ciclo'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Grupo (ID)</label>
          <input type="number" class="form-control" name="grupo_id" min="1" value="<?= htmlspecialchars($_GET['grupo_id'] ?? '') ?>">
        </div>
        <?php if ($role === 'admin'): ?>
        <div class="col-md-3">
          <label class="form-label">Profesor (ID)</label>
          <input type="number" class="form-control" name="profesor_id" min="1" value="<?= htmlspecialchars($_GET['profesor_id'] ?? '') ?>">
        </div>
        <?php endif; ?>
        <div class="col-md-3 align-self-end d-grid">
          <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter me-1"></i> Aplicar filtros</button>
        </div>
      </form>
    </div>
  </div>

  <div class="d-flex justify-content-end mb-3 gap-2">
    <form method="post" action="<?php echo $base; ?>/reports/export/csv">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="ciclo" value="<?= htmlspecialchars($_GET['ciclo'] ?? '') ?>">
      <input type="hidden" name="grupo_id" value="<?= htmlspecialchars($_GET['grupo_id'] ?? '') ?>">
      <?php if ($role === 'admin'): ?>
        <input type="hidden" name="profesor_id" value="<?= htmlspecialchars($_GET['profesor_id'] ?? '') ?>">
      <?php endif; ?>
      <button class="btn btn-outline-primary"><i class="fa-solid fa-file-csv me-1"></i> Exportar CSV</button>
    </form>
    <form method="post" action="<?php echo $base; ?>/reports/export/pdf" target="_blank">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="ciclo" value="<?= htmlspecialchars($_GET['ciclo'] ?? '') ?>">
      <input type="hidden" name="grupo_id" value="<?= htmlspecialchars($_GET['grupo_id'] ?? '') ?>">
      <?php if ($role === 'admin'): ?>
        <input type="hidden" name="profesor_id" value="<?= htmlspecialchars($_GET['profesor_id'] ?? '') ?>">
      <?php endif; ?>
      <button class="btn btn-outline-secondary"><i class="fa-solid fa-file-pdf me-1"></i> Exportar PDF</button>
    </form>
  </div>

  <div class="row g-3">
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title">Resumen</h5>
          <div id="summaryBox" class="text-muted">Cargando…</div>
        </div>
      </div>
    </div>
    <div class="col-md-8">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title">Estadísticas</h5>
          <canvas id="chartStats" height="160"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const params = new URLSearchParams(window.location.search);
const ciclo = params.get('ciclo') || '';
const resumenUrl = '<?php echo $base; ?>/reports/summary' + (ciclo ? ('?ciclo=' + encodeURIComponent(ciclo)) : '');
fetch(resumenUrl).then(r => r.json()).then(j => {
  const box = document.getElementById('summaryBox');
  if (!j.ok) { box.textContent = j.message || 'Error'; return; }
  box.innerHTML = `
    <div class="d-flex flex-column gap-1">
      <div><strong>Promedio:</strong> ${j.data.promedio ?? '—'}</div>
      <div><strong>Reprobados:</strong> ${j.data.reprobados ?? 0} (${j.data.porcentaje_reprobados ?? 0}%)</div>
    </div>`;
});

// Selección de endpoint de gráfica
let chartUrl = '<?php echo $base; ?>/api/charts/promedios-ciclo';
<?php if ($role === 'profesor'): ?>
chartUrl = '<?php echo $base; ?>/api/charts/desempeño-grupo';
<?php endif; ?>

fetch(chartUrl).then(r => r.json()).then(j => {
  if (!j.ok) return;
  const ctx = document.getElementById('chartStats');
  const isLine = (chartUrl.includes('promedios-ciclo'));
  const config = {
    type: isLine ? 'line' : 'bar',
    data: {
      labels: j.data.labels,
      datasets: [{
        label: isLine ? 'Promedio por ciclo' : 'Promedio por grupo',
        data: j.data.data,
        borderColor: '#0d6efd',
        backgroundColor: 'rgba(13,110,253,0.2)'
      }]
    },
    options: { responsive: true, plugins: { legend: { display: true }, tooltip: { enabled: true } } }
  };
  new Chart(ctx, config);
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
