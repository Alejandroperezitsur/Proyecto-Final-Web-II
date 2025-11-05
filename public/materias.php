<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/capas/negocio/ControlMaterias.php';

use App\Capas\Negocio\ControlMaterias;

$auth = new AuthController();
$auth->requireAuth();
$auth->requireRole(['admin']);
$usuario = $auth->getCurrentUser();

$controlMaterias = new ControlMaterias();
$msg = '';
$error = '';
// Para edición
$editMateria = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validación CSRF
    $tokenPost = $_POST['csrf_token'] ?? '';
    if (!$auth->validateCSRFToken($tokenPost)) {
        $error = 'Token CSRF inválido';
    } else {
        $action = $_POST['action'] ?? 'create';
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                $error = 'ID inválido';
            } else {
                try {
                    if (method_exists($controlMaterias, 'eliminar')) {
                        $controlMaterias->eliminar($id);
                    } else {
                        require_once __DIR__ . '/../app/models/Materia.php';
                        $m = new \Materia();
                        $m->delete($id);
                    }
                    $msg = 'Materia eliminada';
                } catch (Throwable $e) {
                    $error = 'No se pudo eliminar (referencias existentes)';
                }
            }
        } else {
            $nombre = trim((string)filter_input(INPUT_POST, 'nombre', FILTER_UNSAFE_RAW));
            $clave = trim((string)filter_input(INPUT_POST, 'clave', FILTER_UNSAFE_RAW));
            if ($nombre === '') {
                $error = 'El nombre es requerido';
            } else {
                try {
                    if ($action === 'update') {
                        $id = (int)($_POST['id'] ?? 0);
                        if ($id <= 0) {
                            $error = 'ID inválido';
                        } else {
                            if (method_exists($controlMaterias, 'actualizar')) {
                                $controlMaterias->actualizar($id, ['nombre' => $nombre, 'clave' => $clave ?: null]);
                            } else {
                                require_once __DIR__ . '/../app/models/Materia.php';
                                $m = new \Materia();
                                $m->update($id, ['nombre' => $nombre, 'clave' => $clave ?: null]);
                            }
                            $msg = 'Materia actualizada correctamente';
                        }
                    } else {
                        if (method_exists($controlMaterias, 'crear')) {
                            $controlMaterias->crear(['nombre' => $nombre, 'clave' => $clave ?: null]);
                        } else {
                            require_once __DIR__ . '/../app/models/Materia.php';
                            $m = new \Materia();
                            $m->create(['nombre' => $nombre, 'clave' => $clave ?: null]);
                        }
                        $msg = 'Materia creada correctamente';
                    }
                } catch (Throwable $e) {
                    $error = 'Error al guardar la materia';
                }
            }
        }
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$materias = null;
if (method_exists($controlMaterias, 'listar')) {
    $materias = $controlMaterias->listar($page, $limit);
} else {
    require_once __DIR__ . '/../app/models/Materia.php';
    $mm = new \Materia();
    $materias = $mm->getAll($page, $limit);
}
$total = null;
if (method_exists($controlMaterias, 'count')) {
    $total = $controlMaterias->count('');
} else {
    if (!isset($mm)) { require_once __DIR__ . '/../app/models/Materia.php'; $mm = new \Materia(); }
    $total = $mm->count('');
}
$totalPages = max(1, (int)ceil($total / $limit));
$csrf_token = $auth->generateCSRFToken();
// Cargar datos de edición si se envía edit_id por GET
$edit_id = (int)($_GET['edit_id'] ?? 0);
if ($edit_id > 0) {
    if (method_exists($controlMaterias, 'find')) {
        $editMateria = $controlMaterias->find($edit_id) ?: null;
    } else {
        if (!isset($mm)) { require_once __DIR__ . '/../app/models/Materia.php'; $mm = new \Materia(); }
        $editMateria = $mm->find($edit_id) ?: null;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token) ?>">
  <title>SICEnet · ITSUR — Materias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <link href="assets/css/desktop-fixes.css" rel="stylesheet">
</head>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12 mb-3">
            <div>
                <?php $pageTitle = 'Materias'; ?>
                <h1 class="h3 mb-0"><?= $pageTitle ?></h1>
                <?php $breadcrumbs = [ ['label' => 'Inicio', 'url' => 'dashboard.php'], ['label' => $pageTitle, 'url' => null] ]; ?>
                <?php require __DIR__ . '/partials/breadcrumb.php'; ?>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-book"></i> Alta de Materia</h5>
                    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
                    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <input type="hidden" name="action" value="<?= $editMateria ? 'update' : 'create' ?>">
                        <?php if ($editMateria): ?>
                        <input type="hidden" name="id" value="<?= (int)$editMateria['id'] ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($editMateria['nombre'] ?? '') ?>" required>
                            <div class="invalid-feedback">El nombre es obligatorio.</div>
                        </div>
                        <div class="mb-3">
                            <label for="clave" class="form-label">Clave</label>
                            <input type="text" class="form-control" id="clave" name="clave" placeholder="Ej: INF101" value="<?= htmlspecialchars($editMateria['clave'] ?? '') ?>">
                        </div>
                        <button type="submit" class="btn btn-primary"><?= $editMateria ? 'Actualizar' : 'Crear' ?></button>
                        <?php if ($editMateria): ?>
                        <a href="materias.php" class="btn btn-outline-secondary">Cancelar</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-list"></i> Materias</h5>
                    <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
                      <div class="flex-grow-1" style="max-width: 320px;">
                        <input type="text" class="form-control" placeholder="Filtrar rápido en la tabla" data-quick-filter-for="#tabla-materias">
                      </div>
                      <div class="d-flex align-items-center gap-2">
                         <button class="btn btn-outline-primary btn-sm" data-export="csv" data-target="#tabla-materias" data-filename="materias.csv" data-timestamp="true"><i class="bi bi-filetype-csv"></i> Exportar CSV</button>
        <button class="btn btn-outline-secondary btn-sm" data-export="pdf" data-target="#tabla-materias"><i class="bi bi-filetype-pdf"></i> Exportar PDF</button>
                      </div>
                    </div>
                    <div class="table-responsive">
                      <table class="table table-striped table-hover table-sort" id="tabla-materias">
                          <thead><tr><th class="d-none d-sm-table-cell">ID</th><th>Nombre</th><th class="d-none d-sm-table-cell">Clave</th><th>Acciones</th></tr></thead>
                          <tbody>
                          <?php if (count($materias) === 0): ?>
                            <tr class="empty-state-row"><td colspan="4" class="text-center text-muted">No hay materias registradas</td></tr>
                          <?php endif; ?>
                          <?php foreach ($materias as $m): ?>
                              <tr>
                                  <td class="d-none d-sm-table-cell"><?= (int)$m['id'] ?></td>
                                  <td><?= htmlspecialchars($m['nombre']) ?></td>
                                  <td class="d-none d-sm-table-cell"><?= htmlspecialchars($m['clave'] ?? '') ?></td>
                                  <td>
                                      <div class="d-flex flex-column flex-sm-row gap-1">
                                        <a href="?edit_id=<?= (int)$m['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta materia?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                        </form>
                                      </div>
                                  </td>
                              </tr>
                          <?php endforeach; ?>
                          </tbody>
                      </table>
                    </div>
                    <nav>
                        <ul class="pagination">
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
// Validación de Bootstrap
(function(){
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>
</body>
</html>