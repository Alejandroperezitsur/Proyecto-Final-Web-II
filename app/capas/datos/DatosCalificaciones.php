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
