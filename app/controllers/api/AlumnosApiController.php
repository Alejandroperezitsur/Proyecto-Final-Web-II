<?php
require_once __DIR__ . '/../../controllers/Controller.php';
require_once __DIR__ . '/../../models/Alumno.php';

class AlumnosApiController extends Controller {
    public function __construct() {
        $this->isApi = true;
        $this->model = new Alumno();
        $this->checkAuth();
    }

    public function index() {
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 10;
        $search = trim(strip_tags((string)filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW)));

        try {
            if ($search) {
                $alumnos = $this->model->search($search, $page, $limit);
            } else {
                $alumnos = $this->model->getAll($page, $limit);
            }

            $total = $this->model->count();
            $pagination = $this->getPaginationData($page, $total, $limit);

            $this->jsonResponse([
                'success' => true,
                'data' => $alumnos,
                'pagination' => $pagination
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener los alumnos'
            ], 500);
        }
    }

    public function show() {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'ID inválido'
            ], 400);
        }

        try {
            $alumno = $this->model->getWithCalificaciones($id);
            if (!$alumno) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Alumno no encontrado'
                ], 404);
            }

            $this->jsonResponse([
                'success' => true,
                'data' => $alumno
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener el alumno'
            ], 500);
        }
    }

    public function store() {
        $this->checkRole(['admin']);
        $this->validateCSRF();

        $data = [
            'matricula' => trim(strip_tags((string)filter_input(INPUT_POST, 'matricula', FILTER_UNSAFE_RAW))),
            'nombre' => trim(strip_tags((string)filter_input(INPUT_POST, 'nombre', FILTER_UNSAFE_RAW))),
            'apellido' => trim(strip_tags((string)filter_input(INPUT_POST, 'apellido', FILTER_UNSAFE_RAW))),
            'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
            'fecha_nac' => trim(strip_tags((string)filter_input(INPUT_POST, 'fecha_nac', FILTER_UNSAFE_RAW)))
        ];

        if (isset($_FILES['foto'])) {
            $data['foto'] = $_FILES['foto'];
        }

        try {
            if ($this->model->create($data)) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Alumno creado exitosamente'
                ], 201);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Error al crear el alumno',
                    'errors' => $_SESSION['errors'] ?? []
                ], 400);
            }
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al crear el alumno'
            ], 500);
        }
    }

    public function update() {
        $this->checkRole(['admin']);
        $this->validateCSRF();

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'ID inválido'
            ], 400);
        }

        $data = [
            'matricula' => trim(strip_tags((string)filter_input(INPUT_POST, 'matricula', FILTER_UNSAFE_RAW))),
            'nombre' => trim(strip_tags((string)filter_input(INPUT_POST, 'nombre', FILTER_UNSAFE_RAW))),
            'apellido' => trim(strip_tags((string)filter_input(INPUT_POST, 'apellido', FILTER_UNSAFE_RAW))),
            'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
            'fecha_nac' => trim(strip_tags((string)filter_input(INPUT_POST, 'fecha_nac', FILTER_UNSAFE_RAW)))
        ];

        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $data['foto'] = $_FILES['foto'];
        }

        try {
            if ($this->model->update($id, $data)) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Alumno actualizado exitosamente'
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Error al actualizar el alumno',
                    'errors' => $_SESSION['errors'] ?? []
                ], 400);
            }
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al actualizar el alumno'
            ], 500);
        }
    }

    public function destroy() {
        $this->checkRole(['admin']);
        $this->validateCSRF();

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'ID inválido'
            ], 400);
        }

        try {
            if ($this->model->delete($id)) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Alumno eliminado exitosamente'
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Error al eliminar el alumno'
                ], 400);
            }
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al eliminar el alumno'
            ], 500);
        }
    }
}