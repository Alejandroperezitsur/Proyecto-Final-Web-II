<?php
namespace App\Controllers;

use PDO;

class CatalogsController
{
    private PDO $pdo;
    private int $ttlSeconds = 300; // cache ligera 5 minutos

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $_SESSION['catalog_cache'] = $_SESSION['catalog_cache'] ?? [];
    }

    private function getCache(string $key): ?array
    {
        $entry = $_SESSION['catalog_cache'][$key] ?? null;
        if (!$entry) { return null; }
        if ((time() - (int)$entry['ts']) > $this->ttlSeconds) { return null; }
        return (array)$entry['data'];
    }

    private function setCache(string $key, array $data): void
    {
        $_SESSION['catalog_cache'][$key] = ['ts' => time(), 'data' => $data];
    }

    public function subjects(): void
    {
        header('Content-Type: application/json');
        $cached = $this->getCache('subjects');
        if ($cached !== null) { echo json_encode($cached); return; }
        $stmt = $this->pdo->query('SELECT id, nombre, clave FROM materias ORDER BY nombre');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->setCache('subjects', $rows);
        echo json_encode($rows);
    }

    public function professors(): void
    {
        header('Content-Type: application/json');
        $cached = $this->getCache('professors');
        if ($cached !== null) { echo json_encode($cached); return; }
        $stmt = $this->pdo->query("SELECT id, nombre, email FROM usuarios WHERE rol = 'profesor' AND activo = 1 ORDER BY nombre");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->setCache('professors', $rows);
        echo json_encode($rows);
    }

    public function students(): void
    {
        header('Content-Type: application/json');
        $cached = $this->getCache('students');
        if ($cached !== null) { echo json_encode($cached); return; }
        $stmt = $this->pdo->query('SELECT id, matricula, CONCAT(nombre, " ", apellido) AS nombre FROM alumnos WHERE activo = 1 ORDER BY apellido, nombre');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->setCache('students', $rows);
        echo json_encode($rows);
    }

    public function groupsByProfessor(int $profesorId): void
    {
        header('Content-Type: application/json');
        $key = 'groups_' . $profesorId;
        $cached = $this->getCache($key);
        if ($cached !== null) { echo json_encode($cached); return; }
        $stmt = $this->pdo->prepare('SELECT g.id, g.nombre, g.ciclo, m.nombre AS materia FROM grupos g JOIN materias m ON m.id = g.materia_id WHERE g.profesor_id = :p ORDER BY g.ciclo DESC, m.nombre, g.nombre');
        $stmt->execute([':p' => $profesorId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->setCache($key, $rows);
        echo json_encode($rows);
    }
}