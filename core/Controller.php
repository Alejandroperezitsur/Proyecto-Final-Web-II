<?php
namespace Core;

class Controller
{
    protected function render(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            echo 'Vista no encontrada: ' . htmlspecialchars($view);
            return;
        }
        include __DIR__ . '/../views/layout.php';
    }

    protected function redirect(string $route): void
    {
        header('Location: /?route=' . urlencode($route));
        exit;
    }

    protected function requireRole(string $role): void
    {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
            $this->redirect('login/' . $role);
        }
    }
}