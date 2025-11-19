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
        $gid = isset($_GET['grupo_id']) ? (int)$_GET['grupo_id'] : null;
        $ciclo = isset($_GET['ciclo']) ? trim((string)$_GET['ciclo']) : null;
        $filters = ['gid' => $gid, 'ciclo' => $ciclo];
        $key = $this->cacheKey('promedios_ciclo', $filters);
        $cached = $this->getCache($key);
        if ($cached) {
            header('Content-Type: application/json');
            return json_encode(['ok' => true, 'data' => $cached]);
        }
        $sql = "SELECT g.ciclo, ROUND(AVG(c.final),2) AS promedio
                FROM calificaciones c
                JOIN grupos g ON g.id = c.grupo_id
                WHERE c.final IS NOT NULL";
        $params = [];
        if ($gid) { $sql .= ' AND g.id = :gid'; $params[':gid'] = $gid; }
        if ($ciclo) { $sql .= ' AND g.ciclo = :ciclo'; $params[':ciclo'] = $ciclo; }
        $sql .= ' GROUP BY g.ciclo ORDER BY g.ciclo';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
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
        $gid = isset($_GET['grupo_id']) ? (int)$_GET['grupo_id'] : null;
        $ciclo = isset($_GET['ciclo']) ? trim((string)$_GET['ciclo']) : null;
        $key = $this->cacheKey('desempeno_grupo', ['pid' => $pid, 'gid' => $gid, 'ciclo' => $ciclo]);
        $cached = $this->getCache($key);
        if ($cached) {
            header('Content-Type: application/json');
            return json_encode(['ok' => true, 'data' => $cached]);
        }
        $sql = "SELECT g.nombre AS grupo, ROUND(AVG(c.final),2) AS promedio
                FROM calificaciones c
                JOIN grupos g ON g.id = c.grupo_id
                WHERE c.final IS NOT NULL AND g.profesor_id = :pid";
        $params = [':pid' => $pid];
        if ($gid) { $sql .= ' AND g.id = :gid'; $params[':gid'] = $gid; }
        if ($ciclo) { $sql .= ' AND g.ciclo = :ciclo'; $params[':ciclo'] = $ciclo; }
        $sql .= ' GROUP BY g.id, g.nombre ORDER BY g.nombre';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
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
        $role = $_SESSION['role'] ?? '';
        $pid = (int)($_SESSION['user_id'] ?? 0);
        $ciclo = isset($_GET['ciclo']) ? trim((string)$_GET['ciclo']) : null;
        $gid = isset($_GET['grupo_id']) ? (int)$_GET['grupo_id'] : null;
        $filters = ['ciclo' => $ciclo, 'gid' => $gid, 'pid' => ($role==='profesor'?$pid:null)];
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
                WHERE 1=1";
        $params = [];
        if ($role === 'profesor' && $pid > 0) { $sql .= ' AND g.profesor_id = :pid'; $params[':pid'] = $pid; }
        if ($ciclo) { $sql .= ' AND g.ciclo = :ciclo'; $params[':ciclo'] = $ciclo; }
        if ($gid) { $sql .= ' AND g.id = :gid'; $params[':gid'] = $gid; }
        $sql .= ' GROUP BY m.id, m.nombre ORDER BY m.nombre';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
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
