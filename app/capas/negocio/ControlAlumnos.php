<?php
/**
 * Wrapper en español para el modelo Alumno.
 */
namespace App\Capas\Negocio;

require_once __DIR__ . '/../../capas/datos/DatosAlumnos.php';

use App\Capas\Datos\DatosAlumnos;

class ControlAlumnos
{
    private $datos;

    public function __construct()
    {
        // Usamos el adaptador de datos en vez de instanciar el modelo directamente
        $this->datos = new DatosAlumnos();
    }

    public function buscarPorMatricula(string $matricula)
    {
        return $this->datos->buscarPorMatricula($matricula);
    }

    public function listar(int $pagina = 1, int $limite = 20)
    {
        return $this->datos->listar($pagina, $limite);
    }

    // Métodos compatibles con la API previa (nombrados como el modelo original)
    public function delete(int $id)
    {
        return $this->datos->delete($id);
    }

    public function update(int $id, array $data)
    {
        return $this->datos->update($id, $data);
    }

    public function create(array $data)
    {
        return $this->datos->crear($data);
    }

    public function find(int $id)
    {
        return $this->datos->find($id);
    }

    public function searchByEstado(string $q, string $estado, int $page = 1, int $limit = 10)
    {
        return $this->datos->searchByEstado($q, $estado, $page, $limit);
    }

    public function countSearchByEstado(string $q, string $estado)
    {
        return $this->datos->countSearchByEstado($q, $estado);
    }

    public function search(string $q, int $page = 1, int $limit = 10)
    {
        return $this->datos->search($q, $page, $limit);
    }

    public function countSearch(string $q)
    {
        return $this->datos->countSearch($q);
    }

    public function getAllByEstado(string $estado, int $page = 1, int $limit = 10)
    {
        return $this->datos->getAllByEstado($estado, $page, $limit);
    }

    public function countByEstado(string $estado)
    {
        return $this->datos->countByEstado($estado);
    }

    public function count()
    {
        return $this->datos->count();
    }
}
