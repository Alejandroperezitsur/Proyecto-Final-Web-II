<?php
require_once __DIR__ . '/Model.php';

class Grupo extends Model {
    protected $table = 'grupos';

    private $allowedFields = ['materia_id', 'profesor_id', 'nombre', 'ciclo', 'cupo'];

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

    public function getWithJoins($page = 1, $limit = 10, $profesorId = null, $materiaId = null) {
        $page = max(1, (int)$page);
        $limit = max(1, (int)$limit);
        $offset = ($page - 1) * $limit;

        $whereClauses = [];
        $params = [];
        if ($profesorId) {
            $whereClauses[] = 'g.profesor_id = :profesor_id';
            $params[':profesor_id'] = $profesorId;
        }
        if ($materiaId) {
            $whereClauses[] = 'g.materia_id = :materia_id';
            $params[':materia_id'] = (int)$materiaId;
        }
        $where = count($whereClauses) ? ('WHERE '.implode(' AND ', $whereClauses)) : '';

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

    public function countWithFilter($profesorId = null, $materiaId = null) {
        $whereClauses = [];
        $params = [];
        if ($profesorId) {
            $whereClauses[] = 'profesor_id = :profesor_id';
            $params[':profesor_id'] = $profesorId;
        }
        if ($materiaId) {
            $whereClauses[] = 'materia_id = :materia_id';
            $params[':materia_id'] = (int)$materiaId;
        }
        $where = count($whereClauses) ? ('WHERE '.implode(' AND ', $whereClauses)) : '';
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

    /**
     * Cuenta los grupos activos de un profesor
     */
    public function countTeacherGroups(int $profesorId): int {
        $sql = "SELECT COUNT(*) FROM grupos 
                WHERE profesor_id = :profesor_id 
                AND ciclo = (SELECT MAX(ciclo) FROM grupos)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':profesor_id', $profesorId);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Cuenta el total de alumnos en los grupos de un profesor
     */
    public function countTeacherStudents(int $profesorId): int {
    // Algunas instalaciones generan calificaciones sin tabla de inscripciones.
    // Contamos alumnos distintos usando la tabla `calificaciones` si existe.
    $sql = "SELECT COUNT(DISTINCT c.alumno_id)
        FROM calificaciones c
        JOIN grupos g ON c.grupo_id = g.id
        WHERE g.profesor_id = :profesor_id
        AND g.ciclo = (SELECT MAX(ciclo) FROM grupos)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':profesor_id', $profesorId);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Obtiene los grupos activos de un profesor con estadísticas
     */
    public function getActiveTeacherGroups(int $profesorId): array {
        // Usar la tabla `calificaciones` (campo `promedio` generado) para estadísticas.
        $sql = "SELECT g.id, m.nombre as materia, g.nombre as grupo,
                       COUNT(DISTINCT c.alumno_id) as alumnos,
                       ROUND(AVG(c.promedio), 2) as promedio
                FROM grupos g
                JOIN materias m ON g.materia_id = m.id
                LEFT JOIN calificaciones c ON c.grupo_id = g.id
                WHERE g.profesor_id = :profesor_id 
                AND g.ciclo = (SELECT MAX(ciclo) FROM grupos)
                GROUP BY g.id, m.nombre, g.nombre
                ORDER BY m.nombre, g.nombre";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':profesor_id', $profesorId);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Obtener grupos por ciclo filtrados por prefijos de clave de materia
    public function getByCicloAndPrefixes($ciclo, array $prefixes) {
        $conditions = [];
        $params = [':ciclo' => $ciclo];
        $i = 0;
        foreach ($prefixes as $p) {
            $key = ':p' . $i++;
            $conditions[] = "m.clave LIKE $key";
            $params[$key] = $p . '%';
        }
        $wherePrefix = count($conditions) ? ('(' . implode(' OR ', $conditions) . ')') : '1=1';
        $sql = "SELECT g.*, m.nombre AS materia_nombre, m.clave AS materia_clave,
                       u.email AS profesor_email, u.matricula AS profesor_matricula
                FROM grupos g
                JOIN materias m ON g.materia_id = m.id
                JOIN usuarios u ON g.profesor_id = u.id
                WHERE g.ciclo = :ciclo AND $wherePrefix
                ORDER BY m.nombre, g.nombre";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        return $stmt->fetchAll();
    }
}