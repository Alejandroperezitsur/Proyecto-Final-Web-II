<?php
namespace App\Controllers\Api;

use PDO;

class ProfessorController
{
    private PDO $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function perfil(): string
    {
        $id = (int)($_SESSION['user_id'] ?? 0);
        header('Content-Type: application/json');
        $stmt = $this->pdo->prepare("SELECT id, matricula, nombre, email FROM usuarios WHERE id = :id AND rol = 'profesor' LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return json_encode(['success' => true, 'data' => $row]);
    }
}
