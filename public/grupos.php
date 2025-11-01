<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/models/Grupo.php';
require_once __DIR__ . '/../app/models/Materia.php';
require_once __DIR__ . '/../app/models/Usuario.php';

$auth = new AuthController();
$auth->requireAuth();

$grupoModel = new Grupo();
$materiaModel = new Materia();
$usuarioModel = new Usuario();

$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
$msg = '';
$error = '';
// Para edición
$editGrupo = null;

if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
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
                    $grupoModel->delete($id);
                    $msg = 'Grupo eliminado';
                } catch (Throwable $e) {
                    $error = 'No se pudo eliminar (referencias existentes)';
                }
            }
        } else {
            $materia_id = filter_input(INPUT_POST, 'materia_id', FILTER_VALIDATE_INT);
            $profesor_id = filter_input(INPUT_POST, 'profesor_id', FILTER_VALIDATE_INT);
            $nombre = trim((string)filter_input(INPUT_POST, 'nombre', FILTER_UNSAFE_RAW));
            $ciclo = trim((string)filter_input(INPUT_POST, 'ciclo', FILTER_UNSAFE_RAW));
            $cupo = filter_input(INPUT_POST, 'cupo', FILTER_VALIDATE_INT);

            if (!$materia_id || !$profesor_id || $nombre === '' || !$cupo || $cupo < 1) {
                $error = 'Todos los campos son obligatorios y el cupo debe ser mayor a 0';
            } else {
                try {
                    if ($action === 'update') {
                        $id = (int)($_POST['id'] ?? 0);
                        if ($id <= 0) {
                            $error = 'ID inválido';
                        } else {
                            $grupoModel->update($id, [
                                'materia_id' => $materia_id,
                                'profesor_id' => $profesor_id,
                                'nombre' => $nombre,
                                'ciclo' => $ciclo ?: null,
                                'cupo' => $cupo
                            ]);
                            $msg = 'Grupo actualizado correctamente';
                        }
                    } else {
                        $grupoModel->create([
                            'materia_id' => $materia_id,
                            'profesor_id' => $profesor_id,
                            'nombre' => $nombre,
                            'ciclo' => $ciclo ?: null,
                            'cupo' => $cupo
                        ]);
                        $msg = 'Grupo creado correctamente';
                    }
                } catch (Throwable $e) {
                    $error = 'Error al guardar el grupo';
                }
            }
        }
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$profesorId = null;
if (!$isAdmin) {
    $profesorId = $_SESSION['user_id'] ?? null;
}
$grupos = $grupoModel->getWithJoins($page, $limit, $profesorId);
$total = $grupoModel->countWithFilter($profesorId);
$totalPages = max(1, (int)ceil($total / $limit));
$csrf_token = $auth->generateCSRFToken();
// Editar si viene edit_id
$edit_id = (int)($_GET['edit_id'] ?? 0);
if ($isAdmin && $edit_id > 0) {
    $editGrupo = $grupoModel->find($edit_id) ?: null;
}

// Datos para selects
$materias = $materiaModel->getAll(1, 100);
$profesores = $usuarioModel->getAll(1, 100, "WHERE rol = 'profesor' AND activo = 1");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <title>Grupos - Control Escolar</title>
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
                <?php if ($isAdmin): ?>
                <li class="nav-item"><a class="nav-link" href="materias.php">Materias</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link active" href="grupos.php">Grupos</a></li>
            </ul>
            <ul class="navbar-nav"><li class="nav-item"><a class="nav-link" href="logout.php">Salir</a></li></ul>
        </div>
    </div>
    </nav>

<div class="container mt-4">
    <div class="row">
        <?php if ($isAdmin): ?>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-grid-3x3"></i> Alta de Grupo</h5>
                    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
                    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <input type="hidden" name="action" value="<?= $editGrupo ? 'update' : 'create' ?>">
                        <?php if ($editGrupo): ?>
                        <input type="hidden" name="id" value="<?= (int)$editGrupo['id'] ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="materia_id" class="form-label">Materia</label>
                            <select class="form-select" id="materia_id" name="materia_id" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($materias as $m): ?>
                                <option value="<?= (int)$m['id'] ?>" <?= ($editGrupo && (int)$editGrupo['materia_id'] === (int)$m['id']) ? 'selected' : '' ?>><?= htmlspecialchars($m['nombre']) ?> (<?= htmlspecialchars($m['clave'] ?? '') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Seleccione una materia.</div>
                        </div>
                        <div class="mb-3">
                            <label for="profesor_id" class="form-label">Profesor</label>
                            <select class="form-select" id="profesor_id" name="profesor_id" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($profesores as $p): ?>
                                <option value="<?= (int)$p['id'] ?>" <?= ($editGrupo && (int)$editGrupo['profesor_id'] === (int)$p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['email']) ?> (<?= htmlspecialchars($p['matricula'] ?? '') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Seleccione un profesor.</div>
                        </div>
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Grupo</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ej: GPO-101-A" value="<?= htmlspecialchars($editGrupo['nombre'] ?? '') ?>" required>
                            <div class="invalid-feedback">El nombre del grupo es obligatorio.</div>
                        </div>
                        <div class="mb-3">
                            <label for="ciclo" class="form-label">Ciclo</label>
                            <input type="text" class="form-control" id="ciclo" name="ciclo" placeholder="Ej: 2024A" value="<?= htmlspecialchars($editGrupo['ciclo'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="cupo" class="form-label">Cupo Máximo</label>
                            <input type="number" class="form-control" id="cupo" name="cupo" placeholder="30" min="1" max="100" value="<?= (int)($editGrupo['cupo'] ?? 30) ?>" required>
                            <div class="form-text">Número máximo de estudiantes que pueden inscribirse en este grupo.</div>
                            <div class="invalid-feedback">El cupo debe ser un número entre 1 y 100.</div>
                        </div>
                        <button type="submit" class="btn btn-primary"><?= $editGrupo ? 'Actualizar' : 'Crear' ?></button>
                        <?php if ($editGrupo): ?>
                        <a href="grupos.php" class="btn btn-outline-secondary">Cancelar</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-list"></i> Grupos</h5>
                    <table class="table table-striped">
                        <thead><tr><th>ID</th><th>Grupo</th><th>Ciclo</th><th>Materia</th><th>Profesor</th><th>Cupo</th><?php if ($isAdmin): ?><th>Acciones</th><?php endif; ?></tr></thead>
                        <tbody>
                        <?php foreach ($grupos as $g): ?>
                            <tr>
                                <td><?= (int)$g['id'] ?></td>
                                <td><?= htmlspecialchars($g['nombre']) ?></td>
                                <td><?= htmlspecialchars($g['ciclo'] ?? '') ?></td>
                                <td><?= htmlspecialchars($g['materia_nombre']) ?> (<?= htmlspecialchars($g['materia_clave'] ?? '') ?>)</td>
                                <td><?= htmlspecialchars($g['profesor_email']) ?> (<?= htmlspecialchars($g['profesor_matricula'] ?? '') ?>)</td>
                                <td><?= (int)($g['cupo'] ?? 30) ?></td>
                                <?php if ($isAdmin): ?>
                                <td>
                                    <a href="?edit_id=<?= (int)$g['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar este grupo?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        								<input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                    </form>
                                </td>
                                <?php endif; ?>
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