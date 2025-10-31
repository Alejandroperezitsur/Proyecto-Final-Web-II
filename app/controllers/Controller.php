<?php
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
            if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
                $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
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

    protected function jsonResponse($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
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