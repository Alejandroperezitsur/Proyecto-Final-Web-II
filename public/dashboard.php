<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';

$auth = new AuthController();
$auth->requireAuth();

$currentUser = $auth->getCurrentUser();
$isAdmin = $_SESSION['user_role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <title>Dashboard - Control Escolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" 
          rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" 
          rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Control Escolar</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                    data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if ($isAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="alumnos.php">
                            <i class="bi bi-people"></i> Alumnos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profesores.php">
                            <i class="bi bi-person-badge"></i> Profesores
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="materias.php">
                            <i class="bi bi-book"></i> Materias
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="grupos.php">
                            <i class="bi bi-grid-3x3"></i> Grupos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="calificaciones.php">
                            <i class="bi bi-card-checklist"></i> Calificaciones
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" 
                           data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <?= htmlspecialchars($_SESSION['user_email']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="perfil.php">
                                    <i class="bi bi-person"></i> Mi Perfil
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item" href="logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">Dashboard</h2>
            </div>
        </div>

        <div class="row g-4">
            <?php if ($isAdmin): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-people text-primary"></i> Alumnos
                        </h5>
                        <p class="card-text">Gestiona el registro de alumnos.</p>
                        <a href="alumnos.php" class="btn btn-primary">
                            Administrar
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-person-badge text-success"></i> Profesores
                        </h5>
                        <p class="card-text">Administra la plantilla docente.</p>
                        <a href="profesores.php" class="btn btn-success">
                            Administrar
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-book text-info"></i> Materias
                        </h5>
                        <p class="card-text">Gestiona el catálogo de materias.</p>
                        <a href="materias.php" class="btn btn-info">
                            Administrar
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="col-md-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-grid-3x3 text-warning"></i> Grupos
                        </h5>
                        <p class="card-text">
                            <?= $isAdmin ? 'Administra los grupos y horarios.' : 
                                         'Ver tus grupos asignados.' ?>
                        </p>
                        <a href="grupos.php" class="btn btn-warning">
                            <?= $isAdmin ? 'Administrar' : 'Ver Grupos' ?>
                        </a>
                    </div>
                </div>
            </div>

            <?php if (!$isAdmin): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-card-checklist text-danger"></i> Calificaciones
                        </h5>
                        <p class="card-text">Registra las calificaciones de tus grupos.</p>
                        <a href="calificaciones.php" class="btn btn-danger">
                            Calificar
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
    </script>
</body>
</html>