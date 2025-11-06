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
  <p><a class="btn" href="<?= \Core\Url::route('professor/groups') ?>"><i class="fa fa-people-group"></i> Ver grupos</a></p>
  </div>
  <div class="card">
    <h3>Promedio por grupo</h3>
    <canvas id="chartPromediosGrupo" data-pg='<?= htmlspecialchars(json_encode(array_map(function($r){return ["label"=>$r["grupo"],"value"=>(float)$r["promedio"]];}, $promedios_por_grupo ?? [])), ENT_QUOTES, 'UTF-8') ?>'></canvas>
  </div>
</div>
<div class="grid cols-3 mt-16">
  <div class="card">
    <h3>Distribución de calificaciones</h3>
    <canvas id="chartDistribucion" data-dist='<?= htmlspecialchars(json_encode($distribucion ?? ["reprobados"=>0,"aprobados"=>0,"destacados"=>0]), ENT_QUOTES, 'UTF-8') ?>'></canvas>
  </div>
  <div class="card">
    <h3>📈 Estadísticas del profesor</h3>
    <div class="prof-stats">
      <p>Grupos activos: <?= (int)($totalGrupos ?? 0) ?></p>
      <p>Alumnos totales: <?= (int)($totalAlumnos ?? 0) ?></p>
      <p>Promedio general: <?= isset($promedioGeneral) ? number_format((float)$promedioGeneral,2) : 'N/A' ?></p>
    </div>
    <?php if((int)($totalGrupos ?? 0) > 0): ?>
      <canvas id="profChart" data-promedio='<?= htmlspecialchars(json_encode((float)($promedioGeneral ?? 0)), ENT_QUOTES, 'UTF-8') ?>'></canvas>
    <?php else: ?>
      <div class="message">Sin grupos activos</div>
    <?php endif; ?>
  </div>
  <div class="card">
    <h3>📈 Grupos por materia</h3>
    <canvas id="teacherChart" data-gpm='<?= htmlspecialchars(json_encode(array_map(function($r){return ["label"=>$r["materia"],"value"=>(int)$r["total"]];}, $grupos_por_materia ?? [])), ENT_QUOTES, 'UTF-8') ?>'></canvas>
  </div>
</div>