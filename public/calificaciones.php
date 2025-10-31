<?php
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/models/Alumno.php';
require_once __DIR__ . '/../app/models/Grupo.php';
require_once __DIR__ . '/../app/models/Calificacion.php';

$auth = new AuthController();
$auth->requireAuth();
$user = $auth->getCurrentUser();

$grupoModel = new Grupo();
$alumnoModel = new Alumno();
$calificacionModel = new Calificacion();

$message = '';
$role = $user['rol'] ?? 'alumno';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;

// Grupos disponibles para el profesor (o todos para admin)
if ($role === 'profesor') {
    $grupos = $grupoModel->getWithJoins($page, $limit, (int)$user['id']);
} else {
    $grupos = $grupoModel->getWithJoins($page, $limit, null);
}

// Manejo de alta/actualización de calificación por profesor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!$auth->validateCSRFToken($token)) {
        http_response_code(400);
        echo 'CSRF inválido';
        exit;
    }

    if ($role !== 'profesor') {
        http_response_code(403);
        echo 'Solo profesores pueden registrar calificaciones';
        exit;
    }

    $grupoId = (int)($_POST['grupo_id'] ?? 0);
    $alumnoMatricula = trim((string)($_POST['alumno_matricula'] ?? ''));
    $parcial1 = (float)($_POST['parcial1'] ?? 0);
    $parcial2 = (float)($_POST['parcial2'] ?? 0);
    $final = (float)($_POST['final'] ?? 0);

    // Validaciones básicas
    if ($grupoId <= 0 || $alumnoMatricula === '') {
        $message = 'Grupo y matrícula del alumno son obligatorios';
    } else {
        // Verificar que el grupo pertenezca al profesor
        $esDelProfesor = false;
        foreach ($grupos as $g) {
            if ((int)$g['id'] === $grupoId) { $esDelProfesor = true; break; }
        }
        if (!$esDelProfesor) {
            $message = 'No puedes calificar grupos que no te pertenecen';
        } else {
            $alumno = $alumnoModel->findByMatricula($alumnoMatricula);
            if (!$alumno) {
                $message = 'No se encontró alumno con esa matrícula';
            } else {
                // Normalizar a rango 0-100
                $parcial1 = max(0, min(100, $parcial1));
                $parcial2 = max(0, min(100, $parcial2));
                $final = max(0, min(100, $final));

                // Upsert de calificación
                $existente = $calificacionModel->findOne((int)$alumno['id'], $grupoId);
                try {
                    if ($existente) {
                        $calificacionModel->update((int)$existente['id'], [
                            'parcial1' => $parcial1,
                            'parcial2' => $parcial2,
                            'final' => $final,
                        ]);
                        $message = 'Calificación actualizada';
                    } else {
                        $calificacionModel->create([
                            'alumno_id' => (int)$alumno['id'],
                            'grupo_id' => $grupoId,
                            'parcial1' => $parcial1,
                            'parcial2' => $parcial2,
                            'final' => $final,
                        ]);
                        $message = 'Calificación registrada';
                    }
                } catch (Throwable $e) {
                    $message = 'Error al guardar calificación: ' . $e->getMessage();
                }
            }
        }
    }
}

$csrf = $auth->generateCSRFToken();

// Listado de calificaciones
if ($role === 'profesor') {
    $calificaciones = $calificacionModel->getByProfesor((int)$user['id'], $page, $limit);
    $total = $calificacionModel->countByProfesor((int)$user['id']);
} else {
    // Admin: ver todas las calificaciones (reutilizamos getByProfesor sin filtro usando un JOIN manual)
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 10;
    $sql = "SELECT c.*, a.matricula AS alumno_matricula, a.nombre AS alumno_nombre, a.apellido AS alumno_apellido,
                    g.nombre AS grupo_nombre, g.ciclo AS grupo_ciclo, m.nombre AS materia_nombre, m.clave AS materia_clave,
                    u.email AS profesor_email, u.matricula AS profesor_matricula
             FROM calificaciones c
             JOIN alumnos a ON c.alumno_id = a.id
             JOIN grupos g ON c.grupo_id = g.id
             JOIN materias m ON g.materia_id = m.id
             JOIN usuarios u ON g.profesor_id = u.id
             LIMIT {$limit} OFFSET " . (($page - 1) * $limit);
    $pdo = (new Usuario())->getDb(); // reutilizamos conexión de manera segura
    $stmt = $pdo->query($sql);
    $calificaciones = $stmt->fetchAll();
    $stmt = $pdo->query("SELECT COUNT(*) FROM calificaciones");
    $total = (int)$stmt->fetchColumn();
}

$totalPages = max(1, (int)ceil($total / $limit));
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Calificaciones</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="/dashboard.php">Control Escolar</a>
  </div>
  <div class="container-fluid">
    <span class="navbar-text text-white"><?= htmlspecialchars(ucfirst($role)) ?></span>
  </div>
</nav>

<main class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3">Calificaciones</h1>
    <a href="/dashboard.php" class="btn btn-outline-secondary">Volver</a>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-info" role="alert"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <?php if ($role === 'profesor'): ?>
  <div class="card mb-4">
    <div class="card-header">Registrar/Actualizar Calificación</div>
    <div class="card-body">
      <form method="post" class="row g-3" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <div class="col-md-4">
          <label class="form-label">Grupo</label>
          <select name="grupo_id" class="form-select" required>
            <option value="">Seleccione...</option>
            <?php foreach ($grupos as $g): ?>
              <option value="<?= (int)$g['id'] ?>">
                <?= htmlspecialchars($g['nombre']) ?> (<?= htmlspecialchars($g['materia_nombre']) ?> - <?= htmlspecialchars($g['materia_clave']) ?> / <?= htmlspecialchars($g['ciclo']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Matrícula del Alumno</label>
          <input type="text" name="alumno_matricula" class="form-control" placeholder="A00XXYY" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Parcial 1</label>
          <input type="number" name="parcial1" class="form-control" min="0" max="100" step="0.01" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Parcial 2</label>
          <input type="number" name="parcial2" class="form-control" min="0" max="100" step="0.01" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Final</label>
          <input type="number" name="final" class="form-control" min="0" max="100" step="0.01" required>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header">Listado</div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead>
            <tr>
              <th>Alumno</th>
              <th>Matrícula</th>
              <th>Materia</th>
              <th>Grupo</th>
              <th>Ciclo</th>
              <th>Parcial 1</th>
              <th>Parcial 2</th>
              <th>Final</th>
              <?php if ($role !== 'profesor'): ?>
              <th>Profesor</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($calificaciones as $c): ?>
            <tr>
              <td><?= htmlspecialchars(($c['alumno_nombre'] ?? '') . ' ' . ($c['alumno_apellido'] ?? '')) ?></td>
              <td><?= htmlspecialchars($c['alumno_matricula'] ?? '') ?></td>
              <td><?= htmlspecialchars(($c['materia_nombre'] ?? '') . ' (' . ($c['materia_clave'] ?? '') . ')') ?></td>
              <td><?= htmlspecialchars($c['grupo_nombre'] ?? '') ?></td>
              <td><?= htmlspecialchars($c['grupo_ciclo'] ?? '') ?></td>
              <td><?= htmlspecialchars((string)$c['parcial1']) ?></td>
              <td><?= htmlspecialchars((string)$c['parcial2']) ?></td>
              <td><?= htmlspecialchars((string)$c['final']) ?></td>
              <?php if ($role !== 'profesor'): ?>
              <td><?= htmlspecialchars(($c['profesor_email'] ?? '') . ' (' . ($c['profesor_matricula'] ?? '') . ')') ?></td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <nav>
        <ul class="pagination">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
          </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>