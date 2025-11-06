<div class="row" style="margin-bottom:12px">
  <a class="export-btn" href="?route=admin/export/pdf&mode=dashboard">📊 Exportar PDF</a>
  <a class="export-btn" href="?route=admin/export/excel&mode=dashboard">📈 Exportar Excel</a>
</div>
<div class="grid cols-3">
  <div class="card">
    <h3>Estado general</h3>
    <div class="badges-row" style="margin-top:6px">
      <span class="badge cyan"><?= (int)$stats['alumnos'] ?> Alumnos 👨‍🎓</span>
      <span class="badge magenta"><?= (int)$stats['profesores'] ?> Profesores 👩‍🏫</span>
      <span class="badge violet"><?= (int)$stats['grupos'] ?> Grupos 🧩</span>
      <span class="badge <?= !empty($reinscripcion_activa) ? 'badge-activo' : 'badge-inactivo' ?>">Reinscripción <?= !empty($reinscripcion_activa) ? 'ACTIVA' : 'INACTIVA' ?> 🔁</span>
    </div>
  </div>
  <div class="card">
    <h3>Tarjetas</h3>
    <div class="status"><span class="badge blue">Carreras</span> <strong><?= $stats['carreras'] ?></strong></div>
    <div class="status"><span class="badge blue">Alumnos</span> <strong><?= $stats['alumnos'] ?></strong></div>
    <div class="status"><span class="badge blue">Profesores</span> <strong><?= $stats['profesores'] ?></strong></div>
    <div class="status"><span class="badge blue">Materias</span> <strong><?= $stats['materias'] ?></strong></div>
    <div class="status"><span class="badge blue">Grupos</span> <strong><?= $stats['grupos'] ?></strong></div>
    <div class="status"><span class="badge green">Promedio global</span> <strong><?= number_format((float)$promedio_global,2) ?></strong></div>
  </div>
  <div class="card">
    <h3>Administración</h3>
    <p><a class="btn" href="?route=admin/crud&entity=carreras">Carreras</a> <span class="badge gray"><?= (int)$stats['carreras'] ?></span></p>
    <p><a class="btn" href="?route=admin/crud&entity=materias">Materias</a> <span class="badge gray"><?= (int)$stats['materias'] ?></span></p>
    <p><a class="btn" href="?route=admin/crud&entity=grupos">Grupos</a> <span class="badge gray"><?= (int)$stats['grupos'] ?></span></p>
    <p><a class="btn" href="?route=admin/crud&entity=alumnos">Alumnos</a> <span class="badge gray"><?= (int)$stats['alumnos'] ?></span></p>
    <p><a class="btn" href="?route=admin/crud&entity=profesores">Profesores</a> <span class="badge gray"><?= (int)$stats['profesores'] ?></span></p>
  </div>
  <div class="card">
    <h3>Control de reinscripción</h3>
    <div class="status">
      Estado: 
      <?php if(!empty($reinscripcion_activa)): ?>
        <span class="status-activo">Activa</span>
      <?php else: ?>
        <span class="status-inactivo">Inactiva</span>
      <?php endif; ?>
    </div>
    <p style="margin-top:8px">
      <a class="btn" href="?route=admin/toggleReinscripcion"><i class="fa fa-rotate"></i> Activar / Desactivar</a>
    </p>
  </div>
  <div class="card">
    <h3>Alumnos por carrera</h3>
    <?php $hasAlumnosCarrera = !empty($alumnos_por_carrera); ?>
    <?php if($hasAlumnosCarrera): ?>
      <canvas id="chartAlumnosCarrera"></canvas>
    <?php else: ?>
      <div class="message">Sin datos</div>
    <?php endif; ?>
  </div>
</div>
<div class="grid cols-3" style="margin-top:16px">
  <div class="card">
    <h3>Profesores por carrera</h3>
    <?php $hasProfesoresCarrera = !empty($profesores_por_carrera); ?>
    <?php if($hasProfesoresCarrera): ?>
      <canvas id="chartProfesoresCarrera"></canvas>
    <?php else: ?>
      <div class="message">Sin datos</div>
    <?php endif; ?>
  </div>
  <div class="card">
    <h3>Promedio global</h3>
    <?php $hasPromGlobal = isset($promedio_global) && (float)$promedio_global > 0; ?>
    <?php if($hasPromGlobal): ?>
      <canvas id="chartPromedioGlobal"></canvas>
    <?php else: ?>
      <div class="message">Sin datos</div>
    <?php endif; ?>
  </div>
</div>

<script>
const alumnosData = <?= json_encode(array_map(function($r){return ['label'=>$r['carrera'],'value'=>(int)$r['total']];}, $alumnos_por_carrera ?? [])) ?>;
const profesoresData = <?= json_encode(array_map(function($r){return ['label'=>$r['carrera'],'value'=>(int)$r['total']];}, $profesores_por_carrera ?? [])) ?>;
const labelsAl = alumnosData.map(d=>d.label);
const valuesAl = alumnosData.map(d=>d.value);
const labelsPr = profesoresData.map(d=>d.label);
const valuesPr = profesoresData.map(d=>d.value);
const accent = '#00bcd4'; const accent2 = '#2196f3'; const text = '#f5f5f5';
const grid = '#2a2a2a';
const cAl = document.getElementById('chartAlumnosCarrera');
if (cAl) {
  new Chart(cAl,{
    type:'bar',
    data:{labels:labelsAl,datasets:[{label:'Alumnos',data:valuesAl,backgroundColor:accent}]},
    options:{responsive:true,plugins:{legend:{labels:{color:text}},tooltip:{callbacks:{label:(ctx)=>`Alumnos: ${ctx.raw}`}}},scales:{x:{ticks:{color:text},grid:{color:grid}},y:{ticks:{color:text},grid:{color:grid}}},animation:{duration:1200,easing:'easeOutQuart'}}
  });
}
const cPr = document.getElementById('chartProfesoresCarrera');
if (cPr) {
  new Chart(cPr,{
    type:'bar',
    data:{labels:labelsPr,datasets:[{label:'Profesores',data:valuesPr,backgroundColor:accent2}]},
    options:{responsive:true,plugins:{legend:{labels:{color:text}},tooltip:{callbacks:{label:(ctx)=>`Profesores: ${ctx.raw}`}}},scales:{x:{ticks:{color:text},grid:{color:grid}},y:{ticks:{color:text},grid:{color:grid}}},animation:{duration:1200,easing:'easeOutQuart'}}
  });
}
const prom = <?= json_encode((float)($promedio_global ?? 0)) ?>;
const cProm = document.getElementById('chartPromedioGlobal');
if (cProm) {
  new Chart(cProm,{
    type:'doughnut',
    data:{labels:['Promedio','Resto'],datasets:[{data:[prom, Math.max(0,100-prom)],backgroundColor:[accent, '#153244']}]},
    options:{responsive:true,plugins:{legend:{labels:{color:text}},tooltip:{callbacks:{label:(ctx)=>`${ctx.label}: ${ctx.raw}`}}},animation:{duration:1200,easing:'easeOutQuart'}}
  });
}

// Sección: Estadísticas generales
</script>

<div class="grid cols-3" style="margin-top:16px">
  <div class="card">
    <h3>📊 Estadísticas generales</h3>
    <canvas id="statsChart"></canvas>
  </div>
  <div class="card">
    <h3>Actividad de Reinscripciones (últimos 6 meses)</h3>
    <canvas id="reinsChart"></canvas>
  </div>
</div>

<script>
// Distribución del Sistema (doughnut)
<?php $sysTotal = (int)$stats['alumnos'] + (int)$stats['profesores'] + (int)$stats['grupos'] + (int)$stats['materias']; ?>
<?php if($sysTotal > 0): ?>
  new Chart(document.getElementById('statsChart'),{
    type:'doughnut',
    data:{
      labels:['Alumnos','Profesores','Grupos','Materias'],
      datasets:[{
        data:[<?= (int)$stats['alumnos'] ?>, <?= (int)$stats['profesores'] ?>, <?= (int)$stats['grupos'] ?>, <?= (int)$stats['materias'] ?>],
        backgroundColor:['#00e5ff','#e040fb','#7c4dff','#00c853']
      }]
    },
    options:{
      responsive:true,
      plugins:{
        legend:{labels:{color:text}},
        title:{display:true,text:'Distribución del Sistema',color:text},
        tooltip:{callbacks:{label:(ctx)=>`${ctx.label}: ${ctx.raw}`}}
      },
      animation:{duration:1200,easing:'easeOutQuart'}
    }
  });
<?php else: ?>
  document.getElementById('statsChart')?.remove();
<?php endif; ?>

// Actividad de Reinscripciones (line) - datos de ejemplo por falta de timestamp en inscripciones
new Chart(document.getElementById('reinsChart'),{
  type:'line',
  data:{
    labels:['Jun','Jul','Ago','Sep','Oct','Nov'],
    datasets:[{
      label:'Reinscripciones',
      data:[12,25,18,32,45,38],
      borderColor:'#00e676',
      tension:0.3,
      fill:true,
      backgroundColor:'rgba(0,230,118,0.2)'
    }]
  },
  options:{
    responsive:true,
    plugins:{
      legend:{labels:{color:text}},
      title:{display:true,text:'Reinscripciones recientes',color:text}
    },
    scales:{x:{ticks:{color:text},grid:{color:grid}},y:{ticks:{color:text},grid:{color:grid}}}
  }
});
</script>