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
}
