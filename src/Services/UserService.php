<?php
namespace App\Services;

use App\Repositories\UserRepository;

class UserService
{
    private UserRepository $repo;

    public function __construct(UserRepository $repo)
    {
        $this->repo = $repo;
    }

    public function authenticate(string $identity, string $password): ?array
    {
        $user = null;
        if (str_contains($identity, '@')) {
            $user = $this->repo->findAdminOrProfessorByEmail($identity);
        } else {
            $user = $this->repo->findStudentByMatricula($identity);
        }
        if (!$user || !$user['activo']) { return null; }
        if (!password_verify($password, $user['password'])) { return null; }
        return [
            'id' => (int)$user['id'],
            'role' => $user['rol'],
            'name' => $user['nombre'] ?? '',
        ];
    }
}