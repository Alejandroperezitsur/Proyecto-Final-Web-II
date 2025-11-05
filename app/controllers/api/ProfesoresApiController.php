<?php
require_once __DIR__ . '/../../controllers/Controller.php';
require_once __DIR__ . '/../../models/Grupo.php';
require_once __DIR__ . '/../../models/Calificacion.php';
require_once __DIR__ . '/../../models/Alumno.php';

class ProfesoresApiController extends Controller {
    private $grupoModel;
    private $calModel;
    private $alumnoModel;

    public function __construct() {
        $this->isApi = true;
        $this->grupoModel = new Grupo();
        $this->calModel = new Calificacion();
        $this->alumnoModel = new Alumno();
        $this->checkAuth();
        $this->checkRole(['profesor']);
    }

    // GET /api/profesores/grupos
    public function grupos() {
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 10;
        $profesorId = (int)($_SESSION['user_id'] ?? 0);
        try {
            $rows = $this->grupoModel->getWithJoins($page, $limit, $profesorId);
            $total = $this->grupoModel->countWithFilter($profesorId);
            $pagination = $this->getPaginationData($page, $total, $limit);
            $this->jsonResponse(['success' => true, 'data' => $rows, 'pagination' => $pagination]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Error al obtener grupos'], 500);
        }
    }

    // GET /api/profesores/grupos/{id}/alumnos
    public function alumnosGrupo($grupoId) {
        $profesorId = (int)($_SESSION['user_id'] ?? 0);
        $grupoId = (int)$grupoId;
        if ($grupoId <= 0) {
            $this->jsonResponse(['success' => false, 'error' => 'Grupo inválido'], 400);
        }
        // Validar que el grupo pertenece al profesor
        $pdo = $this->grupoModel->getDb();
        $stmt = $pdo->prepare('SELECT profesor_id FROM grupos WHERE id = :id');
        $stmt->execute([':id' => $grupoId]);
        $row = $stmt->fetch();
        if (!$row || (int)$row['profesor_id'] !== $profesorId) {
            $this->jsonResponse(['success' => false, 'error' => 'Acceso denegado al grupo'], 403);
        }
        // Listar alumnos y sus calificaciones actuales
        $sql = "SELECT a.id, a.matricula, a.nombre, a.apellido,
                       c.parcial1, c.parcial2, c.final, c.promedio
                FROM calificaciones c
                JOIN alumnos a ON a.id = c.alumno_id
                WHERE c.grupo_id = :grupo_id
                ORDER BY a.apellido, a.nombre";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':grupo_id' => $grupoId]);
        $rows = $stmt->fetchAll();
        $this->jsonResponse(['success' => true, 'data' => $rows]);
    }

    // POST /api/profesores/calificaciones (alumno_id, grupo_id, parcial, calificacion)
    public function updateCalificacion() {
        $this->validateCSRF();
        $profesorId = (int)($_SESSION['user_id'] ?? 0);
        $alumnoId = filter_input(INPUT_POST, 'alumno_id', FILTER_VALIDATE_INT);
        $grupoId = filter_input(INPUT_POST, 'grupo_id', FILTER_VALIDATE_INT);
        $parcial = trim(strip_tags((string)filter_input(INPUT_POST, 'parcial', FILTER_UNSAFE_RAW)));
        $calificacion = filter_input(INPUT_POST, 'calificacion', FILTER_VALIDATE_FLOAT);

        if (!$alumnoId || !$grupoId || !$parcial) {
            $this->jsonResponse(['success' => false, 'error' => 'Parámetros inválidos'], 400);
        }
        if (!in_array($parcial, ['parcial1','parcial2','final'], true)) {
            $this->jsonResponse(['success' => false, 'error' => 'Parcial inválido'], 400);
        }
        if (!is_numeric($calificacion) || $calificacion < 0 || $calificacion > 100) {
            $this->jsonResponse(['success' => false, 'error' => 'Calificación fuera de rango (0-100)'], 422);
        }
        // Validar propiedad del grupo
        $pdo = $this->grupoModel->getDb();
        $stmt = $pdo->prepare('SELECT profesor_id FROM grupos WHERE id = :id');
        $stmt->execute([':id' => $grupoId]);
        $row = $stmt->fetch();
        if (!$row || (int)$row['profesor_id'] !== $profesorId) {
            $this->jsonResponse(['success' => false, 'error' => 'Acceso denegado al grupo'], 403);
        }

        // Upsert de calificación
        $existing = $this->calModel->findOne($alumnoId, $grupoId);
        $data = ['alumno_id' => $alumnoId, 'grupo_id' => $grupoId, $parcial => $calificacion];
        $ok = false;
        if ($existing) {
            $ok = $this->calModel->update((int)$existing['id'], $data);
        } else {
            $ok = $this->calModel->create($data);
        }
        if (!$ok) {
            $this->jsonResponse(['success' => false, 'error' => 'No se pudo guardar la calificación'], 500);
        }
        // Devolver registro actualizado
        $updated = $this->calModel->findOne($alumnoId, $grupoId);
        $this->jsonResponse(['success' => true, 'data' => $updated]);
    }
}