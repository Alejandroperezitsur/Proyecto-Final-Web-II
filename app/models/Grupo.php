<?php
require_once __DIR__ . '/Model.php';

class Grupo extends Model {
    protected $table = 'grupos';

    private $allowedFields = ['materia_id', 'profesor_id', 'nombre', 'ciclo'];

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

    public function getWithJoins($page = 1, $limit = 10, $profesorId = null) {
        $page = max(1, (int)$page);
        $limit = max(1, (int)$limit);
        $offset = ($page - 1) * $limit;

        $where = '';
        $params = [];
        if ($profesorId) {
            $where = 'WHERE g.profesor_id = :profesor_id';
            $params[':profesor_id'] = $profesorId;
        }

        $sql = "SELECT g.*, m.nombre AS materia_nombre, m.clave AS materia_clave,
                       u.email AS profesor_email, u.matricula AS profesor_matricula
                FROM grupos g
                JOIN materias m ON g.materia_id = m.id
                JOIN usuarios u ON g.profesor_id = u.id
                $where
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countWithFilter($profesorId = null) {
        $where = '';
        $params = [];
        if ($profesorId) {
            $where = 'WHERE profesor_id = :profesor_id';
            $params[':profesor_id'] = $profesorId;
        }
        $sql = "SELECT COUNT(*) FROM grupos $where";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    // Catálogo de ciclos distintos, opcionalmente filtrado por profesor
    public function getDistinctCiclos($profesorId = null) {
        $where = '';
        $params = [];
        if ($profesorId) {
            $where = 'WHERE profesor_id = :profesor_id';
            $params[':profesor_id'] = $profesorId;
        }
        $sql = "SELECT DISTINCT ciclo FROM grupos $where ORDER BY ciclo";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $ciclos = [];
        foreach ($rows as $r) {
            $val = trim((string)($r['ciclo'] ?? ''));
            if ($val !== '') { $ciclos[] = $val; }
        }
        return $ciclos;
    }

    private function filterAllowedFields($data) {
        return array_intersect_key($data, array_flip($this->allowedFields));
    }
}