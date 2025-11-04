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
}
