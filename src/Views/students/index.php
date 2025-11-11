<?php
// Expect $students
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Alumnos</h3>
    <a href="/dashboard" class="btn btn-outline-secondary">Volver al Dashboard</a>
  </div>
  <div class="table-responsive">
    <table class="table table-striped table-hover">
      <thead>
        <tr>
          <th>ID</th>
          <th>Matrícula</th>
          <th>Nombre</th>
          <th>Apellido</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($students as $s): ?>
          <tr>
            <td><?= htmlspecialchars($s['id']) ?></td>
            <td><?= htmlspecialchars($s['matricula']) ?></td>
            <td><?= htmlspecialchars($s['nombre']) ?></td>
            <td><?= htmlspecialchars($s['apellido']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="mt-3">
    <a class="btn btn-primary" href="/alumnos?page=<?= max(1, (int)($_GET['page'] ?? 1) - 1) ?>">Anterior</a>
    <a class="btn btn-primary" href="/alumnos?page=<?= (int)($_GET['page'] ?? 1) + 1 ?>">Siguiente</a>
  </div>
</div>