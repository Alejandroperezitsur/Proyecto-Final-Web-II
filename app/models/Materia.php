<?php
require_once __DIR__ . '/Model.php';

class Materia extends Model {
    protected $table = 'materias';

    private $allowedFields = ['nombre', 'clave'];

    public function __construct() {
        parent::__construct();
    }

    public function create($data) {
        $data = $this->filterAllowedFields($data);
        return parent::create($data);
    }

    public function update($id, $data) {
        $data = $this->filterAllowedFields($data);
        return parent::update($id, $data);
    }

    private function filterAllowedFields($data) {
        return array_intersect_key($data, array_flip($this->allowedFields));
    }
}