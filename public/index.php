<?php
// Endurecer cookies de sesión antes de iniciar la sesión
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_ENV['APP_HTTPS']) && $_ENV['APP_HTTPS'] === 'true');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/../core/bootstrap.php';

use Core\Router;

$router = new Router();

// Cabeceras de seguridad básicas
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: no-referrer-when-downgrade');
// CSP más estricta: sin inline ni eval en JS; mantener inline en estilos por atributos style
header("Content-Security-Policy: default-src 'self' https: data; script-src 'self' https:; style-src 'self' https: 'unsafe-inline'; img-src 'self' https: data; font-src 'self' https: data; connect-src 'self' https:; object-src 'none'; base-uri 'self'; frame-ancestors 'self'");

// Rutas públicas
$router->get('/', 'AuthController@home');
$router->get('login/student', 'AuthController@studentLogin');
$router->post('login/student', 'AuthController@studentAuth');
$router->get('login/professor', 'AuthController@professorLogin');
$router->post('login/professor', 'AuthController@professorAuth');
$router->get('login/admin', 'AuthController@adminLogin');
$router->post('login/admin', 'AuthController@adminAuth');
$router->get('logout', 'AuthController@logout');

// Rutas protegidas
$router->get('student/dashboard', 'StudentController@dashboard');
$router->get('student/cardex', 'StudentController@cardex');
$router->get('student/grades', 'StudentController@grades');
$router->get('student/schedule', 'StudentController@schedule');
$router->get('student/reticula', 'StudentController@reticula');
$router->post('student/reinscripcion', 'StudentController@reinscripcion');

$router->get('professor/dashboard', 'ProfessorController@dashboard');
$router->get('professor/groups', 'ProfessorController@groups');
$router->get('professor/group', 'ProfessorController@group');
$router->post('professor/grades/update', 'ProfessorController@updateGrades');

$router->get('admin/dashboard', 'AdminController@dashboard');
$router->get('admin/stats', 'AdminController@stats');
$router->get('admin/crud', 'AdminController@crud');
$router->post('admin/crud/save', 'AdminController@crudSave');
$router->post('admin/crud/update', 'AdminController@crudUpdate');
$router->post('admin/crud/delete', 'AdminController@crudDelete');
// Exportaciones Admin (PDF/Excel)
$router->get('admin/export/pdf', 'AdminController@exportPDF');
$router->post('admin/export/pdf', 'AdminController@exportPDF');
$router->get('admin/export/excel', 'AdminController@exportExcel');
$router->post('admin/export/excel', 'AdminController@exportExcel');
// Control de reinscripción
$router->get('admin/toggleReinscripcion', 'AdminController@toggleReinscripcion');

// Despachar
$router->dispatch();