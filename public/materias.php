<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/models/Materia.php';

$auth = new AuthController();
$auth->requireAuth();
$auth->requireRole(['admin']);

$materiaModel = new Materia();
$msg = '';
$error = '';
// Para edición
$editMateria = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validación CSRF básica
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error = 'Token CSRF inválido';
    } else {
        $action = $_POST['action'] ?? 'create';
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                $error = 'ID inválido';
            } else {
                try {
                    $materiaModel->delete($id);
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
                            $materiaModel->update($id, ['nombre' => $nombre, 'clave' => $clave ?: null]);
                            $msg = 'Materia actualizada correctamente';
                        }
                    } else {
                        $materiaModel->create(['nombre' => $nombre, 'clave' => $clave ?: null]);
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
$materias = $materiaModel->getAll($page, $limit);
$total = $materiaModel->count('');
$totalPages = max(1, (int)ceil($total / $limit));
$csrf_token = $auth->generateCSRFToken();
// Cargar datos de edición si se envía edit_id por GET
$edit_id = (int)($_GET['edit_id'] ?? 0);
if ($edit_id > 0) {
    $editMateria = $materiaModel->find($edit_id) ?: null;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <title>Materias - Control Escolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
}</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">Control Escolar</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="materias.php">Materias</a></li>
                <li class="nav-item"><a class="nav-link" href="grupos.php">Grupos</a></li>
            </ul>
            <ul class="navbar-nav"><li class="nav-item"><a class="nav-link" href="logout.php">Salir</a></li></ul>
        </div>
    </div>
    </nav>

<div class="container mt-4">
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
                    <table class="table table-striped">
                        <thead><tr><th>ID</th><th>Nombre</th><th>Clave</th><th>Acciones</th></tr></thead>
                        <tbody>
                        <?php foreach ($materias as $m): ?>
                            <tr>
                                <td><?= (int)$m['id'] ?></td>
                                <td><?= htmlspecialchars($m['nombre']) ?></td>
                                <td><?= htmlspecialchars($m['clave'] ?? '') ?></td>
                                <td>
                                    <a href="?edit_id=<?= (int)$m['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar esta materia?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
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