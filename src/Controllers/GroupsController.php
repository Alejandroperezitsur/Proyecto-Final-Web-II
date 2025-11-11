<?php
namespace App\Controllers;

use App\Services\GroupsService;
use PDO;

class GroupsController
{
    private GroupsService $service;
    private PDO $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->service = new GroupsService($pdo);
    }

    public function index(): void
    {
        $groups = $this->service->count() ? $this->listAll() : [];
        include __DIR__ . '/../Views/groups/index.php';
    }

    private function listAll(): array
    {
        $sql = "SELECT g.id, g.nombre, g.ciclo, g.cupo, m.nombre AS materia, u.nombre AS profesor
                FROM grupos g
                JOIN materias m ON m.id = g.materia_id
                JOIN usuarios u ON u.id = g.profesor_id
                ORDER BY g.ciclo DESC, m.nombre, g.nombre";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->assertCsrf();
            $ok = $this->service->create($_POST);
            \App\Utils\Logger::info('group_create', [
                'materia_id' => (int)($_POST['materia_id'] ?? 0),
                'profesor_id' => (int)($_POST['profesor_id'] ?? 0),
                'nombre' => (string)($_POST['nombre'] ?? ''),
                'ciclo' => (string)($_POST['ciclo'] ?? ''),
            ]);
            $_SESSION['flash'] = $ok ? 'Grupo creado' : ('Error al crear grupo: ' . ($this->service->getLastError() ?? 'validación fallida'));
            $_SESSION['flash_type'] = $ok ? 'success' : 'danger';
            header('Location: /groups');
            return;
        }
        include __DIR__ . '/../Views/groups/create.php';
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->assertCsrf();
            $id = (int)($_POST['id'] ?? 0);
            $ok = $this->service->update($id, $_POST);
            \App\Utils\Logger::info('group_update', ['id' => $id]);
            $_SESSION['flash'] = $ok ? 'Grupo actualizado' : ('Error al actualizar grupo: ' . ($this->service->getLastError() ?? 'validación fallida'));
            $_SESSION['flash_type'] = $ok ? 'success' : 'danger';
            header('Location: /groups');
            return;
        }
        header('Location: /groups');
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->assertCsrf();
            $id = (int)($_POST['id'] ?? 0);
            $ok = $this->service->delete($id);
            \App\Utils\Logger::info('group_delete', ['id' => $id]);
            $_SESSION['flash'] = $ok ? 'Grupo eliminado' : 'Error al eliminar grupo';
            $_SESSION['flash_type'] = $ok ? 'warning' : 'danger';
            header('Location: /groups');
            return;
        }
        header('Location: /groups');
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