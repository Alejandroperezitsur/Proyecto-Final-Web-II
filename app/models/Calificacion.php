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

    // Agregados globales: total, promedio final, aprobados/reprobados
    public function getGlobalAggregates() {
        $sql = "SELECT 
                    COUNT(CASE WHEN final IS NOT NULL THEN 1 END) AS total,
                    AVG(final) AS promedio,
                    SUM(CASE WHEN final >= 70 THEN 1 ELSE 0 END) AS aprobados,
                    SUM(CASE WHEN final < 70 THEN 1 ELSE 0 END) AS reprobados
                FROM {$this->table}";
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch();
        return [
            'total' => (int)($row['total'] ?? 0),
            'promedio' => $row['promedio'] !== null ? round((float)$row['promedio'], 2) : 0.0,
            'aprobados' => (int)($row['aprobados'] ?? 0),
            'reprobados' => (int)($row['reprobados'] ?? 0),
        ];
    }

    // Promedios por ciclo
    public function getAveragesByCiclo() {
        $sql = "SELECT g.ciclo AS ciclo, COUNT(*) AS count, AVG(c.final) AS promedio
                FROM {$this->table} c
                JOIN grupos g ON c.grupo_id = g.id
                WHERE c.final IS NOT NULL
                GROUP BY g.ciclo
                ORDER BY g.ciclo";
        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll();
        return array_map(function($r){
            return [
                'ciclo' => (string)($r['ciclo'] ?? ''),
                'count' => (int)($r['count'] ?? 0),
                'promedio' => $r['promedio'] !== null ? round((float)$r['promedio'], 2) : 0.0,
            ];
        }, $rows);
    }

    // Promedios por materia
    public function getAveragesByMateria() {
        $sql = "SELECT m.id, m.nombre, m.clave, COUNT(*) AS count, AVG(c.final) AS promedio
                FROM {$this->table} c
                JOIN grupos g ON c.grupo_id = g.id
                JOIN materias m ON g.materia_id = m.id
                WHERE c.final IS NOT NULL
                GROUP BY m.id, m.nombre, m.clave
                ORDER BY m.nombre";
        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll();
        return array_map(function($r){
            return [
                'id' => (int)($r['id'] ?? 0),
                'nombre' => (string)($r['nombre'] ?? ''),
                'clave' => (string)($r['clave'] ?? ''),
                'count' => (int)($r['count'] ?? 0),
                'promedio' => $r['promedio'] !== null ? round((float)$r['promedio'], 2) : 0.0,
            ];
        }, $rows);
    }

    // Agregados detallados por ciclo: count, promedio, aprobados, reprobados
    public function getAggregatesByCicloDetailed() {
        $sql = "SELECT g.ciclo AS ciclo,
                       COUNT(CASE WHEN c.final IS NOT NULL THEN 1 END) AS total,
                       AVG(c.final) AS promedio,
                       SUM(CASE WHEN c.final >= 70 THEN 1 ELSE 0 END) AS aprobados,
                       SUM(CASE WHEN c.final < 70 THEN 1 ELSE 0 END) AS reprobados
                FROM {$this->table} c
                JOIN grupos g ON c.grupo_id = g.id
                GROUP BY g.ciclo
                ORDER BY g.ciclo";
        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll();
        return array_map(function($r){
            return [
                'ciclo' => (string)($r['ciclo'] ?? ''),
                'total' => (int)($r['total'] ?? 0),
                'promedio' => $r['promedio'] !== null ? round((float)$r['promedio'], 2) : 0.0,
                'aprobados' => (int)($r['aprobados'] ?? 0),
                'reprobados' => (int)($r['reprobados'] ?? 0),
            ];
        }, $rows);
    }

    // Promedios por materia para un ciclo específico
    public function getAveragesByMateriaForCiclo($ciclo) {
        $sql = "SELECT m.id, m.nombre, m.clave, COUNT(*) AS count, AVG(c.final) AS promedio
                FROM {$this->table} c
                JOIN grupos g ON c.grupo_id = g.id
                JOIN materias m ON g.materia_id = m.id
                WHERE c.final IS NOT NULL AND g.ciclo = :ciclo
                GROUP BY m.id, m.nombre, m.clave
                ORDER BY promedio DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':ciclo', $ciclo);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        return array_map(function($r){
            return [
                'id' => (int)($r['id'] ?? 0),
                'nombre' => (string)($r['nombre'] ?? ''),
                'clave' => (string)($r['clave'] ?? ''),
                'count' => (int)($r['count'] ?? 0),
                'promedio' => $r['promedio'] !== null ? round((float)$r['promedio'], 2) : 0.0,
            ];
        }, $rows);
    }

    private function filterAllowedFields($data) {
        return array_intersect_key($data, array_flip($this->allowedFields));
    }

    // Listar calificaciones por alumno, retorna filas con grupo_id
    public function getGrupoIdsByAlumno($alumnoId) {
        $sql = "SELECT grupo_id FROM calificaciones WHERE alumno_id = :alumno_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':alumno_id' => $alumnoId]);
        return array_map(function($r){ return (int)$r['grupo_id']; }, $stmt->fetchAll());
    }

    // Contar inscritos por grupo
    public function countByGrupo($grupoId) {
        $sql = "SELECT COUNT(*) AS cnt FROM calificaciones WHERE grupo_id = :grupo_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':grupo_id' => $grupoId]);
        return (int)($stmt->fetchColumn() ?? 0);
    }

    // Eliminar inscripción del alumno en un grupo
    public function deleteByAlumnoGrupo($alumnoId, $grupoId) {
        $sql = "DELETE FROM calificaciones WHERE alumno_id = :alumno_id AND grupo_id = :grupo_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':alumno_id' => $alumnoId, ':grupo_id' => $grupoId]);
    }

    // Materias aprobadas por alumno (retorna claves de materia con final >= 70)
    public function getMateriasAprobadasClavesByAlumno($alumnoId) {
        $sql = "SELECT DISTINCT m.clave
                FROM calificaciones c
                JOIN grupos g ON c.grupo_id = g.id
                JOIN materias m ON g.materia_id = m.id
                WHERE c.alumno_id = :alumno_id AND c.final IS NOT NULL AND c.final >= 70";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':alumno_id' => $alumnoId]);
        return array_map(function($r){ return (string)$r['clave']; }, $stmt->fetchAll());
    }
}