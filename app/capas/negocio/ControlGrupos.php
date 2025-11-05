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

    // Alias para compatibilidad con vistas existentes
    public function ciclosDistintos(?int $profesorId = null): array
    {
        return $this->datos->ciclosDistintos($profesorId);
    }

    public function obtenerConJoins(int $pagina, int $limite, $profesorId = null)
    {
        return $this->datos->listarConJoins($pagina, $limite, $profesorId);
    }

    /**
     * Cuenta el número de grupos activos que tiene asignados un profesor
     */
    public function contarGruposProfesor(int $profesorId): int 
    {
        return $this->datos->contarGruposProfesor($profesorId);
    }

    /**
     * Cuenta el número total de alumnos en los grupos de un profesor
     */
    public function contarAlumnosProfesor(int $profesorId): int
    {
        return $this->datos->contarAlumnosProfesor($profesorId);
    }

    /**
     * Obtiene la lista de grupos activos de un profesor con estadísticas
     */
    public function obtenerGruposActivosProfesor(int $profesorId): array 
    {
        return $this->datos->obtenerGruposActivosProfesor($profesorId);
    }
}
