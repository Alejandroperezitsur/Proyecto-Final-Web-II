<div class="grid cols-3">
  <div class="card">
    <h3>Resumen</h3>
    <div class="status"><span class="badge blue">Grupos</span> <strong><?= $stats['grupos'] ?></strong></div>
    <div class="status"><span class="badge green">Alumnos</span> <strong><?= $stats['alumnos'] ?></strong></div>
    <div class="status"><span class="badge cyan">Materias asignadas</span> <strong><?= $materias_asignadas ?? 0 ?></strong></div>
    <div class="status"><span class="badge green">Promedio total</span> <strong><?= number_format((float)($promedio_total ?? 0),2) ?></strong></div>
  </div>
  <div class="card">
    <h3>Acciones</h3>
  <p><a class="btn" href="?route=professor/groups"><i class="fa fa-people-group"></i> Ver grupos</a></p>
  </div>
  <div class="card">
    <h3>Promedio por grupo</h3>
    <canvas id="chartPromediosGrupo"></canvas>
  </div>
</div>
<div class="grid cols-3" style="margin-top:16px">
  <div class="card">
    <h3>Distribución de calificaciones</h3>
    <canvas id="chartDistribucion"></canvas>
  </div>
  <div class="card">
    <h3>📈 Estadísticas del profesor</h3>
    <div class="prof-stats">
      <p>Grupos activos: <?= (int)($totalGrupos ?? 0) ?></p>
      <p>Alumnos totales: <?= (int)($totalAlumnos ?? 0) ?></p>
      <p>Promedio general: <?= isset($promedioGeneral) ? number_format((float)$promedioGeneral,2) : 'N/A' ?></p>
    </div>
    <?php if((int)($totalGrupos ?? 0) > 0): ?>
      <canvas id="profChart"></canvas>
    <?php else: ?>
      <div class="message">Sin grupos activos</div>
    <?php endif; ?>
  </div>
  <div class="card">
    <h3>📈 Grupos por materia</h3>
    <canvas id="teacherChart"></canvas>
  </div>
</div>

<script>
const pg = <?= json_encode(array_map(function($r){return ['label'=>$r['grupo'],'value'=>(float)$r['promedio']];}, $promedios_por_grupo ?? [])) ?>;
const labelsPG = pg.map(d=>d.label); const valuesPG = pg.map(d=>d.value);
const accent = '#00bcd4'; const accent2 = '#2196f3'; const text = '#f5f5f5'; const grid = '#2a2a2a';
new Chart(document.getElementById('chartPromediosGrupo'),{
  type:'bar',
  data:{labels:labelsPG,datasets:[{label:'Promedio',data:valuesPG,backgroundColor:accent2}]},
  options:{responsive:true,plugins:{legend:{labels:{color:text}}},scales:{x:{ticks:{color:text},grid:{color:grid}},y:{ticks:{color:text},grid:{color:grid}}}}
});
const dist = <?= json_encode($distribucion ?? ['reprobados'=>0,'aprobados'=>0,'destacados'=>0]) ?>;
new Chart(document.getElementById('chartDistribucion'),{
  type:'doughnut',
  data:{labels:['Reprobados','Aprobados','Destacados'],datasets:[{data:[dist.reprobados,dist.aprobados,dist.destacados],backgroundColor:['#e53935',accent,'#43a047']}]},
  options:{responsive:true,plugins:{legend:{labels:{color:text}}}}
});

// Promedio general del profesor (bar) con color adaptativo, tooltips y animación
const pCanvas = document.getElementById('profChart');
if (pCanvas) {
  const pgeneral = <?= json_encode((float)($promedioGeneral ?? 0)) ?>;
  const pgeneral10 = pgeneral > 10 ? pgeneral/10 : pgeneral;
  const avgColor = pgeneral10 >= 8.5 ? '#43a047' : (pgeneral10 >= 7 ? '#fbc02d' : '#e53935');
  new Chart(pCanvas,{
    type:'bar',
    data:{labels:['Promedio General'],datasets:[{label:'Calificación promedio',data:[pgeneral10],backgroundColor:avgColor}]},
    options:{
      scales:{y:{beginAtZero:true,max:10,ticks:{color:text},grid:{color:grid}},x:{ticks:{color:text},grid:{color:grid}}},
      plugins:{legend:{display:false},title:{display:true,text:'Promedio general de grupos',color:text},tooltip:{callbacks:{label:(ctx)=>`Promedio: ${ctx.raw}`}}},
      animation:{duration:1200,easing:'easeOutQuart'},
      responsive:true
    }
  });
}

// Grupos por materia (bar)
const gpm = <?= json_encode(array_map(function($r){return ['label'=>$r['materia'],'value'=>(int)$r['total']];}, $grupos_por_materia ?? [])) ?>;
new Chart(document.getElementById('teacherChart'),{
  type:'bar',
  data:{labels:gpm.map(d=>d.label),datasets:[{label:'Grupos',data:gpm.map(d=>d.value),backgroundColor:accent}]},
  options:{responsive:true,plugins:{legend:{labels:{color:text}}},scales:{x:{ticks:{color:text},grid:{color:grid}},y:{ticks:{color:text},grid:{color:grid}}}}
});
</script>