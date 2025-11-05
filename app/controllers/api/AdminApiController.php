<?php
require_once __DIR__ . '/../../controllers/Controller.php';
require_once __DIR__ . '/../../models/Usuario.php';
require_once __DIR__ . '/../../models/Materia.php';
require_once __DIR__ . '/../../models/Grupo.php';
require_once __DIR__ . '/../../models/Calificacion.php';

class AdminApiController extends Controller {
    private $usuarioModel;
    private $materiaModel;
    private $grupoModel;
    private $calModel;

    public function __construct() {
        $this->isApi = true;
        $this->usuarioModel = new Usuario();
        $this->materiaModel = new Materia();
        $this->grupoModel = new Grupo();
        $this->calModel = new Calificacion();
        $this->checkAuth();
        $this->checkRole(['admin']);
    }

    // GET listar por entidad
    public function index($entity) {
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 10;
        $q = trim(strip_tags((string)filter_input(INPUT_GET, 'q', FILTER_UNSAFE_RAW)));
        switch ($entity) {
            case 'usuarios':
                $rol = trim(strip_tags((string)filter_input(INPUT_GET, 'rol', FILTER_UNSAFE_RAW)));
                if ($rol && $q) {
                    $rows = $this->usuarioModel->searchByRole($rol, $q, $page, $limit);
                    $total = $this->usuarioModel->countByRoleSearch($rol, $q);
                } elseif ($rol) {
                    $rows = $this->usuarioModel->getAllByRole($rol, $page, $limit);
                    $total = $this->usuarioModel->countByRole($rol);
                } else {
                    $rows = $this->usuarioModel->getAll($page, $limit);
                    $total = $this->usuarioModel->count();
                }
                break;
            case 'alumnos':
                $grupoId = filter_input(INPUT_GET, 'grupo_id', FILTER_VALIDATE_INT);
                if ($grupoId) {
                    $pdo = $this->grupoModel->getDb();
                    $offset = ($page - 1) * $limit;
                    $sql = "SELECT a.id, a.matricula, a.nombre, a.apellido
                            FROM calificaciones c
                            JOIN alumnos a ON a.id = c.alumno_id
                            WHERE c.grupo_id = :grupo_id
                            ORDER BY a.apellido, a.nombre
                            LIMIT {$limit} OFFSET {$offset}";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':grupo_id', $grupoId);
                    $stmt->execute();
                    $rows = $stmt->fetchAll();
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM calificaciones WHERE grupo_id = :grupo_id");
                    $stmt->bindValue(':grupo_id', $grupoId);
                    $stmt->execute();
                    $total = (int)$stmt->fetchColumn();
                } else {
                    // Lista general de alumnos con búsqueda por q
                    require_once __DIR__ . '/AlumnosApiController.php';
                    $alumnosController = new AlumnosApiController();
                    // Reutilizamos el modelo directamente para consistencia
                    $alumnoModel = new \Alumno();
                    if ($q) {
                        $rows = $alumnoModel->search($q, $page, $limit);
                        $total = $alumnoModel->countSearch($q);
                    } else {
                        $rows = $alumnoModel->getAll($page, $limit);
                        $total = $alumnoModel->count();
                    }
                }
                break;
            case 'materias':
                if ($q) {
                    // Búsqueda simple por nombre/clave
                    $pdo = $this->materiaModel->getDb();
                    $offset = ($page - 1) * $limit;
                    $stmt = $pdo->prepare("SELECT * FROM materias WHERE nombre LIKE :q OR clave LIKE :q LIMIT {$limit} OFFSET {$offset}");
                    $stmt->bindValue(':q', '%'.$q.'%');
                    $stmt->execute();
                    $rows = $stmt->fetchAll();
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM materias WHERE nombre LIKE :q OR clave LIKE :q");
                    $stmt->bindValue(':q', '%'.$q.'%');
                    $stmt->execute();
                    $total = (int)$stmt->fetchColumn();
                } else {
                    $rows = $this->materiaModel->getAll($page, $limit);
                    $total = $this->materiaModel->count();
                }
                break;
            case 'grupos':
                $materiaId = filter_input(INPUT_GET, 'materia_id', FILTER_VALIDATE_INT);
                $rows = $this->grupoModel->getWithJoins($page, $limit, null, $materiaId);
                $total = $this->grupoModel->countWithFilter(null, $materiaId);
                break;
            case 'calificaciones':
                $filters = [
                    'materia_id' => filter_input(INPUT_GET, 'materia_id', FILTER_VALIDATE_INT) ?: null,
                    'grupo_id' => filter_input(INPUT_GET, 'grupo_id', FILTER_VALIDATE_INT) ?: null,
                    'alumno_id' => filter_input(INPUT_GET, 'alumno_id', FILTER_VALIDATE_INT) ?: null,
                    'q' => $q ?: null,
                ];
                $rows = $this->calModel->getWithFilters($page, $limit, $filters);
                $total = $this->calModel->countWithFilters($filters);
                break;
            default:
                $this->jsonResponse(['success' => false, 'error' => 'Entidad no válida'], 400);
        }
        $pagination = $this->getPaginationData($page, $total, $limit);
        $this->jsonResponse(['success' => true, 'data' => $rows, 'pagination' => $pagination]);
    }

    // GET detalle por id
    public function show($entity, $id) {
        $id = (int)$id;
        if ($id <= 0) { $this->jsonResponse(['success' => false, 'error' => 'ID inválido'], 400); }
        switch ($entity) {
            case 'usuarios': $row = $this->usuarioModel->find($id); break;
            case 'materias': $row = $this->materiaModel->find($id); break;
            case 'grupos': $row = $this->grupoModel->find($id); break;
            case 'calificaciones': $row = $this->calModel->find($id); break;
            default: $this->jsonResponse(['success' => false, 'error' => 'Entidad no válida'], 400);
        }
        if (!$row) { $this->jsonResponse(['success' => false, 'error' => 'No encontrado'], 404); }
        $this->jsonResponse(['success' => true, 'data' => $row]);
    }

    // POST crear
    public function store($entity) {
        $this->validateCSRF();
        $ok = false;
        switch ($entity) {
            case 'usuarios':
                $data = [
                    'matricula' => trim(strip_tags((string)filter_input(INPUT_POST, 'matricula', FILTER_UNSAFE_RAW))),
                    'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
                    'password' => filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW),
                    'rol' => trim(strip_tags((string)filter_input(INPUT_POST, 'rol', FILTER_UNSAFE_RAW))),
                    'activo' => filter_input(INPUT_POST, 'activo', FILTER_VALIDATE_INT) ?: 1,
                ];
                $ok = $this->usuarioModel->create($data);
                break;
            case 'materias':
                $data = [
                    'nombre' => trim(strip_tags((string)filter_input(INPUT_POST, 'nombre', FILTER_UNSAFE_RAW))),
                    'clave' => trim(strip_tags((string)filter_input(INPUT_POST, 'clave', FILTER_UNSAFE_RAW))),
                ];
                $ok = $this->materiaModel->create($data);
                break;
            case 'grupos':
                $data = [
                    'materia_id' => filter_input(INPUT_POST, 'materia_id', FILTER_VALIDATE_INT),
                    'profesor_id' => filter_input(INPUT_POST, 'profesor_id', FILTER_VALIDATE_INT),
                    'nombre' => trim(strip_tags((string)filter_input(INPUT_POST, 'nombre', FILTER_UNSAFE_RAW))),
                    'ciclo' => trim(strip_tags((string)filter_input(INPUT_POST, 'ciclo', FILTER_UNSAFE_RAW))),
                    'cupo' => filter_input(INPUT_POST, 'cupo', FILTER_VALIDATE_INT),
                ];
                $ok = $this->grupoModel->create($data);
                break;
            case 'calificaciones':
                $data = [
                    'alumno_id' => filter_input(INPUT_POST, 'alumno_id', FILTER_VALIDATE_INT),
                    'grupo_id' => filter_input(INPUT_POST, 'grupo_id', FILTER_VALIDATE_INT),
                    'parcial1' => filter_input(INPUT_POST, 'parcial1', FILTER_VALIDATE_FLOAT),
                    'parcial2' => filter_input(INPUT_POST, 'parcial2', FILTER_VALIDATE_FLOAT),
                    'final' => filter_input(INPUT_POST, 'final', FILTER_VALIDATE_FLOAT),
                ];
                $ok = $this->calModel->create($data);
                break;
            default:
                $this->jsonResponse(['success' => false, 'error' => 'Entidad no válida'], 400);
        }
        if ($ok) { $this->jsonResponse(['success' => true, 'message' => 'Creado correctamente'], 201); }
        $this->jsonResponse(['success' => false, 'error' => 'Error al crear'], 400);
    }

    // POST actualizar
    public function update($entity, $id) {
        $this->validateCSRF();
        $id = (int)$id;
        if ($id <= 0) { $this->jsonResponse(['success' => false, 'error' => 'ID inválido'], 400); }
        $ok = false;
        switch ($entity) {
            case 'usuarios':
                $data = [
                    'matricula' => trim(strip_tags((string)filter_input(INPUT_POST, 'matricula', FILTER_UNSAFE_RAW))),
                    'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
                    'password' => filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW),
                    'rol' => trim(strip_tags((string)filter_input(INPUT_POST, 'rol', FILTER_UNSAFE_RAW))),
                    'activo' => filter_input(INPUT_POST, 'activo', FILTER_VALIDATE_INT),
                ];
                $ok = $this->usuarioModel->update($id, $data);
                break;
            case 'materias':
                $data = [
                    'nombre' => trim(strip_tags((string)filter_input(INPUT_POST, 'nombre', FILTER_UNSAFE_RAW))),
                    'clave' => trim(strip_tags((string)filter_input(INPUT_POST, 'clave', FILTER_UNSAFE_RAW))),
                ];
                $ok = $this->materiaModel->update($id, $data);
                break;
            case 'grupos':
                $data = [
                    'materia_id' => filter_input(INPUT_POST, 'materia_id', FILTER_VALIDATE_INT),
                    'profesor_id' => filter_input(INPUT_POST, 'profesor_id', FILTER_VALIDATE_INT),
                    'nombre' => trim(strip_tags((string)filter_input(INPUT_POST, 'nombre', FILTER_UNSAFE_RAW))),
                    'ciclo' => trim(strip_tags((string)filter_input(INPUT_POST, 'ciclo', FILTER_UNSAFE_RAW))),
                    'cupo' => filter_input(INPUT_POST, 'cupo', FILTER_VALIDATE_INT),
                ];
                $ok = $this->grupoModel->update($id, $data);
                break;
            case 'calificaciones':
                $data = [
                    'parcial1' => filter_input(INPUT_POST, 'parcial1', FILTER_VALIDATE_FLOAT),
                    'parcial2' => filter_input(INPUT_POST, 'parcial2', FILTER_VALIDATE_FLOAT),
                    'final' => filter_input(INPUT_POST, 'final', FILTER_VALIDATE_FLOAT),
                ];
                $ok = $this->calModel->update($id, $data);
                break;
            default:
                $this->jsonResponse(['success' => false, 'error' => 'Entidad no válida'], 400);
        }
        if ($ok) { $this->jsonResponse(['success' => true, 'message' => 'Actualizado correctamente']); }
        $this->jsonResponse(['success' => false, 'error' => 'Error al actualizar'], 400);
    }

    // POST eliminar
    public function destroy($entity, $id) {
        $this->validateCSRF();
        $id = (int)$id;
        if ($id <= 0) { $this->jsonResponse(['success' => false, 'error' => 'ID inválido'], 400); }
        $ok = false;
        switch ($entity) {
            case 'usuarios': $ok = $this->usuarioModel->delete($id); break;
            case 'materias': $ok = $this->materiaModel->delete($id); break;
            case 'grupos': $ok = $this->grupoModel->delete($id); break;
            case 'calificaciones': $ok = $this->calModel->delete($id); break;
            default: $this->jsonResponse(['success' => false, 'error' => 'Entidad no válida'], 400);
        }
        if ($ok) { $this->jsonResponse(['success' => true, 'message' => 'Eliminado correctamente']); }
        $this->jsonResponse(['success' => false, 'error' => 'Error al eliminar'], 400);
    }
}