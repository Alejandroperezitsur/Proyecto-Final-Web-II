<?php
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/models/Usuario.php';

$auth = new AuthController();
$auth->requireAuth();
$user = $auth->getCurrentUser();
if ($user['rol'] !== 'admin') {
    http_response_code(403);
    echo 'Acceso denegado';
    exit;
}

$usuarioModel = new Usuario();
$message = '';
$error = '';
$editProfesor = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!$auth->validateCSRFToken($token)) {
        http_response_code(400);
        $error = 'CSRF inválido';
    } else {
        $action = $_POST['action'] ?? 'create';
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                try {
                    $ok = $usuarioModel->delete($id);
                    if ($ok) {
                        $message = 'Profesor eliminado correctamente';
                    } else {
                        $error = 'No se pudo eliminar el profesor (referencias existentes o error).';
                    }
                } catch (Throwable $e) {
                    $error = 'Error al eliminar: ' . $e->getMessage();
                }
            } else {
                $error = 'ID inválido para eliminar';
            }
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $matricula = trim((string)($_POST['matricula'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $password = (string)($_POST['password'] ?? '');

            if ($action === 'create') {
                if ($matricula === '' || $email === '' || $password === '') {
                    $error = 'Todos los campos son obligatorios para registrar';
                } else {
                    try {
                        $usuarioModel->create([
                            'matricula' => $matricula,
                            'email' => $email,
                            'password' => $password,
                            'rol' => 'profesor',
                            'activo' => 1,
                        ]);
                        $message = 'Profesor creado correctamente';
                    } catch (Throwable $e) {
                        $error = 'Error al crear el profesor: ' . $e->getMessage();
                    }
                }
            } else if ($action === 'update') { // update
                if ($id <= 0) {
                    $error = 'ID inválido para actualizar';
                } else {
                    $data = [
                        'matricula' => $matricula,
                        'email' => $email,
                    ];
                    if ($password !== '') {
                        $data['password'] = $password;
                    }
                    try {
                        $ok = $usuarioModel->update($id, $data);
                        if ($ok) {
                            $message = 'Profesor actualizado correctamente';
                        } else {
                            $error = 'No se pudo actualizar el profesor';
                        }
                    } catch (Throwable $e) {
                        $error = 'Error al actualizar: ' . $e->getMessage();
                    }
                }
            } else if ($action === 'toggle_active') {
                if ($id <= 0) {
                    $error = 'ID inválido para activar/desactivar';
                } else {
                    try {
                        $prof = $usuarioModel->find($id);
                        if ($prof && isset($prof['activo'])) {
                            $nuevo = (int)$prof['activo'] ? 0 : 1;
                            $ok = $usuarioModel->update($id, ['activo' => $nuevo]);
                            if ($ok) {
                                $message = $nuevo ? 'Profesor activado' : 'Profesor desactivado';
                            } else {
                                $error = 'No se pudo cambiar el estado';
                            }
                        } else {
                            $error = 'Profesor no encontrado';
                        }
                    } catch (Throwable $e) {
                        $error = 'Error al cambiar estado: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Cargar profesor para edición si corresponde
$editId = max(0, (int)($_GET['edit_id'] ?? 0));
if ($editId > 0) {
    $editProfesor = $usuarioModel->find($editId);
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$q = trim((string)($_GET['q'] ?? ''));
$estado = (string)($_GET['estado'] ?? '');
if ($estado !== '' && !in_array($estado, ['0','1'], true)) { $estado = ''; }

// Listado con búsqueda y filtro por estado
if ($q !== '') {
  if ($estado !== '') {
    $profesores = $usuarioModel->searchByRoleAndEstado('profesor', $q, $estado, $page, $limit);
    $total = $usuarioModel->countByRoleSearchAndEstado('profesor', $q, $estado);
  } else {
    $profesores = $usuarioModel->searchByRole('profesor', $q, $page, $limit);
    $total = $usuarioModel->countByRoleSearch('profesor', $q);
  }
} else {
  if ($estado !== '') {
    $profesores = $usuarioModel->getByRoleAndEstado('profesor', $estado, $page, $limit);
    $total = $usuarioModel->countByRoleAndEstado('profesor', $estado);
  } else {
    $profesores = $usuarioModel->getAllByRole('profesor', $page, $limit);
    $total = $usuarioModel->countByRole('profesor');
  }
}
$totalPages = max(1, (int)ceil($total / $limit));
$csrf = $auth->generateCSRFToken();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SICEnet · ITSUR — Profesores</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
<!-- Header institucional compacto -->
<header class="institutional-header">
  <div class="container-fluid">
    <a href="dashboard.php" class="institutional-brand">
      <img src="assets/ITSUR-LOGO.webp" alt="ITSUR Logo" class="institutional-logo">
      <div class="institutional-text">
        <h1 class="institutional-title">SICEnet · ITSUR</h1>
        <p class="institutional-subtitle">Sistema Integral de Control Escolar</p>
      </div>
    </a>
  </div>
</header>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
          <img src="assets/ITSUR-LOGO.webp" alt="ITSUR Logo" class="navbar-logo me-2">
          <span class="brand-text">SICEnet · ITSUR</span>
        </a>
        <button class="btn btn-outline-light btn-sm ms-auto me-2" id="themeToggle" title="Cambiar tema">
          <i class="bi bi-sun-fill"></i>
        </button>
  </div>
  <div class="container-fluid">
    <span class="navbar-text text-white">Admin</span>
  </div>
 </nav>

<div class="app-shell">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="app-content">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 mb-0">Profesores</h1>
      <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-0">
          <li class="breadcrumb-item"><a href="dashboard.php">Inicio</a></li>
          <li class="breadcrumb-item active" aria-current="page">Profesores</li>
        </ol>
      </nav>
      <div class="text-muted small">Mostrando <?= count($profesores) ?> de <?= (int)$total ?></div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <form method="get" class="d-flex align-items-center" role="search">
        <input type="hidden" name="page" value="1">
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control" placeholder="Buscar por matrícula o email">
        <select name="estado" class="form-select ms-2" style="max-width: 180px;">
          <option value="" <?= $estado === '' ? 'selected' : '' ?>>Todos</option>
          <option value="1" <?= $estado === '1' ? 'selected' : '' ?>>Activos</option>
          <option value="0" <?= $estado === '0' ? 'selected' : '' ?>>Inactivos</option>
        </select>
        <button class="btn btn-outline-primary ms-2" type="submit">Buscar</button>
        <?php if ($q !== '' || $estado !== ''): ?>
          <a href="profesores.php" class="btn btn-outline-secondary ms-2">Limpiar</a>
        <?php endif; ?>
      </form>
        <a href="dashboard.php" class="btn btn-outline-secondary">Volver</a>
    </div>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-success" role="alert"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card mb-4">
    <div class="card-header"><?= $editProfesor ? 'Editar Profesor' : 'Agregar Profesor' ?></div>
    <div class="card-body">
      <form method="post" class="row g-3" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <?php if ($editProfesor): ?>
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?= (int)$editProfesor['id'] ?>">
        <?php else: ?>
          <input type="hidden" name="action" value="create">
        <?php endif; ?>
        <div class="col-md-3">
          <label class="form-label">Matrícula</label>
          <input type="text" name="matricula" class="form-control" required value="<?= $editProfesor ? htmlspecialchars($editProfesor['matricula']) : '' ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required value="<?= $editProfesor ? htmlspecialchars($editProfesor['email']) : '' ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Contraseña <?= $editProfesor ? '(dejar en blanco para conservar)' : '' ?></label>
          <input type="password" name="password" class="form-control" <?= $editProfesor ? '' : 'required' ?>>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary"><?= $editProfesor ? 'Guardar cambios' : 'Guardar' ?></button>
          <?php if ($editProfesor): ?>
            <a href="profesores.php" class="btn btn-secondary ms-2">Cancelar</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header">Listado</div>
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
        <div class="flex-grow-1" style="max-width: 320px;">
          <input type="text" class="form-control" placeholder="Filtrar rápido en la tabla" data-quick-filter-for="#tabla-profesores">
        </div>
         <button class="btn btn-outline-primary btn-sm" data-export="csv" data-target="#tabla-profesores" data-filename="profesores.csv" data-timestamp="true"><i class="bi bi-filetype-csv"></i> Exportar CSV</button>
         <button class="btn btn-outline-secondary btn-sm" data-export="pdf" data-target="#tabla-profesores"><i class="bi bi-filetype-pdf"></i> Exportar PDF</button>
      </div>
      <div class="table-responsive">
        <table id="tabla-profesores" class="table table-striped table-hover table-sort">
          <thead>
            <tr>
              <th>ID</th>
              <th>Matrícula</th>
              <th>Email</th>
              <th>Activo</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php if (count($profesores) === 0): ?>
            <tr class="empty-state-row"><td colspan="6" class="text-center text-muted">No hay resultados</td></tr>
          <?php endif; ?>
          <?php foreach ($profesores as $p): ?>
            <tr>
              <td><?= (int)$p['id'] ?></td>
              <td><?= htmlspecialchars($p['matricula']) ?></td>
              <td><?= htmlspecialchars($p['email']) ?></td>
              <td><?= isset($p['activo']) ? ((int)$p['activo'] ? 'Sí' : 'No') : 'Sí' ?></td>
              <td>
                <a class="btn btn-sm btn-outline-primary" href="profesores.php?edit_id=<?= (int)$p['id'] ?>">Editar</a>
                <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar este profesor?');">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                </form>
                <form method="post" class="d-inline" onsubmit="return confirm('¿Cambiar estado de este profesor?');">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="action" value="toggle_active">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <?php $isActive = isset($p['activo']) ? (int)$p['activo'] : 1; ?>
                  <button type="submit" class="btn btn-sm <?= $isActive ? 'btn-outline-warning' : 'btn-outline-success' ?>">
                    <?= $isActive ? 'Desactivar' : 'Activar' ?>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <nav>
        <ul class="pagination">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?><?= $estado !== '' ? '&estado=' . urlencode($estado) : '' ?>"><?= $i ?></a>
          </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>
  </div>
</main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>