<?php
namespace App\Repositories;

use PDO;

class UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findAdminOrProfessorByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, email, nombre, password, rol, activo FROM usuarios WHERE email = :email AND rol IN ('admin','profesor') LIMIT 1");
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findStudentByMatricula(string $matricula): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, matricula, nombre, password, 'alumno' AS rol, activo FROM alumnos WHERE matricula = :m LIMIT 1");
        $stmt->execute([':m' => $matricula]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}