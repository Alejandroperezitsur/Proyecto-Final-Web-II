<?php
require_once __DIR__ . '/../../app/controllers/api/AdminApiController.php';

$controller = new AdminApiController();

// Rutas soportadas:
// GET /api/admin/{entity}
// GET /api/admin/{entity}/{id}
// POST /api/admin/{entity}
// POST /api/admin/{entity}/{id} (update vs delete según campos)

$pathInfo = isset($_SERVER['PATH_INFO']) ? trim((string)$_SERVER['PATH_INFO'], '/') : '';
$segments = $pathInfo !== '' ? explode('/', $pathInfo) : [];

if (count($segments) < 1) {
    $controller->jsonResponse(['success' => false, 'error' => 'Entidad requerida'], 400);
}

$entity = $segments[0];
$id = null;
if (isset($segments[1]) && preg_match('/^\d+$/', $segments[1])) { $id = (int)$segments[1]; }

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($id !== null) {
        $controller->show($entity, $id);
    } else {
        $controller->index($entity);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($id === null) {
        $controller->store($entity);
    } else {
        // Si sólo viene id + csrf_token, considerar eliminar
        $_POST['id'] = $id;
        $keys = array_keys($_POST); sort($keys);
        if ($keys === ['csrf_token','id']) {
            $controller->destroy($entity, $id);
        } else {
            $controller->update($entity, $id);
        }
    }
} else {
    $controller->jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
}