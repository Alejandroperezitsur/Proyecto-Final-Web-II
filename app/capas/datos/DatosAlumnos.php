<?php
/**
 * Adaptador de acceso a datos para alumnos (placeholder).
 * Envuelve el modelo `Alumno` para centralizar llamadas a la capa de datos.
 */
namespace App\Capas\Datos;

require_once __DIR__ . '/../../models/Alumno.php';

class DatosAlumnos
{
    private $modelo;

    public function __construct()
    {
        $this->modelo = new \Alumno();
    }

    public function buscarPorMatricula(string $matricula)
    {
        return $this->modelo->findByMatricula($matricula);
    }

    public function listar(int $pagina = 1, int $limite = 20)
    {
        // Si el modelo no define getAll, este método podría ajustarse.
        if (method_exists($this->modelo, 'getAll')) {
            return $this->modelo->getAll($pagina, $limite);
        }
        // Fallback simple
        return [];
    }

    public function crear(array $datos)
    {
        if (method_exists($this->modelo, 'create')) {
            return $this->modelo->create($datos);
        }
        return false;
    }

    public function delete(int $id)
    {
        if (method_exists($this->modelo, 'delete')) {
            return $this->modelo->delete($id);
        }
        return false;
    }

    public function update(int $id, array $data)
    {
        if (method_exists($this->modelo, 'update')) {
            return $this->modelo->update($id, $data);
        }
        return false;
    }

    public function find(int $id)
    {
        if (method_exists($this->modelo, 'find')) {
            return $this->modelo->find($id);
        }
        return null;
    }

    public function searchByEstado(string $q, string $estado, int $page = 1, int $limit = 10)
    {
        if (method_exists($this->modelo, 'searchByEstado')) {
            return $this->modelo->searchByEstado($q, $estado, $page, $limit);
        }
        return [];
    }

    public function countSearchByEstado(string $q, string $estado)
    {
        if (method_exists($this->modelo, 'countSearchByEstado')) {
            return $this->modelo->countSearchByEstado($q, $estado);
        }
        return 0;
    }

    public function search(string $q, int $page = 1, int $limit = 10)
    {
        if (method_exists($this->modelo, 'search')) {
            return $this->modelo->search($q, $page, $limit);
        }
        return [];
    }

    public function countSearch(string $q)
    {
        if (method_exists($this->modelo, 'countSearch')) {
            return $this->modelo->countSearch($q);
        }
        return 0;
    }

    public function getAllByEstado(string $estado, int $page = 1, int $limit = 10)
    {
        if (method_exists($this->modelo, 'getAllByEstado')) {
            return $this->modelo->getAllByEstado($estado, $page, $limit);
        }
        return [];
    }

    public function countByEstado(string $estado)
    {
        if (method_exists($this->modelo, 'countByEstado')) {
            return $this->modelo->countByEstado($estado);
        }
        return 0;
    }

    public function count()
    {
        if (method_exists($this->modelo, 'count')) {
            return $this->modelo->count();
        }
        return 0;
    }
}
