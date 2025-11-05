<?php
require_once __DIR__ . '/../../app/controllers/api/AlumnoAcademicoApiController.php';

$controller = new AlumnoAcademicoApiController();

// Rutas soportadas:
// GET /api/alumno/carga?ciclo=2024A
// GET /api/alumno/calificaciones?ciclo=2024A
// GET /api/alumno/estadisticas?ciclo=2024A

$pathInfo = isset($_SERVER['PATH_INFO']) ? trim((string)$_SERVER['PATH_INFO'], '/') : '';
$segments = $pathInfo !== '' ? explode('/', $pathInfo) : [];

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $controller->jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
}

if (count($segments) === 1 && $segments[0] === 'carga') {
    $controller->carga();
} elseif (count($segments) === 1 && $segments[0] === 'calificaciones') {
    $controller->calificaciones();
} elseif (count($segments) === 1 && $segments[0] === 'estadisticas') {
    $controller->estadisticas();
} else {
    $controller->jsonResponse(['success' => false, 'error' => 'Ruta no encontrada'], 404);
}