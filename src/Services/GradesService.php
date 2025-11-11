<?php
namespace App\Services;

use PDO;

class GradesService
{
    private PDO $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function globalAverage(): float
    {
        $avg = $this->pdo->query('SELECT AVG(final) FROM calificaciones WHERE final IS NOT NULL')->fetchColumn();
        return $avg !== null ? round((float)$avg, 2) : 0.0;
    }

    public function upsertGrade(int $alumnoId, int $grupoId, array $data): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM calificaciones WHERE alumno_id = :alumno AND grupo_id = :grupo LIMIT 1');
        $stmt->execute([':alumno' => $alumnoId, ':grupo' => $grupoId]);
        $existingId = $stmt->fetchColumn();
        if ($existingId) {
            $stmt = $this->pdo->prepare('UPDATE calificaciones SET parcial1 = :p1, parcial2 = :p2, final = :fin WHERE id = :id');
            return $stmt->execute([
                ':id' => (int)$existingId,
                ':p1' => $data['parcial1'] ?? null,
                ':p2' => $data['parcial2'] ?? null,
                ':fin' => $data['final'] ?? null,
            ]);
        }
        $stmt = $this->pdo->prepare('INSERT INTO calificaciones (alumno_id, grupo_id, parcial1, parcial2, final) VALUES (:alumno, :grupo, :p1, :p2, :fin)');
        return $stmt->execute([
            ':alumno' => $alumnoId,
            ':grupo' => $grupoId,
            ':p1' => $data['parcial1'] ?? null,
            ':p2' => $data['parcial2'] ?? null,
            ':fin' => $data['final'] ?? null,
        ]);
    }
}