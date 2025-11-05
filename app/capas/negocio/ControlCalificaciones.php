<?php
/**
 * Wrapper en español para el modelo Calificacion.
 */
namespace App\Capas\Negocio;

require_once __DIR__ . '/../../capas/datos/DatosCalificaciones.php';

use App\Capas\Datos\DatosCalificaciones;

class ControlCalificaciones
{
    private $datos;

    public function __construct()
    {
        $this->datos = new DatosCalificaciones();
    }

    public function obtenerAgregadosGlobales(): array
    {
        return $this->datos->agregadosGlobales();
    }

    public function obtenerPromediosPorCiclo(): array
    {
        return $this->datos->promediosPorCiclo();
    }

    public function obtenerAgregadosPorCiclo(string $ciclo): array
    {
        if (method_exists($this->datos, 'agregadosPorCiclo')) {
            return $this->datos->agregadosPorCiclo($ciclo);
        }
        return [];
    }

    public function obtenerAgregadosPorCicloDetallados(): array
    {
        // El modelo original exponía este método; si el adaptador lo necesita, puede añadirse.
        if (method_exists($this->datos, 'obtenerAgregadosPorCicloDetallados')) {
            return $this->datos->obtenerAgregadosPorCicloDetallados();
        }
        return [];
    }

    public function promediosPorMateria(?string $ciclo = null): array
    {
        return $this->datos->promediosPorMateria($ciclo);
    }

    public function contarEvaluacionesPendientes(int $profesorId): int
    {
        if (method_exists($this->datos, 'contarEvaluacionesPendientesProfesor')) {
            return $this->datos->contarEvaluacionesPendientesProfesor($profesorId);
        }
        return 0;
    }

    public function obtenerPromedioProfesor(int $profesorId): float
    {
        if (method_exists($this->datos, 'obtenerPromedioProfesor')) {
            return $this->datos->obtenerPromedioProfesor($profesorId);
        }
        return 0.0;
    }

    // CRUD y consultas de apoyo usadas en vistas
    public function findOne(int $alumnoId, int $grupoId)
    {
        return $this->datos->findOne($alumnoId, $grupoId);
    }

    public function crear(array $data)
    {
        return $this->datos->crear($data);
    }

    public function actualizar(int $id, array $data)
    {
        return $this->datos->actualizar($id, $data);
    }

    public function getByProfesor(int $profesorId, int $page = 1, int $limit = 10): array
    {
        return $this->datos->getByProfesor($profesorId, $page, $limit);
    }

    public function countByProfesor(int $profesorId): int
    {
        return $this->datos->countByProfesor($profesorId);
    }
}
