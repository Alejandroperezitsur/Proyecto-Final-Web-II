<div class="grid cols-3">
  <div class="card">
    <h3>Resumen</h3>
    <div class="status"><span class="badge green">Aprobadas</span> <strong><?= $stats['aprobadas'] ?></strong></div>
    <div class="status"><span class="badge blue">Cursando</span> <strong><?= $stats['cursando'] ?></strong></div>
    <div class="status"><span class="badge gray">Pendientes</span> <strong><?= $stats['pendientes'] ?></strong></div>
  </div>
  <div class="card">
    <h3>Acciones</h3>
    <p>
      <a class="btn" href="<?= \Core\Url::route('student/cardex') ?>"><i class="fa fa-list"></i> Ver Cardex</a>
    </p>
    <p>
      <a class="btn" href="<?= \Core\Url::route('student/grades') ?>"><i class="fa fa-star"></i> Calificaciones actuales</a>
    </p>
    <p>
      <a class="btn" href="<?= \Core\Url::route('student/schedule') ?>"><i class="fa fa-calendar"></i> Horario escolar</a>
    </p>
    <p>
      <a class="btn" href="<?= \Core\Url::route('student/reticula') ?>"><i class="fa fa-diagram-project"></i> Retícula</a>
    </p>
  </div>
  <div class="card">
    <h3>Reinscripción</h3>
    <?php if(!empty($reinscripcion_activa)): ?>
      <div class="status"><span class="status-activo">Periodo de reinscripción ACTIVO</span></div>
      <form method="post" action="<?= \Core\Url::route('student/reinscripcion') ?>">
        <input type="hidden" name="csrf_token" value="<?= \Core\Security::csrfToken() ?>" />
        <p>Selecciona materias disponibles:</p>
        <div id="materias-select"></div>
        <button class="btn" type="submit" onclick="return confirm('¿Confirmar reinscripción?')"><i class="fa fa-check"></i> Reinscribirme</button>
      </form>

    <?php else: ?>
      <div class="status"><span class="status-inactivo">La reinscripción aún no está disponible</span></div>
      <p class="text-muted">Vuelve más tarde. Te avisaremos cuando se active.</p>
    <?php endif; ?>
  </div>
</div>
  <div class="grid cols-3 mt-16">
  <div class="card">
    <h3>Indicadores</h3>
    <div class="status"><span class="badge green">Promedio actual</span> <strong><?= number_format((float)($promedio_actual ?? 0),2) ?></strong></div>
    <div class="status"><span class="badge cyan">Aprobadas</span> <strong><?= $stats['aprobadas'] ?></strong></div>
    <div class="status"><span class="badge blue">Créditos</span> <strong><?= $creditos ?? $stats['aprobadas'] ?></strong></div>
    <div class="status"><span class="badge green">Avance %</span> <strong><?= number_format((float)($avance_porcentaje ?? 0),2) ?>%</strong></div>
  </div>
  <div class="card">
    <h3>Progreso en retícula</h3>
    <canvas id="chartProgreso"></canvas>
  </div>
  <div class="card">
    <h3>Historial de promedios por semestre</h3>
    <canvas id="chartHistorial"></canvas>
  </div>
</div>

  <div class="grid cols-3 mt-16">
  <div class="card">
    <h3>📊 Mi Progreso Académico</h3>
    <div class="progress-info">
      <p>Total de materias: <?= (int)($totalMaterias ?? 0) ?></p>
      <p>Aprobadas: <?= (int)($materiasAprobadas ?? 0) ?></p>
      <p>Pendientes: <?= (int)($materiasPendientes ?? 0) ?></p>
      <p>Promedio general: <?= isset($promedio) ? number_format((float)$promedio,2) : 'N/A' ?></p>
    </div>
    <?php
      $total = (int)($totalMaterias ?? 0);
      $aprobadas = (int)($materiasAprobadas ?? 0);
      $pct = $total > 0 ? round(($aprobadas / max(1,$total)) * 100) : 0;
      $labelTxt = $pct >= 80 ? 'Progreso sobresaliente' : ($pct >= 60 ? 'Progreso aceptable' : 'Necesita mejorar');
      $labelClass = $pct >= 80 ? 'good' : ($pct >= 60 ? 'okay' : 'bad');
    ?>
    <div class="progress-label <?= $labelClass ?>"><?= $labelTxt ?><?= $total>0 ? " ({$pct}%)" : '' ?></div>
    <?php if($total>0): ?>
      <canvas id="studentChart"></canvas>
    <?php else: ?>
      <div class="message">Sin materias inscritas</div>
    <?php endif; ?>
  </div>
</div>

<script>
const accent = '#2e7d32'; const accent2 = '#2196f3'; const text = '#f5f5f5'; const grid = '#2a2a2a';
const aprob = <?= json_encode((int)$stats['aprobadas']) ?>;
const total = <?= json_encode((int)($total_materias ?? 0)) ?>;
new Chart(document.getElementById('chartProgreso'),{
  type:'doughnut',
  data:{labels:['Aprobadas','Pendientes'],datasets:[{data:[aprob, Math.max(0,total-aprob)],backgroundColor:[accent,'#153244']}]},
  options:{responsive:true,plugins:{legend:{labels:{color:text}}}}
});
const historial = <?= json_encode(array_map(function($r){return ['label'=>(int)$r['semestre'],'value'=>(float)$r['promedio']];}, $historial ?? [])) ?>;
new Chart(document.getElementById('chartHistorial'),{
  type:'line',
  data:{labels:historial.map(d=>`S${d.label}`),datasets:[{label:'Promedio',data:historial.map(d=>d.value),borderColor:accent2,backgroundColor:'rgba(33,150,243,0.2)',tension:0.2}]},
  options:{responsive:true,plugins:{legend:{labels:{color:text}}},scales:{x:{ticks:{color:text},grid:{color:grid}},y:{ticks:{color:text},grid:{color:grid}}}}
});

// Progreso académico personalizado (dona) con tooltips
const sCanvas = document.getElementById('studentChart');
if (sCanvas) {
  const sctx = sCanvas.getContext('2d');
  new Chart(sctx, {
    type: 'doughnut',
    data: {
      labels: ['Aprobadas', 'Pendientes'],
      datasets: [{
        data: [<?= (int)($materiasAprobadas ?? 0) ?>, <?= (int)($materiasPendientes ?? 0) ?>],
        backgroundColor: ['#00e676', '#ff5252']
      }]
    },
    options: {
      plugins: {
        legend: { labels: { color: '#fff' } },
        title: { display: true, text: 'Progreso académico', color: '#fff' },
        tooltip: {
          callbacks: {
            label: (context) => `${context.label}: ${context.raw}`
          }
        }
      }
    }
  });
}
</script>