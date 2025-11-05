<?php
/**
 * Adaptador de acceso a datos para calificaciones (placeholder).
 */
namespace App\Capas\Datos;

require_once __DIR__ . '/../../models/Calificacion.php';

class DatosCalificaciones
{
    private $modelo;

    public function __construct()
    {
        $this->modelo = new \Calificacion();
    }

    public function agregadosGlobales(): array
    {
        return $this->modelo->getGlobalAggregates();
    }

    public function promediosPorCiclo(): array
    {
        return $this->modelo->getAveragesByCiclo();
    }

    /**
     * Agregados detallados por ciclo (total, promedio, aprobados, reprobados) para todos los ciclos
     */
    public function obtenerAgregadosPorCicloDetallados(): array
    {
        if (method_exists($this->modelo, 'getAggregatesByCicloDetailed')) {
            return $this->modelo->getAggregatesByCicloDetailed();
        }
        return [];
    }

    // CRUD y consultas de apoyo usadas en vistas
    public function findOne(int $alumnoId, int $grupoId)
    {
        return $this->modelo->findOne($alumnoId, $grupoId);
    }

    public function crear(array $data)
    {
        return $this->modelo->create($data);
    }

    public function actualizar(int $id, array $data)
    {
        return $this->modelo->update($id, $data);
    }

    public function getByProfesor(int $profesorId, int $page = 1, int $limit = 10): array
    {
        return $this->modelo->getByProfesor($profesorId, $page, $limit);
    }

    public function countByProfesor(int $profesorId): int
    {
        return $this->modelo->countByProfesor($profesorId);
    }

    public function agregadosPorCiclo(string $ciclo): array
    {
        // Agregados para un ciclo específico
        $sql = "SELECT 
                    COUNT(CASE WHEN c.final IS NOT NULL THEN 1 END) AS total,
                    AVG(c.final) AS promedio,
                    SUM(CASE WHEN c.final >= 70 THEN 1 ELSE 0 END) AS aprobados,
                    SUM(CASE WHEN c.final < 70 THEN 1 ELSE 0 END) AS reprobados
                FROM calificaciones c
                JOIN grupos g ON c.grupo_id = g.id
                WHERE g.ciclo = :ciclo";
        $stmt = $this->modelo->getDb()->prepare($sql);
        $stmt->bindValue(':ciclo', $ciclo);
        $stmt->execute();
        $row = $stmt->fetch();
        return [
            'total' => (int)($row['total'] ?? 0),
            'promedio' => $row['promedio'] !== null ? round((float)$row['promedio'], 2) : 0.0,
            'aprobados' => (int)($row['aprobados'] ?? 0),
            'reprobados' => (int)($row['reprobados'] ?? 0),
        ];
    }

    public function promediosPorMateria(?string $ciclo = null): array
    {
        if ($ciclo === null || $ciclo === '') {
            return $this->modelo->getAveragesByMateria();
        }
        return $this->modelo->getAveragesByMateriaForCiclo($ciclo);
    }

    public function contarEvaluacionesPendientesProfesor(int $profesorId): int
    {
        // Calificaciones donde falta alguna evaluación final o parciales
        $sql = "SELECT COUNT(*) FROM calificaciones c
                JOIN grupos g ON c.grupo_id = g.id
                WHERE g.profesor_id = :profesor
                AND (c.final IS NULL OR c.parcial1 IS NULL OR c.parcial2 IS NULL)";
    $stmt = $this->modelo->getDb()->prepare($sql);
        $stmt->bindValue(':profesor', $profesorId);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function obtenerPromedioProfesor(int $profesorId): float
    {
        $sql = "SELECT AVG(c.promedio) as avgp
                FROM calificaciones c
                JOIN grupos g ON c.grupo_id = g.id
                WHERE g.profesor_id = :profesor
                AND c.promedio IS NOT NULL";
    $stmt = $this->modelo->getDb()->prepare($sql);
        $stmt->bindValue(':profesor', $profesorId);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row && $row['avgp'] !== null ? (float)$row['avgp'] : 0.0;
    }
}
