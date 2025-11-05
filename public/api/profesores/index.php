<?php
require_once __DIR__ . '/../../app/controllers/api/ProfesoresApiController.php';

$controller = new ProfesoresApiController();

// Soporta:
// - GET /api/profesores/grupos
// - GET /api/profesores/grupos/{id}/alumnos
// - POST /api/profesores/calificaciones

$pathInfo = isset($_SERVER['PATH_INFO']) ? trim((string)$_SERVER['PATH_INFO'], '/') : '';
$segments = $pathInfo !== '' ? explode('/', $pathInfo) : [];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (count($segments) === 1 && $segments[0] === 'grupos') {
        $controller->grupos();
    } elseif (count($segments) === 3 && $segments[0] === 'grupos' && $segments[2] === 'alumnos' && preg_match('/^\d+$/', $segments[1])) {
        $controller->alumnosGrupo((int)$segments[1]);
    } else {
        $controller->jsonResponse(['success' => false, 'error' => 'Ruta no encontrada'], 404);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (count($segments) === 1 && $segments[0] === 'calificaciones') {
        $controller->updateCalificacion();
    } else {
        $controller->jsonResponse(['success' => false, 'error' => 'Ruta no encontrada'], 404);
    }
} else {
    $controller->jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
}