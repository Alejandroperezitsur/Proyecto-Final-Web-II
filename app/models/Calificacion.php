<?php
require_once __DIR__ . '/Model.php';

class Calificacion extends Model {
    protected $table = 'calificaciones';

    private $allowedFields = ['alumno_id', 'grupo_id', 'parcial1', 'parcial2', 'final'];

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

    public function findOne($alumnoId, $grupoId) {
        $sql = "SELECT * FROM {$this->table} WHERE alumno_id = :alumno AND grupo_id = :grupo LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':alumno', $alumnoId);
        $stmt->bindValue(':grupo', $grupoId);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getByProfesor($profesorId, $page = 1, $limit = 10) {
        $page = max(1, (int)$page);
        $limit = max(1, (int)$limit);
        $offset = ($page - 1) * $limit;
        $sql = "SELECT c.*, a.matricula AS alumno_matricula, a.nombre AS alumno_nombre, a.apellido AS alumno_apellido,
                       g.nombre AS grupo_nombre, g.ciclo AS grupo_ciclo, m.nombre AS materia_nombre, m.clave AS materia_clave
                FROM calificaciones c
                JOIN alumnos a ON c.alumno_id = a.id
                JOIN grupos g ON c.grupo_id = g.id
                JOIN materias m ON g.materia_id = m.id
                WHERE g.profesor_id = :profesor
                LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':profesor', $profesorId);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countByProfesor($profesorId) {
        $sql = "SELECT COUNT(*)
                FROM calificaciones c
                JOIN grupos g ON c.grupo_id = g.id
                WHERE g.profesor_id = :profesor";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':profesor', $profesorId);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    private function filterAllowedFields($data) {
        return array_intersect_key($data, array_flip($this->allowedFields));
    }
}