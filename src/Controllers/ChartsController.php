<?php
namespace App\Controllers;

use PDO;
use App\Utils\Logger;

class ChartsController
{
    private PDO $pdo;
    private int $ttlSeconds = 300; // 5 minutos
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $_SESSION['charts_cache'] = $_SESSION['charts_cache'] ?? [];
    }

    private function cacheKey(string $name, array $filters = []): string
    {
        return $name . ':' . md5(json_encode($filters));
    }

    private function getCache(string $key): ?array
    {
        $entry = $_SESSION['charts_cache'][$key] ?? null;
        if (!$entry) { return null; }
        if ((time() - (int)$entry['ts']) > $this->ttlSeconds) { return null; }
        return (array)$entry['data'];
    }

    private function setCache(string $key, array $data): void
    {
        $_SESSION['charts_cache'][$key] = ['ts' => time(), 'data' => $data];
    }

    public function averagesBySubject(): void
    {
        header('Content-Type: application/json');
        $sql = "SELECT m.nombre AS materia, ROUND(AVG(c.final),2) AS promedio
                FROM calificaciones c
                JOIN grupos g ON g.id = c.grupo_id
                JOIN materias m ON m.id = g.materia_id
                WHERE c.final IS NOT NULL
                GROUP BY m.id, m.nombre
                ORDER BY m.nombre";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rows);
    }

    // /api/charts/promedios-ciclo
    public function averagesByCycle(): string
    {
        $filters = [];
        $key = $this->cacheKey('promedios_ciclo', $filters);
        $cached = $this->getCache($key);
        if ($cached) {
            header('Content-Type: application/json');
            return json_encode(['ok' => true, 'data' => $cached]);
        }
        $sql = "SELECT g.ciclo, ROUND(AVG(c.final),2) AS promedio
                FROM calificaciones c
                JOIN grupos g ON g.id = c.grupo_id
                WHERE c.final IS NOT NULL
                GROUP BY g.ciclo
                ORDER BY g.ciclo";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data = [
            'labels' => array_map(fn($r) => $r['ciclo'], $rows),
            'data' => array_map(fn($r) => (float)$r['promedio'], $rows),
        ];
        $this->setCache($key, $data);
        Logger::info('chart_query', ['type' => 'promedios_ciclo']);
        header('Content-Type: application/json');
        return json_encode(['ok' => true, 'data' => $data]);
    }

    // /api/charts/desempeño-grupo (profesor)
    public function performanceByProfessorGroups(): string
    {
        $role = $_SESSION['role'] ?? '';
        $pid = (int)($_SESSION['user_id'] ?? 0);
        if ($role !== 'profesor' || $pid <= 0) {
            header('Content-Type: application/json');
            http_response_code(403);
            return json_encode(['ok' => false, 'message' => 'No autorizado']);
        }
        $key = $this->cacheKey('desempeno_grupo', ['pid' => $pid]);
        $cached = $this->getCache($key);
        if ($cached) {
            header('Content-Type: application/json');
            return json_encode(['ok' => true, 'data' => $cached]);
        }
        $sql = "SELECT g.nombre AS grupo, ROUND(AVG(c.final),2) AS promedio
                FROM calificaciones c
                JOIN grupos g ON g.id = c.grupo_id
                WHERE c.final IS NOT NULL AND g.profesor_id = :pid
                GROUP BY g.id, g.nombre
                ORDER BY g.nombre";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':pid' => $pid]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data = [
            'labels' => array_map(fn($r) => $r['grupo'], $rows),
            'data' => array_map(fn($r) => (float)$r['promedio'], $rows),
        ];
        $this->setCache($key, $data);
        Logger::info('chart_query', ['type' => 'desempeno_grupo', 'pid' => $pid]);
        header('Content-Type: application/json');
        return json_encode(['ok' => true, 'data' => $data]);
    }

    // /api/charts/reprobados
    public function failRateBySubject(): string
    {
        $filters = [];
        $key = $this->cacheKey('reprobados_materia', $filters);
        $cached = $this->getCache($key);
        if ($cached) {
            header('Content-Type: application/json');
            return json_encode(['ok' => true, 'data' => $cached]);
        }
        $sql = "SELECT m.nombre AS materia,
                       ROUND(SUM(CASE WHEN c.final IS NOT NULL AND c.final < 70 THEN 1 ELSE 0 END) / NULLIF(COUNT(CASE WHEN c.final IS NOT NULL THEN 1 END),0) * 100, 2) AS porcentaje
                FROM calificaciones c
                JOIN grupos g ON g.id = c.grupo_id
                JOIN materias m ON m.id = g.materia_id
                GROUP BY m.id, m.nombre
                ORDER BY m.nombre";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data = [
            'labels' => array_map(fn($r) => $r['materia'], $rows),
            'data' => array_map(fn($r) => (float)$r['porcentaje'], $rows),
        ];
        $this->setCache($key, $data);
        Logger::info('chart_query', ['type' => 'reprobados_materia']);
        header('Content-Type: application/json');
        return json_encode(['ok' => true, 'data' => $data]);
    }
}