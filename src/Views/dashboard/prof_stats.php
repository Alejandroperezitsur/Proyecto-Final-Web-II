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
    const vals = (j.data.data || []).map(Number);
    const bg = vals.map(v => (isNaN(v) ? 'rgba(108,117,125,0.4)' : (v >= 70 ? 'rgba(25,135,84,0.5)' : 'rgba(220,53,69,0.5)')));
    const border = vals.map(v => (isNaN(v) ? '#6c757d' : (v >= 70 ? '#198754' : '#dc3545')));
    new Chart(ctx, {
      type: 'bar',
      data: { labels: j.data.labels, datasets: [{ label: 'Promedio por grupo', data: vals, backgroundColor: bg, borderColor: border, borderWidth: 1 }] },
      options: { plugins: { tooltip: { enabled: true }, legend: { display: true } }, scales: { y: { beginAtZero: true, suggestedMax: 100, max: 100 } } }
    });
  });
</script>
