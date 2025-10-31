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
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public function login($identifier, $password) {
        if (empty($identifier) || empty($password)) {
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
                return true;
            }

            // Si no es alumno o no coincide contraseña, intentar profesor por matrícula
            $usuarioProfesor = $this->model->findByMatricula($identifier);
            if (!$usuarioProfesor || $usuarioProfesor['rol'] !== 'profesor') {
                return false;
            }
            if (!password_verify($password, $usuarioProfesor['password'])) {
                return false;
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = $usuarioProfesor['id'];
            $_SESSION['user_email'] = $usuarioProfesor['email'] ?? '';
            $_SESSION['user_role'] = 'profesor';
            $_SESSION['user_identifier'] = $identifier; // matrícula del profesor
            $this->model->updateLastLogin($usuarioProfesor['id']);
            return true;
        } else {
            // Solo el administrador puede ingresar por email
            $usuario = $this->model->findByEmail($identifier);
            if (!$usuario || $usuario['rol'] !== 'admin') {
                return false;
            }
            if (!password_verify($password, $usuario['password'])) {
                return false;
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_email'] = $usuario['email'];
            $_SESSION['user_role'] = $usuario['rol'];
            $_SESSION['user_identifier'] = $usuario['email'];
            $this->model->updateLastLogin($usuario['id']);
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
        return isset($_SESSION['user_id']);
    }
    
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header('Location: /index.php');
            exit;
        }
    }
    
    public function requireRole($roles) {
        $this->requireAuth();
        if (!in_array($_SESSION['user_role'], (array)$roles)) {
            header('Location: /index.php?code=403');
            exit;
        }
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        return $this->model->find($_SESSION['user_id']);
    }
}