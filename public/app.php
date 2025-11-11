<?php
// Entry point moderno con router y vistas. Compatible con XAMPP.

// Autoload Composer si existe
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    // Autoloader PSR-4 simple para "App\\" → "src/"
    spl_autoload_register(function ($class) {
        $prefix = 'App\\';
        $baseDir = __DIR__ . '/../src/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) { return; }
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) { require $file; }
    });
}

// Reutiliza la conexión existente
require_once __DIR__ . '/../config/db.php';

use App\Kernel;
use App\Http\Router;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\RateLimitMiddleware;
use App\Repositories\UserRepository;
use App\Services\UserService;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ReportsController;
use App\Controllers\GradesController;
use App\Controllers\StudentsController;
use App\Controllers\Api\KpiController;
use App\Controllers\Api\StudentController;
use App\Controllers\ChartsController;
use App\Controllers\CatalogsController;
use App\Controllers\ProfessorsController;

Kernel::boot();
$pdo = \Database::getInstance()->getConnection();

$router = new Router();

// Dependencias
$userRepo = new UserRepository($pdo);
$userService = new UserService($userRepo);
$auth = new AuthController($userService);
$dashboard = new DashboardController();
$reports = new ReportsController($pdo);
$grades = new GradesController($pdo);
$students = new StudentsController($pdo);
$kpi = new KpiController($pdo);
$studentApi = new StudentController($pdo);
$charts = new ChartsController($pdo);
$catalogs = new CatalogsController($pdo);
$professorsCtl = new ProfessorsController($pdo);

// Rutas públicas
$router->get('/login', fn() => $auth->showLogin());
$router->post('/login', fn() => $auth->login(), [RateLimitMiddleware::limit('login', 20, 600)]);
$router->get('/logout', fn() => $auth->logout());

// Rutas autenticadas
$router->get('/', fn() => $dashboard->index(), [AuthMiddleware::requireAuth()]);
$router->get('/dashboard', fn() => $dashboard->index(), [AuthMiddleware::requireAuth()]);

// Admin
$router->get('/reports', fn() => $reports->index(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
$router->post('/reports/export/csv', fn() => $reports->exportCsv(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
$router->post('/reports/export/pdf', fn() => $reports->exportPdf(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
$router->get('/reports/summary', fn() => $reports->summary(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
$router->get('/api/kpis/admin', fn() => $kpi->admin(), [AuthMiddleware::requireRole('admin')]);
$router->get('/api/charts/promedios-materias', fn() => $charts->averagesBySubject(), [AuthMiddleware::requireRole('admin')]);
$router->get('/api/charts/promedios-ciclo', fn() => $charts->averagesByCycle(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
$router->get('/api/charts/desempeño-grupo', fn() => $charts->performanceByProfessorGroups((int)($_SESSION['user_id'] ?? 0)), [AuthMiddleware::requireRole('profesor')]);
$router->get('/api/charts/reprobados', fn() => $charts->failRateBySubject(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);

// Gestión de profesores (migración de profesores.php)
$router->get('/professors', fn() => $professorsCtl->index(), [AuthMiddleware::requireRole('admin')]);
$router->post('/professors/create', fn() => $professorsCtl->create(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('prof_create', 20, 600)]);
$router->post('/professors/delete', fn() => $professorsCtl->delete(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('prof_delete', 20, 600)]);

// Catálogos (para selects dinámicos)
$router->get('/api/catalogs/subjects', fn() => $catalogs->subjects(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
$router->get('/api/catalogs/professors', fn() => $catalogs->professors(), [AuthMiddleware::requireRole('admin')]);
$router->get('/api/catalogs/students', fn() => $catalogs->students(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
$router->get('/api/catalogs/groups', function () use ($catalogs) {
    $profId = (int)($_GET['profesor'] ?? ($_SESSION['user_id'] ?? 0));
    return $catalogs->groupsByProfessor($profId);
}, [AuthMiddleware::requireAnyRole(['admin','profesor'])]);

// Profesor
$router->get('/grades', fn() => $grades->index(), [AuthMiddleware::requireRole('profesor')]);
$router->get('/grades/bulk', fn() => $grades->showBulkForm(), [AuthMiddleware::requireRole('profesor')]);
$router->post('/grades/bulk', fn() => $grades->processBulkUpload(), [AuthMiddleware::requireRole('profesor'), RateLimitMiddleware::limit('grades_bulk', 20, 600)]);
$router->get('/grades/bulk-log', fn() => $grades->downloadBulkLog(), [AuthMiddleware::requireRole('profesor')]);
$router->post('/grades/create', fn() => $grades->create(), [AuthMiddleware::requireRole('profesor'), RateLimitMiddleware::limit('grades_create', 30, 600)]);
$router->get('/api/kpis/profesor', fn() => $kpi->profesorDashboard((int)($_SESSION['user_id'] ?? 0)), [AuthMiddleware::requireRole('profesor')]);

// Alumno - API del panel
$router->get('/api/alumno/carga', fn() => $studentApi->carga(), [AuthMiddleware::requireRole('alumno')]);
$router->get('/api/alumno/estadisticas', fn() => $studentApi->estadisticas(), [AuthMiddleware::requireRole('alumno')]);
$router->get('/api/alumno/chart', fn() => $studentApi->chart(), [AuthMiddleware::requireRole('alumno')]);

// Migración de alumnos.php → nueva ruta
$router->get('/alumnos', fn() => $students->index(), [AuthMiddleware::requireRole('admin')]);

// CRUD Subjects/Groups
$router->get('/subjects', fn() => (new App\Controllers\SubjectsController($pdo))->index(), [AuthMiddleware::requireRole('admin')]);
$router->post('/subjects/create', fn() => (new App\Controllers\SubjectsController($pdo))->create(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('subjects_create', 30, 600)]);
$router->post('/subjects/delete', fn() => (new App\Controllers\SubjectsController($pdo))->delete(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('subjects_delete', 30, 600)]);

$router->get('/groups', fn() => (new App\Controllers\GroupsController($pdo))->index(), [AuthMiddleware::requireRole('admin')]);
$router->post('/groups/create', fn() => (new App\Controllers\GroupsController($pdo))->create(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('groups_create', 30, 600)]);
$router->post('/groups/delete', fn() => (new App\Controllers\GroupsController($pdo))->delete(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('groups_delete', 30, 600)]);

// Despachar
$router->dispatch();