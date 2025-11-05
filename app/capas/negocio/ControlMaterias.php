<?php
/**
 * Wrapper en español para el modelo Materia.
 */
namespace App\Capas\Negocio;

require_once __DIR__ . '/../../models/Materia.php';

class ControlMaterias
{
    private $modelo;

    public function __construct()
    {
        $this->modelo = new \Materia();
    }

    public function obtenerCatalogo()
    {
        return $this->modelo->getCatalog();
    }

    public function listar(int $pagina = 1, int $limite = 100)
    {
        if (method_exists($this->modelo, 'getAll')) {
            return $this->modelo->getAll($pagina, $limite);
        }
        return [];
    }

    // Aliases y métodos CRUD para satisfacer llamadas existentes en las vistas
    public function getCatalog()
    {
        return $this->modelo->getCatalog();
    }

    public function crear(array $data)
    {
        return $this->modelo->create($data);
    }

    public function eliminar(int $id)
    {
        return $this->modelo->delete($id);
    }

    public function actualizar(int $id, array $data)
    {
        return $this->modelo->update($id, $data);
    }

    public function find(int $id)
    {
        return $this->modelo->find($id);
    }

    public function count(string $where = '')
    {
        return $this->modelo->count($where);
    }
}
