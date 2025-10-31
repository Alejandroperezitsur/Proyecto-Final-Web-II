<?php
require_once __DIR__ . '/../app/controllers/AuthController.php';

$auth = new AuthController();
$auth->requireAuth();
$user = $auth->getCurrentUser();
if ($user['rol'] !== 'admin') {
  http_response_code(403);
  echo 'Acceso denegado';
  exit;
}
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
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="/dashboard.php">Control Escolar</a>
  </div>
  <div class="container-fluid">
    <span class="navbar-text text-white">Admin</span>
  </div>
</nav>

<div class="app-shell">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="app-content">
    <h1 class="h3 mb-3">Carga Académica</h1>
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