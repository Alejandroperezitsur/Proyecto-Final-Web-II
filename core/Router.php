<?php
namespace Core;

class Router
{
    private array $routes = [];

    public function get(string $path, string $action): void
    {
        $this->routes['GET'][$path] = $action;
    }

    public function post(string $path, string $action): void
    {
        $this->routes['POST'][$path] = $action;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $route = $_GET['route'] ?? '/';
        $action = $this->routes[$method][$route] ?? null;
        if (!$action) {
            http_response_code(404);
            echo '404 - Ruta no encontrada';
            return;
        }
        [$controllerName, $methodName] = explode('@', $action);
        $controllerClass = 'Controllers\\' . $controllerName;
        if (!class_exists($controllerClass)) {
            http_response_code(500);
            echo 'Controlador no encontrado';
            return;
        }
        $controller = new $controllerClass();
        if (!method_exists($controller, $methodName)) {
            http_response_code(500);
            echo 'Acción no encontrada';
            return;
        }
        $controller->$methodName();
    }
}