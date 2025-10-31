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
  <title>Monitoreo de Grupos</title>
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
    <h1 class="h3 mb-3">Monitoreo de Grupos</h1>
    <p class="text-muted">Estado de grupos, ocupación y asignaciones.</p>

  <div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-end mb-3 gap-2">
          <button class="btn btn-outline-primary" data-export="csv" data-target="#tabla-monitoreo" data-filename="monitoreo_grupos.csv"><i class="bi bi-filetype-csv"></i> Exportar CSV</button>
          <button class="btn btn-outline-secondary" data-export="pdf"><i class="bi bi-filetype-pdf"></i> Exportar PDF</button>
        </div>
        <div class="table-responsive">
          <table id="tabla-monitoreo" class="table table-striped table-hover">
            <thead>
              <tr>
                <th>Grupo</th>
                <th>Materia</th>
                <th>Profesor</th>
                <th>Cupos</th>
                <th>Inscritos</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>ISC-101</td>
                <td>Programación I</td>
                <td>prof001@univ.edu</td>
                <td>40</td>
                <td>35</td>
                <td><span class="badge bg-success">Abierto</span></td>
              </tr>
              <tr>
                <td>ISC-201</td>
                <td>Estructuras de Datos</td>
                <td>prof002@univ.edu</td>
                <td>35</td>
                <td>35</td>
                <td><span class="badge bg-warning">Lleno</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>