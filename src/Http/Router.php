<?php
namespace App\Http;

class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler, array $middlewares = []): void
    {
        $this->add('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, callable $handler, array $middlewares = []): void
    {
        $this->add('POST', $path, $handler, $middlewares);
    }

    private function add(string $method, string $path, callable $handler, array $middlewares): void
    {
        $this->routes[$method][$path] = ['handler' => $handler, 'middlewares' => $middlewares];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        if ($scriptDir && $scriptDir !== '/') {
            $uri = preg_replace('#^' . preg_quote($scriptDir, '#') . '#', '', $uri);
            if ($uri === '') { $uri = '/'; }
        }

        $route = $this->routes[$method][$uri] ?? null;
        if (!$route) {
            http_response_code(404);
            echo 'Ruta no encontrada: ' . htmlspecialchars($method . ' ' . $uri);
            return;
        }

        foreach ($route['middlewares'] as $mw) {
            $result = $mw();
            if ($result === false) {
                return; // Middleware ya emitió respuesta
            }
        }

        echo $route['handler']();
    }
}