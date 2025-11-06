<?php
namespace Controllers;

use Core\Controller;
use Core\Database;
use PDO;

class AuthController extends Controller
{
    public function home(): void
    {
        $this->render('auth/home');
    }

    public function studentLogin(): void
    {
        $this->render('auth/login_student');
    }

    public function professorLogin(): void
    {
        $this->render('auth/login_professor');
    }

    public function adminLogin(): void
    {
        $this->render('auth/login_admin');
    }

    public function studentAuth(): void
    {
        $matricula = \Core\Security::input('matricula');
        $password = $_POST['password'] ?? '';
        $csrf = \Core\Security::input('csrf_token');
        if (!\Core\Security::verifyCsrf($csrf)) {
            $_SESSION['error'] = 'CSRF inválido';
            $this->redirect('login/student');
        }
        if (!preg_match('/^\d{9}$/', $matricula) || strlen($password) < 8) {
            $_SESSION['error'] = 'Datos inválidos';
            $this->redirect('login/student');
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, nombre, apellido, password_hash FROM alumnos WHERE matricula = ?');
        $stmt->execute([$matricula]);
        $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($alumno && password_verify($password, $alumno['password_hash'])) {
            $_SESSION['role'] = 'student';
            $_SESSION['user'] = ['id' => $alumno['id'], 'name' => $alumno['nombre'] . ' ' . $alumno['apellido']];
            $this->redirect('student/dashboard');
        }
        $_SESSION['error'] = 'Matrícula o contraseña incorrecta';
        $this->redirect('login/student');
    }

    public function professorAuth(): void
    {
        $usuario = \Core\Security::input('usuario');
        $password = $_POST['password'] ?? '';
        $csrf = \Core\Security::input('csrf_token');
        if (!\Core\Security::verifyCsrf($csrf)) {
            $_SESSION['error'] = 'CSRF inválido';
            $this->redirect('login/professor');
        }
        if ($usuario === '' || strlen($password) < 8) {
            $_SESSION['error'] = 'Datos inválidos';
            $this->redirect('login/professor');
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, nombre, apellido, password_hash, carrera_id FROM profesores WHERE usuario = ?');
        $stmt->execute([$usuario]);
        $prof = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($prof && password_verify($password, $prof['password_hash'])) {
            $_SESSION['role'] = 'professor';
            $_SESSION['user'] = ['id' => $prof['id'], 'name' => $prof['nombre'] . ' ' . $prof['apellido'], 'carrera_id' => $prof['carrera_id']];
            $this->redirect('professor/dashboard');
        }
        $_SESSION['error'] = 'Usuario o contraseña incorrecta';
        $this->redirect('login/professor');
    }

    public function adminAuth(): void
    {
        $usuario = \Core\Security::input('usuario');
        $password = $_POST['password'] ?? '';
        $csrf = \Core\Security::input('csrf_token');
        if (!\Core\Security::verifyCsrf($csrf)) {
            $_SESSION['error'] = 'CSRF inválido';
            $this->redirect('login/admin');
        }
        if ($usuario === '' || strlen($password) < 8) {
            $_SESSION['error'] = 'Datos inválidos';
            $this->redirect('login/admin');
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, nombre, password_hash FROM admins WHERE usuario = ?');
        $stmt->execute([$usuario]);
        $adm = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($adm && password_verify($password, $adm['password_hash'])) {
            $_SESSION['role'] = 'admin';
            $_SESSION['user'] = ['id' => $adm['id'], 'name' => $adm['nombre']];
            $this->redirect('admin/dashboard');
        }
        $_SESSION['error'] = 'Usuario o contraseña incorrecta';
        $this->redirect('login/admin');
    }

    public function logout(): void
    {
        session_destroy();
        $this->redirect('/');
    }
}