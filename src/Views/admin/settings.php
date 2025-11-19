<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0"><i class="fa-solid fa-sliders me-2"></i>Ajustes de siembra</h3>
  <a href="<?php echo $base; ?>/dashboard" class="btn btn-outline-secondary">Volver</a>
</div>

<form method="post" action="<?php echo $base; ?>/admin/settings/save" class="card p-3">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label">Grupos mínimos por ciclo</label>
      <input type="number" name="seed_min_groups_per_cycle" class="form-control" min="1" value="<?php echo (int)$minGroups; ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Calificaciones mínimas por grupo</label>
      <input type="number" name="seed_min_grades_per_group" class="form-control" min="1" value="<?php echo (int)$minGrades; ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Tamaño del pool de alumnos</label>
      <input type="number" name="seed_students_pool" class="form-control" min="10" value="<?php echo (int)$pool; ?>">
    </div>
  </div>
  <div class="mt-3">
    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button>
  </div>
</form>

<?php $content = ob_get_clean(); include __DIR__ . '/../../Views/layout.php'; ?>

