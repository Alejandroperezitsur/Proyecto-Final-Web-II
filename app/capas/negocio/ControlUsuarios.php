<?php
/**
 * Wrapper en español para el modelo Usuario.
 */
namespace App\Capas\Negocio;

require_once __DIR__ . '/../../models/Usuario.php';

class ControlUsuarios
{
    private $modelo;

    public function __construct()
    {
        $this->modelo = new \Usuario();
    }

    public function obtenerPorId(int $id)
    {
        return $this->modelo->find($id);
    }

    public function listar(int $pagina = 1, int $limite = 100, string $where = '')
    {
        if (method_exists($this->modelo, 'getAll')) {
            return $this->modelo->getAll($pagina, $limite, $where);
        }
        return [];
    }
}
