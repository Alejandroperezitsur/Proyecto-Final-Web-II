<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Calificaciones del Grupo</h3>
    <a href="<?php echo $base; ?>/dashboard" class="btn btn-outline-secondary">Volver</a>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <div class="row">
        <div class="col-md-3"><strong>Materia:</strong> <?= htmlspecialchars($grp['materia'] ?? '') ?></div>
        <div class="col-md-3"><strong>Grupo:</strong> <?= htmlspecialchars($grp['nombre'] ?? '') ?></div>
        <div class="col-md-3"><strong>Ciclo:</strong> <?= htmlspecialchars($grp['ciclo'] ?? '') ?></div>
        <div class="col-md-3"><strong>Profesor:</strong> <?= htmlspecialchars($grp['profesor'] ?? '') ?></div>
      </div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-striped table-hover table-sm">
      <thead>
        <tr>
          <th>Matrícula</th>
          <th>Alumno</th>
          <th class="text-end">Parcial 1</th>
          <th class="text-end">Parcial 2</th>
          <th class="text-end">Final</th>
          <th class="text-end">Promedio</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($rows)): foreach ($rows as $r): ?>
        <?php
          $p1 = isset($r['parcial1']) && $r['parcial1'] !== '' ? (float)$r['parcial1'] : null;
          $p2 = isset($r['parcial2']) && $r['parcial2'] !== '' ? (float)$r['parcial2'] : null;
          $fin = isset($r['final']) && $r['final'] !== '' ? (float)$r['final'] : null;
          $prom = isset($r['promedio']) && $r['promedio'] !== '' ? (float)$r['promedio'] : null;
          $cls = function($v){ if ($v === null) return 'text-muted'; return $v >= 70 ? 'text-success' : 'text-danger'; };
        ?>
        <tr>
          <td><?= htmlspecialchars($r['matricula'] ?? '') ?></td>
          <td><?= htmlspecialchars(($r['nombre'] ?? '') . ' ' . ($r['apellido'] ?? '')) ?></td>
          <td class="text-end"><span class="<?= $cls($p1) ?>"><?= htmlspecialchars($r['parcial1'] ?? '') ?></span></td>
          <td class="text-end"><span class="<?= $cls($p2) ?>"><?= htmlspecialchars($r['parcial2'] ?? '') ?></span></td>
          <td class="text-end"><span class="<?= $cls($fin) ?>"><?= htmlspecialchars($r['final'] ?? '') ?></span></td>
          <td class="text-end"><span class="<?= $cls($prom) ?>"><?= htmlspecialchars($r['promedio'] ?? '') ?></span></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="6" class="text-muted">No hay calificaciones registradas en este grupo.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
