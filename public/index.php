<?php
require_once __DIR__ . '/../app/init.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/capas/negocio/ControlAutenticacion.php';
use App\Capas\Negocio\ControlAutenticacion;
$controlAut = new ControlAutenticacion();

// Si ya hay sesión activa, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$mensajeError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF del formulario
    $postToken = (string)($_POST['csrf_token'] ?? '');
    if (!$controlAut->validarTokenCSRF($postToken)) {
        $mensajeError = 'Token de seguridad inválido. Intenta de nuevo.';
    } else {
        $identificador = trim((string)filter_input(INPUT_POST, 'identifier', FILTER_UNSAFE_RAW));
        $contrasena = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);
        
        try {
            if ($controlAut->iniciarSesion($identificador, $contrasena)) {
                header('Location: dashboard.php');
                exit;
            } else {
                $mensajeError = 'Credenciales inválidas';
            }
        } catch (Exception $e) {
            $mensajeError = 'Error al intentar iniciar sesión';
        }
    }
}

$tokenCSRF = $controlAut->generarTokenCSRF();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SICEnet · ITSUR — Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" 
          rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <link href="assets/css/desktop-fixes.css" rel="stylesheet">
    <meta name="csrf-token" content="<?= htmlspecialchars($tokenCSRF) ?>">
</head>
<body>
    <!-- Header institucional compacto -->
    <header class="institutional-header">
        <div class="container-fluid">
            <a href="index.php" class="institutional-brand">
                <img src="assets/ITSUR-LOGO.webp" alt="ITSUR Logo" class="institutional-logo">
                <div class="institutional-text">
                    <h1 class="institutional-title">SICEnet · ITSUR</h1>
                    <p class="institutional-subtitle">Sistema Integral de Control Escolar</p>
                </div>
            </a>
        </div>
    </header>
    <div class="container-fluid login-container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-11 col-lg-9 col-xl-8">
                <div class="login-card">
                    <div class="login-content">
                        <h3 class="login-title text-center mb-3">Bienvenido</h3>
                        <p class="login-subtitle text-center mb-4">Accede con tu matrícula</p>
                        
                        <?php if (!empty($mensajeError)): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>"
                              class="needs-validation" novalidate id="login-form">
                <input type="hidden" name="csrf_token" 
                    value="<?= htmlspecialchars($tokenCSRF) ?>">
                            
                            <div class="mb-3">
                                <label for="identifier" class="form-label">Matrícula</label>
                                <input type="text" class="form-control" id="identifier" 
                                       name="identifier" required 
                                       pattern="^[SICMQEA][0-9]{8}$|^.+@.+$" 
                                       placeholder="Ej: S12345678">
                                <div class="invalid-feedback">
                                    Ingresa una matrícula válida (prefijo de ingeniería + 8 dígitos).
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña:</label>
                                <input type="password" class="form-control" id="password" 
                                       name="password" required>
                                <div class="invalid-feedback">
                                    Por favor ingrese su contraseña.
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                Iniciar Sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
    // Validación ligera y UX: prevenir envíos vacíos, trimming y feedback
    (function() {
        'use strict';
        const form = document.getElementById('login-form');
        if (!form) return;
        form.addEventListener('submit', (event) => {
            const idEl = document.getElementById('identifier');
            const passEl = document.getElementById('password');
            if (idEl) idEl.value = (idEl.value || '').trim();
            if (passEl) passEl.value = (passEl.value || '').trim();
            const valid = form.checkValidity();
            if (!valid) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    })();
    </script>
</body>
</html>