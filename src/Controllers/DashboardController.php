<?php
namespace App\Controllers;

class DashboardController
{
    public function index(): string
    {
        $role = $_SESSION['role'] ?? 'guest';
        ob_start();
        $viewPath = match ($role) {
            'admin' => __DIR__ . '/../Views/dashboard/admin.php',
            'profesor' => __DIR__ . '/../Views/dashboard/professor.php',
            'alumno' => __DIR__ . '/../Views/dashboard/student.php',
            default => __DIR__ . '/../Views/auth/login.php',
        };
        include $viewPath;
        return ob_get_clean();
    }
}