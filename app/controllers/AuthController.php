<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Usuario.php';

class AuthController extends Controller {
    protected $model;
    
    public function __construct() {
        $this->model = new Usuario();
    }
    
    public function generateCSRFToken() {
        return parent::generateCSRFToken();
    }
    
    public function login($email, $password) {
        if (empty($email) || empty($password)) {
            return false;
        }

        $usuario = $this->model->findByEmail($email);
        if (!$usuario) {
            return false;
        }

        if (!password_verify($password, $usuario['password'])) {
            return false;
        }

        // Regenerar ID de sesión por seguridad
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_email'] = $usuario['email'];
        $_SESSION['user_role'] = $usuario['rol'];
        
        // Registrar último login
        $this->model->updateLastLogin($usuario['id']);
        
        return true;
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
            header('Location: /login.php');
            exit;
        }
    }
    
    public function requireRole($roles) {
        $this->requireAuth();
        if (!in_array($_SESSION['user_role'], (array)$roles)) {
            header('Location: /error.php?code=403');
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