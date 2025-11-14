<?php
header('Location: app.php?r=/dashboard');
exit;
?>
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Carga Académica</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/styles.css" rel="stylesheet">
  <link href="assets/css/desktop-fixes.css" rel="stylesheet">
</head>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>

<div class="app-shell">
  <!-- Sidebar eliminado: accesos centralizados en dashboard -->
  <main class="app-content">
    <?php $pageTitle = 'Carga Académica'; ?>
    <div class="d-flex flex-column mb-3">
      <h1 class="h3 mb-0"><?= $pageTitle ?></h1>
      <?php $breadcrumbs = [ ['label' => 'Inicio', 'url' => 'dashboard.php'], ['label' => $pageTitle, 'url' => null] ]; ?>
      <?php require __DIR__ . '/partials/breadcrumb.php'; ?>
    </div>
    <p class="text-muted">Asignación de materias y grupos para el periodo.</p>

    <div class="card">
      <div class="card-body">
        <form class="row g-3" method="post">
          <div class="col-md-4">
            <label class="form-label">Matrícula</label>
            <input type="text" name="matricula" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Materia</label>
            <input type="text" name="materia" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Grupo</label>
            <input type="text" name="grupo" class="form-control" required>
          </div>
          <div class="col-12">
            <button class="btn btn-primary" type="submit"><i class="bi bi-plus-circle"></i> Asignar</button>
          </div>
        </form>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
