<?php $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/'); ?>
<div class="mt-4">
  <div class="card">
    <div class="card-body">
      <h5 class="card-title mb-3">Promedios por Ciclo</h5>
      <canvas id="chart-ciclo" height="120"></canvas>
    </div>
  </div>
</div>

<div class="mt-4">
  <div class="card">
    <div class="card-body">
      <h5 class="card-title mb-3">Porcentaje de Reprobados por Materia</h5>
      <canvas id="chart-reprobados" height="120"></canvas>
    </div>
  </div>
</div>

<script>
// Promedios por ciclo (línea)
fetch('<?php echo $base; ?>/api/charts/promedios-ciclo')
  .then(r=>r.json())
  .then(j=>{
    if (!j.ok) return;
    const ctx = document.getElementById('chart-ciclo').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: { labels: j.data.labels, datasets: [{ label: 'Promedio por ciclo', data: j.data.data, borderColor: '#198754', backgroundColor: 'rgba(25,135,84,0.2)' }] },
      options: { plugins: { tooltip: { enabled: true }, legend: { display: true } }, scales: { y: { beginAtZero: true, suggestedMax: 10 } } }
    });
  });

// Reprobados por materia (barra)
fetch('<?php echo $base; ?>/api/charts/reprobados')
  .then(r=>r.json())
  .then(j=>{
    if (!j.ok) return;
    const ctx = document.getElementById('chart-reprobados').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: { labels: j.data.labels, datasets: [{ label: '% Reprobados', data: j.data.data, backgroundColor: '#dc3545' }] },
      options: { plugins: { tooltip: { enabled: true }, legend: { display: true } }, scales: { y: { beginAtZero: true, suggestedMax: 100 } } }
    });
  });
</script>
