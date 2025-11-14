<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Alumno.php';

class AuthController extends Controller {
    protected $model;
    
    public function __construct() {
        $this->model = new Usuario();
    }
    
    public function generateCSRFToken() {
        return parent::generateCSRFToken();
    }

    public function validateCSRFToken($token) {
        require_once __DIR__ . '/../init.php';
        return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public function login($identifier, $password) {
        if (empty($identifier) || empty($password)) {
            return false;
        }
        // Protección básica contra fuerza bruta: limitar intentos por sesión
        require_once __DIR__ . '/../init.php';
        $now = time();
        $_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? [];
        // limpiar intentos antiguos (más de 10 minutos)
        $_SESSION['login_attempts'] = array_filter(
            (array)$_SESSION['login_attempts'],
            function($ts) use ($now) { return ($now - (int)$ts) < 600; }
        );
        if (count($_SESSION['login_attempts']) >= 10) {
            // Demora leve para desalentar scripts
            usleep(250000); // 250ms
            return false;
        }
        // Detectar si es matrícula: prefijo de ingeniería + 8 dígitos
        // Prefijos inventados (solo ingenierías):
        // S (Sistemas Computacionales), I (Industrial), C (Civil), M (Mecánica),
        // Q (Química), E (Electrónica), A (Ambiental)
        $isMatricula = (bool)preg_match('/^[SICMQEA][0-9]{8}$/', $identifier);

        if ($isMatricula) {
            $alumnoModel = new Alumno();
            $alumno = $alumnoModel->findByMatricula($identifier);
            if ($alumno && !empty($alumno['password']) && password_verify($password, $alumno['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $alumno['id'];
                $_SESSION['user_email'] = $alumno['email'] ?? '';
                $_SESSION['user_role'] = 'alumno';
                $_SESSION['user_identifier'] = $alumno['matricula'];
                // Rehash transparente si cambian los parámetros del algoritmo
                if (password_needs_rehash($alumno['password'], PASSWORD_DEFAULT)) {
                    $alumnoModel->update($alumno['id'], ['password' => $password]);
                }
                // Reiniciar contador de intentos al éxito
                $_SESSION['login_attempts'] = [];
                return true;
            }

            // Si no es alumno o no coincide contraseña, intentar profesor por matrícula
            $usuarioProfesor = $this->model->findByMatricula($identifier);
            if (!$usuarioProfesor || $usuarioProfesor['rol'] !== 'profesor') {
                return false;
            }
            if (!password_verify($password, $usuarioProfesor['password'])) {
                $_SESSION['login_attempts'][] = $now;
                return false;
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = $usuarioProfesor['id'];
            $_SESSION['user_email'] = $usuarioProfesor['email'] ?? '';
            $_SESSION['user_role'] = 'profesor';
            $_SESSION['user_identifier'] = $identifier; // matrícula del profesor
            $this->model->updateLastLogin($usuarioProfesor['id']);
            if (password_needs_rehash($usuarioProfesor['password'], PASSWORD_DEFAULT)) {
                $this->model->update($usuarioProfesor['id'], ['password' => $password]);
            }
            $_SESSION['login_attempts'] = [];
            return true;
        } else {
            // Solo el administrador puede ingresar por email
            $usuario = $this->model->findByEmail($identifier);
            if (!$usuario || $usuario['rol'] !== 'admin') {
                return false;
            }
            if (!password_verify($password, $usuario['password'])) {
                $_SESSION['login_attempts'][] = $now;
                return false;
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_email'] = $usuario['email'];
            $_SESSION['user_role'] = $usuario['rol'];
            $_SESSION['user_identifier'] = $usuario['email'];
            $this->model->updateLastLogin($usuario['id']);
            if (password_needs_rehash($usuario['password'], PASSWORD_DEFAULT)) {
                $this->model->update($usuario['id'], ['password' => $password]);
            }
            $_SESSION['login_attempts'] = [];
            return true;
        }
    }
    
    public function logout() {
        // Destruir todas las variables de sesión
        $_SESSION = array();
        
        // Destruir la cookie de sesión si existe
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-42000, '/');
        }
        
        // Destruir la sesión
        session_destroy();
    }
    
    public function isLoggedIn() {
        require_once __DIR__ . '/../init.php';
        return isset($_SESSION['user_id']);
    }
    
    public function requireAuth() {
        require_once __DIR__ . '/../init.php';
        if (!$this->isLoggedIn()) {
            header('Location: /app.php/login');
            exit;
        }
    }
    
    public function requireRole($roles) {
        $this->requireAuth();
        if (!in_array($_SESSION['user_role'], (array)$roles)) {
            header('Location: /app.php/login?code=403');
            exit;
        }
    }
    
    public function getCurrentUser() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (!$this->isLoggedIn()) {
            return null;
        }
        $role = $_SESSION['user_role'] ?? null;
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) { return null; }
        // Soporte para alumnos: obtener desde tabla alumnos y normalizar rol
        if ($role === 'alumno') {
            $alumnoModel = new Alumno();
            $alumno = $alumnoModel->find($id);
            if ($alumno && is_array($alumno)) {
                $alumno['rol'] = 'alumno';
                return $alumno;
            }
            return null;
        }
        // Para admin y profesor: tabla usuarios
        return $this->model->find($id);
    }

    public function changePassword($currentPassword, $newPassword) {
        require_once __DIR__ . '/../init.php';
        if (!$this->isLoggedIn()) {
            return false;
        }
        // Requisitos mínimos: 8+ caracteres, letras y números
        $len = strlen((string)$newPassword);
        if ($len < 8 || !preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/\d/', $newPassword)) {
            return false;
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $role = (string)($_SESSION['user_role'] ?? '');
        if ($userId <= 0 || $role === '') {
            return false;
        }

        if ($role === 'alumno') {
            $alumnoModel = new Alumno();
            $user = $alumnoModel->find($userId);
            $hash = $user['password'] ?? null;
            if (!$hash || !password_verify($currentPassword, $hash)) {
                return false;
            }
            // El modelo aplicará hash internamente
            $ok = $alumnoModel->update($userId, ['password' => $newPassword]);
        } else {
            $user = $this->model->find($userId);
            $hash = $user['password'] ?? null;
            if (!$hash || !password_verify($currentPassword, $hash)) {
                return false;
            }
            $ok = $this->model->update($userId, ['password' => $newPassword]);
        }

        if ($ok) {
            session_regenerate_id(true);
        }
        return (bool)$ok;
    }
}
