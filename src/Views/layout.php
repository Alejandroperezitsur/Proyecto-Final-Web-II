<?php
$role = $_SESSION['role'] ?? 'guest';
$name = $_SESSION['name'] ?? '';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Control Escolar</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
      <a class="navbar-brand" href="/dashboard"><i class="fa-solid fa-graduation-cap me-2"></i>ITSUR</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <?php if ($role === 'admin'): ?>
            <li class="nav-item"><a class="nav-link" href="/dashboard">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="/reports">Reportes</a></li>
            <li class="nav-item"><a class="nav-link" href="/alumnos">Alumnos</a></li>
          <?php elseif ($role === 'profesor'): ?>
            <li class="nav-item"><a class="nav-link" href="/dashboard">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="/reports">Reportes</a></li>
            <li class="nav-item"><a class="nav-link" href="/grades/bulk">Carga masiva</a></li>
          <?php elseif ($role === 'alumno'): ?>
            <li class="nav-item"><a class="nav-link" href="/dashboard">Mi tablero</a></li>
          <?php endif; ?>
        </ul>
        <div class="d-flex">
          <?php if ($role !== 'guest'): ?>
            <span class="navbar-text me-3"><i class="fa-regular fa-user me-1"></i><?php echo htmlspecialchars($name ?: $role); ?></span>
            <a href="/logout" class="btn btn-outline-light">Salir</a>
          <?php else: ?>
            <a href="/login" class="btn btn-outline-light">Acceder</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>
  <main class="container py-4">
    <?php echo $content ?? ''; ?>
  </main>
  <?php if (!empty($_SESSION['flash'])): ?>
  <?php $flashType = $_SESSION['flash_type'] ?? 'primary'; $validTypes = ['primary','success','warning','danger','info']; if (!in_array($flashType, $validTypes)) { $flashType = 'primary'; } ?>
  <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
    <div class="toast show align-items-center text-bg-<?php echo htmlspecialchars($flashType); ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          <?php echo htmlspecialchars($_SESSION['flash']); ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  </div>
  <?php unset($_SESSION['flash'], $_SESSION['flash_type']); endif; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>