<?php
require_once __DIR__ . '/../../app/controllers/api/AlumnosApiController.php';

// Endpoint REST para alumnos, compatible con /api/alumnos y /api/alumnos/{id}
// Se apoya en PATH_INFO para obtener el ID si viene en la URL.

$controller = new AlumnosApiController();

// Normalizar PATH_INFO para obtener el ID cuando es /api/alumnos/{id}
$pathInfo = isset($_SERVER['PATH_INFO']) ? trim((string)$_SERVER['PATH_INFO'], '/') : '';
$segments = $pathInfo !== '' ? explode('/', $pathInfo) : [];
$idFromPath = null;
if (count($segments) >= 1 && preg_match('/^\d+$/', $segments[0])) {
    $idFromPath = (int)$segments[0];
}

// Mapear método y ruta
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($idFromPath !== null) {
        $_GET['id'] = $idFromPath;
        $controller->show();
    } else {
        $controller->index();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Crear vs actualizar/eliminar según si hay id en ruta
    if ($idFromPath === null) {
        $controller->store();
    } else {
        // Si sólo viene id + csrf_token, considerar eliminar
        $_POST['id'] = $idFromPath;
        $onlyIdAndCsrf = array_keys($_POST);
        sort($onlyIdAndCsrf);
        if ($onlyIdAndCsrf === ['csrf_token', 'id']) {
            $controller->destroy();
        } else {
            $controller->update();
        }
    }
} else {
    $controller->jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
}