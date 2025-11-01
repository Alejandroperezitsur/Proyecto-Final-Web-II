<?php
require_once __DIR__ . '/../app/init.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
$auth = new AuthController();

// Si ya hay sesión activa, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim((string)filter_input(INPUT_POST, 'identifier', FILTER_UNSAFE_RAW));
    $password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);
    
    try {
        if ($auth->login($identifier, $password)) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Credenciales inválidas';
        }
    } catch (Exception $e) {
        $error = 'Error al intentar iniciar sesión';
    }
}

$csrf_token = $auth->generateCSRFToken();
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
            <button id="themeToggle" class="btn btn-sm btn-outline-secondary ms-auto" type="button" title="Cambiar tema">
              <i class="bi bi-sun-fill"></i>
            </button>
        </div>
    </header>
    <div class="container login-container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-4">
                <div class="login-card">
                    <div class="">
                        <h3 class="login-title text-center mb-2">Bienvenido</h3>
                        <p class="login-subtitle text-center">Accede con tu matrícula o correo institucional</p>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>"
                              class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" 
                                   value="<?= htmlspecialchars($csrf_token) ?>">
                            
                            <div class="mb-3">
                                <label for="identifier" class="form-label">Matrícula</label>
                                <input type="text" class="form-control" id="identifier" 
                                       name="identifier" required 
                                       pattern="^[SICMQEA][0-9]{8}$|^.+@.+\..+$" 
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
    // Validación del formulario
    (function() {
        'use strict';
        const forms = document.querySelectorAll('.needs-validation');
        
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
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