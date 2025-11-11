<?php
namespace App\Controllers;

use App\Services\SubjectsService;
use PDO;

class SubjectsController
{
    private SubjectsService $service;
    public function __construct(PDO $pdo)
    {
        $this->service = new SubjectsService($pdo);
    }

    public function index(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $subjects = $this->service->all($page, 20);
        include __DIR__ . '/../Views/subjects/index.php';
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->assertCsrf();
            $ok = $this->service->create($_POST);
            \App\Utils\Logger::info('subject_create', ['nombre' => (string)($_POST['nombre'] ?? ''), 'clave' => (string)($_POST['clave'] ?? '')]);
            $_SESSION['flash'] = $ok ? 'Materia creada' : 'Error al crear materia';
            $_SESSION['flash_type'] = $ok ? 'success' : 'danger';
            header('Location: /subjects');
            return;
        }
        include __DIR__ . '/../Views/subjects/create.php';
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->assertCsrf();
            $id = (int)($_POST['id'] ?? 0);
            $ok = $this->service->update($id, $_POST);
            \App\Utils\Logger::info('subject_update', ['id' => $id]);
            $_SESSION['flash'] = $ok ? 'Materia actualizada' : 'Error al actualizar materia';
            $_SESSION['flash_type'] = $ok ? 'success' : 'danger';
            header('Location: /subjects');
            return;
        }
        header('Location: /subjects');
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->assertCsrf();
            $id = (int)($_POST['id'] ?? 0);
            $ok = $this->service->delete($id);
            \App\Utils\Logger::info('subject_delete', ['id' => $id]);
            $_SESSION['flash'] = $ok ? 'Materia eliminada' : 'Error al eliminar materia';
            $_SESSION['flash_type'] = $ok ? 'warning' : 'danger';
            header('Location: /subjects');
            return;
        }
        header('Location: /subjects');
    }

    private function assertCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            exit('CSRF inválido');
        }
    }
}