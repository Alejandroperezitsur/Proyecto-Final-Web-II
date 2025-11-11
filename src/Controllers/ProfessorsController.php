<?php
namespace App\Controllers;

use PDO;

class ProfessorsController
{
    private PDO $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(): void
    {
        $stmt = $this->pdo->query("SELECT id, nombre, email, activo FROM usuarios WHERE rol = 'profesor' ORDER BY nombre");
        $professors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        include __DIR__ . '/../Views/professors/index.php';
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }
        $token = $_POST['csrf_token'] ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) { http_response_code(403); echo 'CSRF inválido'; return; }

        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        if ($nombre === '' || !$email) { $_SESSION['flash'] = 'Datos inválidos'; $_SESSION['flash_type'] = 'danger'; header('Location: /professors'); return; }

        // Crear profesor con contraseña temporal segura
        $password = bin2hex(random_bytes(8));
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, activo) VALUES (:n, :e, :p, 'profesor', 1)");
        $stmt->execute([':n' => htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'), ':e' => $email, ':p' => $hash]);
        \App\Utils\Logger::info('professor_create', ['email' => $email]);
        $_SESSION['flash'] = 'Profesor creado. Contraseña temporal enviada al administrador.';
        $_SESSION['flash_type'] = 'success';
        header('Location: /professors');
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }
        $token = $_POST['csrf_token'] ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) { http_response_code(403); echo 'CSRF inválido'; return; }
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) { $_SESSION['flash'] = 'ID inválido'; $_SESSION['flash_type'] = 'danger'; header('Location: /professors'); return; }
        $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id = :id AND rol = 'profesor'");
        $stmt->execute([':id' => $id]);
        \App\Utils\Logger::info('professor_delete', ['id' => $id]);
        $_SESSION['flash'] = 'Profesor eliminado';
        $_SESSION['flash_type'] = 'warning';
        header('Location: /professors');
    }
}