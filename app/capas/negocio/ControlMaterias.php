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
}
