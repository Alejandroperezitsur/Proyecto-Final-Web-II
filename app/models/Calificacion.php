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

    // Estadísticas para un alumno: materias inscritas, pendientes y promedio general
    public function getStudentStats(int $alumnoId, ?string $ciclo = null): array {
        $params = [':alumno_id' => $alumnoId];
        $whereCiclo = '';
        if ($ciclo !== null && $ciclo !== '') { $whereCiclo = ' AND g.ciclo = :ciclo'; $params[':ciclo'] = $ciclo; }
        // Materias inscritas (distinct materias por grupos donde tiene calificación)
        $sqlMaterias = "SELECT COUNT(DISTINCT m.id)
                        FROM calificaciones c
                        JOIN grupos g ON c.grupo_id = g.id
                        JOIN materias m ON g.materia_id = m.id
                        WHERE c.alumno_id = :alumno_id" . $whereCiclo;
        $stmt = $this->db->prepare($sqlMaterias);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $materias = (int)($stmt->fetchColumn() ?? 0);

        // Calificaciones pendientes: cualquier parcial o final NULL
        $sqlPend = "SELECT COUNT(*)
                    FROM calificaciones c
                    JOIN grupos g ON c.grupo_id = g.id
                    WHERE c.alumno_id = :alumno_id" . $whereCiclo . " AND (c.parcial1 IS NULL OR c.parcial2 IS NULL OR c.final IS NULL)";
        $stmt = $this->db->prepare($sqlPend);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $pendientes = (int)($stmt->fetchColumn() ?? 0);

        // Promedio general (final no nulo)
        $sqlProm = "SELECT AVG(c.final)
                    FROM calificaciones c
                    JOIN grupos g ON c.grupo_id = g.id
                    WHERE c.alumno_id = :alumno_id" . $whereCiclo . " AND c.final IS NOT NULL";
        $stmt = $this->db->prepare($sqlProm);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $promedio = $stmt->fetchColumn();
        return [
            'materias_inscritas' => $materias,
            'calificaciones_pendientes' => $pendientes,
            'promedio_general' => $promedio !== null ? round((float)$promedio, 2) : 0.0,
        ];
    }

    // Estadísticas para un profesor: grupos activos, alumnos totales, pendientes y promedio general
    public function getTeacherStats(int $profesorId, ?string $ciclo = null): array {
        $params = [':profesor_id' => $profesorId];
        $whereCiclo = '';
        if ($ciclo !== null && $ciclo !== '') { $whereCiclo = ' AND g.ciclo = :ciclo'; $params[':ciclo'] = $ciclo; }
        else { $whereCiclo = ' AND g.ciclo = (SELECT MAX(ciclo) FROM grupos)'; }

        // Grupos activos
        $sqlGrupos = "SELECT COUNT(*) FROM grupos g WHERE g.profesor_id = :profesor_id" . $whereCiclo;
        $stmt = $this->db->prepare($sqlGrupos);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $gruposActivos = (int)($stmt->fetchColumn() ?? 0);

        // Alumnos totales (distintos) en sus grupos
        $sqlAlumnos = "SELECT COUNT(DISTINCT c.alumno_id)
                       FROM calificaciones c
                       JOIN grupos g ON c.grupo_id = g.id
                       WHERE g.profesor_id = :profesor_id" . $whereCiclo;
        $stmt = $this->db->prepare($sqlAlumnos);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $alumnosTotales = (int)($stmt->fetchColumn() ?? 0);

        // Evaluaciones pendientes: filas con calificaciones incompletas en sus grupos
        $sqlPend = "SELECT COUNT(*)
                    FROM calificaciones c
                    JOIN grupos g ON c.grupo_id = g.id
                    WHERE g.profesor_id = :profesor_id" . $whereCiclo . " AND (c.parcial1 IS NULL OR c.parcial2 IS NULL OR c.final IS NULL)";
        $stmt = $this->db->prepare($sqlPend);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $pendientes = (int)($stmt->fetchColumn() ?? 0);

        // Promedio general del profesor (final no nulo)
        $sqlProm = "SELECT AVG(c.final)
                    FROM calificaciones c
                    JOIN grupos g ON c.grupo_id = g.id
                    WHERE g.profesor_id = :profesor_id" . $whereCiclo . " AND c.final IS NOT NULL";
        $stmt = $this->db->prepare($sqlProm);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $promedio = $stmt->fetchColumn();

        return [
            'grupos_activos' => $gruposActivos,
            'alumnos_totales' => $alumnosTotales,
            'evaluaciones_pendientes' => $pendientes,
            'promedio_general' => $promedio !== null ? round((float)$promedio, 2) : 0.0,
        ];
    }

    public function getWithFilters($page = 1, $limit = 10, array $filters = []) {
        $page = max(1, (int)$page);
        $limit = max(1, (int)$limit);
        $offset = ($page - 1) * $limit;
        $where = [];
        $params = [];
        if (!empty($filters['materia_id'])) { $where[] = 'g.materia_id = :materia_id'; $params[':materia_id'] = (int)$filters['materia_id']; }
        if (!empty($filters['grupo_id'])) { $where[] = 'c.grupo_id = :grupo_id'; $params[':grupo_id'] = (int)$filters['grupo_id']; }
        if (!empty($filters['alumno_id'])) { $where[] = 'c.alumno_id = :alumno_id'; $params[':alumno_id'] = (int)$filters['alumno_id']; }
        if (!empty($filters['q'])) {
            $where[] = '(a.matricula LIKE :q OR a.nombre LIKE :q OR a.apellido LIKE :q OR m.nombre LIKE :q OR m.clave LIKE :q OR g.nombre LIKE :q)';
            $params[':q'] = '%'.trim((string)$filters['q']).'%';
        }
        $whereSql = count($where) ? ('WHERE '.implode(' AND ', $where)) : '';
        $sql = "SELECT c.*, a.matricula AS alumno_matricula, a.nombre AS alumno_nombre, a.apellido AS alumno_apellido,
                       g.nombre AS grupo_nombre, g.ciclo AS grupo_ciclo, m.nombre AS materia_nombre, m.clave AS materia_clave
                FROM calificaciones c
                JOIN alumnos a ON c.alumno_id = a.id
                JOIN grupos g ON c.grupo_id = g.id
                JOIN materias m ON g.materia_id = m.id
                $whereSql
                ORDER BY m.nombre, g.nombre, a.apellido
                LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countWithFilters(array $filters = []) {
        $where = [];
        $params = [];
        if (!empty($filters['materia_id'])) { $where[] = 'g.materia_id = :materia_id'; $params[':materia_id'] = (int)$filters['materia_id']; }
        if (!empty($filters['grupo_id'])) { $where[] = 'c.grupo_id = :grupo_id'; $params[':grupo_id'] = (int)$filters['grupo_id']; }
        if (!empty($filters['alumno_id'])) { $where[] = 'c.alumno_id = :alumno_id'; $params[':alumno_id'] = (int)$filters['alumno_id']; }
        if (!empty($filters['q'])) {
            $where[] = '(a.matricula LIKE :q OR a.nombre LIKE :q OR a.apellido LIKE :q OR m.nombre LIKE :q OR m.clave LIKE :q OR g.nombre LIKE :q)';
            $params[':q'] = '%'.trim((string)$filters['q']).'%';
        }
        $whereSql = count($where) ? ('WHERE '.implode(' AND ', $where)) : '';
        $sql = "SELECT COUNT(*)
                FROM calificaciones c
                JOIN alumnos a ON c.alumno_id = a.id
                JOIN grupos g ON c.grupo_id = g.id
                JOIN materias m ON g.materia_id = m.id
                $whereSql";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
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