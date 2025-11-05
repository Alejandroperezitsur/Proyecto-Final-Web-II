<?php
/**
 * Adaptador de acceso a datos para grupos (placeholder).
 */
namespace App\Capas\Datos;

require_once __DIR__ . '/../../models/Grupo.php';

class DatosGrupos
{
    private $modelo;

    public function __construct()
    {
        $this->modelo = new \Grupo();
    }

    public function ciclosDistintos(?int $profesorId = null): array
    {
        return $this->modelo->getDistinctCiclos($profesorId);
    }

    public function listarConJoins(int $pagina = 1, int $limite = 10, $profesorId = null)
    {
        return $this->modelo->getWithJoins($pagina, $limite, $profesorId);
    }

    public function contarGruposProfesor(int $profesorId): int 
    {
        return $this->modelo->countTeacherGroups($profesorId);
    }

    public function contarAlumnosProfesor(int $profesorId): int
    {
        return $this->modelo->countTeacherStudents($profesorId);
    }

    public function obtenerGruposActivosProfesor(int $profesorId): array 
    {
        return $this->modelo->getActiveTeacherGroups($profesorId);
    }
}
