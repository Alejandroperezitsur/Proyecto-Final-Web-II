<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Pendientes</h3>
    <a href="<?php echo $base; ?>/dashboard" class="btn btn-sm btn-outline-secondary">Volver</a>
  </div>
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped table-hover table-sm">
          <thead><tr><th>Ciclo</th><th>Materia</th><th>Grupo</th></tr></thead>
          <tbody>
            <?php if (!empty($rows)): foreach ($rows as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['ciclo']) ?></td>
              <td><?= htmlspecialchars($r['materia']) ?></td>
              <td><?= htmlspecialchars($r['grupo']) ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="3" class="text-muted">No tienes evaluaciones pendientes.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
