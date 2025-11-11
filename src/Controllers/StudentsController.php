<?php
namespace App\Controllers;

use PDO;

class StudentsController
{
    private PDO $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $limit = 20;
        $offset = max(0, ($page - 1) * $limit);
        $stmt = $this->pdo->prepare('SELECT id, matricula, nombre, apellido FROM alumnos ORDER BY apellido, nombre LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        include __DIR__ . '/../Views/students/index.php';
    }
}