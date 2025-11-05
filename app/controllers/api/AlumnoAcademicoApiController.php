<?php
require_once __DIR__ . '/../../controllers/Controller.php';
require_once __DIR__ . '/../../models/Calificacion.php';
require_once __DIR__ . '/../../models/Grupo.php';
require_once __DIR__ . '/../../models/Materia.php';

class AlumnoAcademicoApiController extends Controller {
    private $calModel;
    private $grupoModel;
    private $materiaModel;

    public function __construct() {
        $this->isApi = true;
        $this->calModel = new Calificacion();
        $this->grupoModel = new Grupo();
        $this->materiaModel = new Materia();
        $this->checkAuth();
        $this->checkRole(['alumno']);
    }

    private function carreraPrefixesFromMatricula($matricula) {
        $map = [
            'S' => ['INF','CSI','PROG','BD'],
            'I' => ['IND','EST','ADM'],
            'C' => ['CIV','EST','MAT'],
            'M' => ['MEC','MAT','EST'],
            'Q' => ['QUI','QIM','MAT'],
            'E' => ['ELE','ELC','DIG'],
            'A' => ['AMB','BIO','MAT'],
        ];
        $key = strtoupper(substr($matricula ?? '', 0, 1));
        return $map[$key] ?? [];
    }

    // GET carga académica por ciclo (usando grupos donde el alumno tiene calificaciones)
    public function carga() {
        $alumnoId = (int)($_SESSION['user_id'] ?? 0);
        $matricula = (string)($_SESSION['user_identifier'] ?? '');
        $ciclo = trim(strip_tags((string)filter_input(INPUT_GET, 'ciclo', FILTER_UNSAFE_RAW))) ?: '';
        $prefixes = $this->carreraPrefixesFromMatricula($matricula);
        $pdo = $this->grupoModel->getDb();
        $params = [':alumno_id' => $alumnoId];
        $whereCiclo = '';
        if ($ciclo !== '') { $whereCiclo = ' AND g.ciclo = :ciclo'; $params[':ciclo'] = $ciclo; }
        $prefixConds = [];
        $i = 0; foreach ($prefixes as $p) { $k = ':p'.$i++; $prefixConds[] = "m.clave LIKE $k"; $params[$k] = $p.'%'; }
        $wherePrefix = count($prefixConds) ? (' AND ('.implode(' OR ', $prefixConds).')') : '';
        $sql = "SELECT DISTINCT g.id AS grupo_id, g.nombre AS grupo, g.ciclo,
                       m.id AS materia_id, m.nombre AS materia, m.clave
                FROM calificaciones c
                JOIN grupos g ON c.grupo_id = g.id
                JOIN materias m ON g.materia_id = m.id
                WHERE c.alumno_id = :alumno_id $whereCiclo $wherePrefix
                ORDER BY m.nombre, g.nombre";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $this->jsonResponse(['success' => true, 'data' => $rows]);
    }

    // GET calificaciones por ciclo (semestre equivalente a ciclo)
    public function calificaciones() {
        $alumnoId = (int)($_SESSION['user_id'] ?? 0);
        $ciclo = trim(strip_tags((string)filter_input(INPUT_GET, 'ciclo', FILTER_UNSAFE_RAW))) ?: '';
        $pdo = $this->calModel->getDb();
        $params = [':alumno_id' => $alumnoId];
        $whereCiclo = '';
        if ($ciclo !== '') { $whereCiclo = ' AND g.ciclo = :ciclo'; $params[':ciclo'] = $ciclo; }
        $sql = "SELECT m.nombre AS materia, m.clave, g.nombre AS grupo, g.ciclo,
                       c.parcial1, c.parcial2, c.final, c.promedio
                FROM calificaciones c
                JOIN grupos g ON c.grupo_id = g.id
                JOIN materias m ON g.materia_id = m.id
                WHERE c.alumno_id = :alumno_id $whereCiclo
                ORDER BY g.ciclo, m.nombre";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $this->jsonResponse(['success' => true, 'data' => $rows]);
    }

    // GET estadísticas del alumno autenticado
    public function estadisticas() {
        $alumnoId = (int)($_SESSION['user_id'] ?? 0);
        $ciclo = trim(strip_tags((string)filter_input(INPUT_GET, 'ciclo', FILTER_UNSAFE_RAW))) ?: null;
        $stats = $this->calModel->getStudentStats($alumnoId, $ciclo);
        $this->jsonResponse(['success' => true, 'data' => $stats]);
    }
}