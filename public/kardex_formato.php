<?php
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/models/Alumno.php';

$auth = new AuthController();
$auth->requireAuth();
$user = $auth->getCurrentUser();
$role = $_SESSION['user_role'] ?? '';
$isAdmin = ($role === 'admin');
$isAlumno = ($role === 'alumno');
$isProfesor = ($role === 'profesor');
if (!$isAdmin && !$isAlumno && !$isProfesor) {
  http_response_code(403);
  echo 'Acceso denegado';
  exit;
}

$matricula = trim((string)($_GET['matricula'] ?? ''));
if ($isAlumno) {
  $matricula = $matricula !== '' ? $matricula : trim((string)($user['matricula'] ?? ''));
}

$alumnoModel = new Alumno();
$alumno = null;
$historial = [];
if ($matricula !== '') {
  $alumno = $alumnoModel->findByMatricula($matricula);
  if ($alumno) {
    $profFilter = $isProfesor ? (int)($user['id'] ?? 0) : null;
    $historial = $alumnoModel->getWithCalificaciones((int)$alumno['id'], $profFilter);
  }
}

// Cálculo de totales
$count = 0; $sumProm = 0; $aprob = 0; $reprob = 0;
foreach ($historial as $h) {
  $final = is_numeric($h['final'] ?? null) ? (float)$h['final'] : null;
  $prom = is_numeric($h['promedio'] ?? null) ? (float)$h['promedio'] : null;
  if (!is_null($final)) {
    $count++; $aprob += ($final >= 70) ? 1 : 0; $reprob += ($final < 70) ? 1 : 0;
    $sumProm += (!is_null($prom) ? $prom : $final);
  }
}
$promedioGeneral = $count ? ($sumProm / $count) : 0;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Formato oficial Kardex</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    @media print {
      .no-print { display: none !important; }
    }
    .brand-header { border-bottom: 3px solid #0d6efd; margin-bottom: 16px; }
    .brand-header h1 { font-size: 1.25rem; margin: 0; }
    .brand-header small { color: #6c757d; }
  </style>
</head>
<body class="p-4">
  <div class="brand-header d-flex justify-content-between align-items-center">
    <div>
      <h1>Universidad - Control Escolar</h1>
      <small>Formato oficial de Kardex</small>
    </div>
    <div class="text-end">
      <?php $folio = ($alumno ? (strtoupper(substr((string)$alumno['matricula'], 0, 3)) . '-' . date('Ymd') . '-' . (int)$alumno['id']) : ('KDX-' . date('Ymd-His'))); ?>
      <div><strong>Fecha:</strong> <?= htmlspecialchars(date('Y-m-d')) ?></div>
      <div><strong>Folio:</strong> <?= htmlspecialchars($folio) ?></div>
      <div><strong>Emitido por:</strong> <?= htmlspecialchars($role) ?></div>
    </div>
  </div>

  <?php if ($alumno): ?>
  <div class="mb-3">
    <h2 class="h5">Datos del alumno</h2>
    <table class="table table-sm">
      <tbody>
        <tr><th>Nombre</th><td><?= htmlspecialchars(($alumno['nombre'] ?? '') . ' ' . ($alumno['apellido'] ?? '')) ?></td></tr>
        <tr><th>Matrícula</th><td><?= htmlspecialchars($alumno['matricula'] ?? '') ?></td></tr>
        <tr><th>Email</th><td><?= htmlspecialchars($alumno['email'] ?? '') ?></td></tr>
      </tbody>
    </table>
  </div>

  <div class="mb-3">
    <h2 class="h5">Historial académico</h2>
    <div class="table-responsive">
      <table class="table table-striped table-bordered">
        <thead>
          <tr>
            <th>Ciclo</th>
            <th>Materia</th>
            <th>Clave</th>
            <th>Parcial 1</th>
            <th>Parcial 2</th>
            <th>Final</th>
            <th>Promedio</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!empty($historial)): ?>
          <?php foreach ($historial as $h): ?>
          <tr>
            <td><?= htmlspecialchars($h['grupo_ciclo'] ?? '') ?></td>
            <td><?= htmlspecialchars($h['materia'] ?? '') ?></td>
            <td><?= htmlspecialchars($h['materia_clave'] ?? '') ?></td>
            <td><?= htmlspecialchars((string)($h['parcial1'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)($h['parcial2'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)($h['final'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)($h['promedio'] ?? '')) ?></td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="7" class="text-center">Sin registros</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="alert alert-secondary" role="alert">
    <strong>Totales:</strong>
    Materias: <?= (int)$count ?> · Promedio general: <?= number_format((float)$promedioGeneral, 2) ?> · Aprobadas: <?= (int)$aprob ?> · Reprobadas: <?= (int)$reprob ?>
  </div>

  <div class="mt-4">
    <p class="small text-muted mb-1">Este documento ha sido generado electrónicamente.</p>
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <div><strong>Departamento de Control Escolar</strong></div>
        <div>Firma digital: CE-UNIV-<?= htmlspecialchars(date('Ymd')) ?></div>
      </div>
      <div class="text-end">
        <span class="badge bg-primary">Válido para uso institucional</span>
      </div>
    </div>
  </div>

  <div class="no-print mt-3">
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir / Guardar PDF</button>
    <a class="btn btn-secondary" href="kardex.php?matricula=<?= urlencode($alumno['matricula'] ?? '') ?>">Volver</a>
  </div>
  <?php else: ?>
    <div class="alert alert-warning">No se encontró alumno con la matrícula proporcionada.</div>
  <?php endif; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>