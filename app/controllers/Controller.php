<?php
require_once __DIR__ . '/../../includes/response.php';
abstract class Controller {
    protected $model;
    protected $isApi = false;

    protected function checkAuth() {
        require_once __DIR__ . '/../init.php';
        if (!isset($_SESSION['user_id'])) {
            if ($this->isApi) {
                $this->jsonResponse(['success' => false, 'error' => 'No autorizado'], 401);
            } else {
                header('Location: /index.php');
                exit;
            }
        }
    }

    protected function checkRole($roles) {
        if (!in_array($_SESSION['user_role'], (array)$roles)) {
            if ($this->isApi) {
                $this->jsonResponse(['success' => false, 'error' => 'Acceso denegado'], 403);
            } else {
                header('Location: /index.php?code=403');
                exit;
            }
        }
    }

    protected function validateCSRF() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postToken = $_POST['csrf_token'] ?? null;
            $sessionToken = $_SESSION['csrf_token'] ?? null;
            if (!is_string($postToken) || !is_string($sessionToken) || !hash_equals($sessionToken, $postToken)) {
                if ($this->isApi) {
                    $this->jsonResponse(['success' => false, 'error' => 'Token CSRF inválido'], 400);
                } else {
                    header('Location: /index.php?code=400');
                    exit;
                }
            }
        }
    }

    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function jsonResponse($data, $code = 200) {
        // Delegar al helper global para unificar formato y flags
        jsonResponse($data, $code);
    }

    protected function redirect($url) {
        header("Location: $url");
        exit;
    }

    protected function view($template, $data = []) {
        if ($this->isApi) {
            $this->jsonResponse($data);
        }
        
        extract($data);
        $csrf_token = $this->generateCSRFToken();
        
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . "/../views/$template.php";
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    protected function getPaginationData($page, $total, $limit) {
        $totalPages = ceil($total / $limit);
        return [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'hasNextPage' => $page < $totalPages,
            'hasPrevPage' => $page > 1
        ];
    }
}