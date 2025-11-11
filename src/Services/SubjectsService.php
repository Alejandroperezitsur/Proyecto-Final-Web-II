<?php
namespace App\Services;

use PDO;

class SubjectsService
{
    private PDO $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function count(): int
    {
        return (int)$this->pdo->query('SELECT COUNT(*) FROM materias')->fetchColumn();
    }

    public function all(int $page = 1, int $limit = 10): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $stmt = $this->pdo->prepare('SELECT * FROM materias ORDER BY nombre LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO materias (nombre, clave) VALUES (:nombre, :clave)');
        return $stmt->execute([
            ':nombre' => trim((string)($data['nombre'] ?? '')),
            ':clave' => trim((string)($data['clave'] ?? '')),
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare('UPDATE materias SET nombre = :nombre, clave = :clave WHERE id = :id');
        return $stmt->execute([
            ':id' => $id,
            ':nombre' => trim((string)($data['nombre'] ?? '')),
            ':clave' => trim((string)($data['clave'] ?? '')),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM materias WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }
}