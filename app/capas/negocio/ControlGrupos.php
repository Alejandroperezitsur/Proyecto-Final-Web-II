<?php
/**
 * Wrapper en español para el modelo Grupo.
 */
namespace App\Capas\Negocio;

require_once __DIR__ . '/../../capas/datos/DatosGrupos.php';

use App\Capas\Datos\DatosGrupos;

class ControlGrupos
{
    private $datos;

    public function __construct()
    {
        $this->datos = new DatosGrupos();
    }

    public function obtenerCiclosDistintos(?int $profesorId = null): array
    {
        return $this->datos->ciclosDistintos($profesorId);
    }

    public function obtenerConJoins(int $pagina, int $limite, $profesorId = null)
    {
        return $this->datos->listarConJoins($pagina, $limite, $profesorId);
    }
}
