<?php $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/'); ?>
<div class="mt-4">
  <div class="card">
    <div class="card-body">
      <h5 class="card-title mb-3">Comparativa de Promedio por Grupo</h5>
      <canvas id="chart-grupos" height="120"></canvas>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
fetch('<?php echo $base; ?>/api/charts/desempeño-grupo')
  .then(r=>r.json())
  .then(j=>{
    if (!j.ok) return;
    const ctx = document.getElementById('chart-grupos').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: { labels: j.data.labels, datasets: [{ label: 'Promedio por grupo', data: j.data.data, backgroundColor: '#0d6efd' }] },
      options: { plugins: { tooltip: { enabled: true }, legend: { display: true } }, scales: { y: { beginAtZero: true, suggestedMax: 10 } } }
    });
  });
</script>
