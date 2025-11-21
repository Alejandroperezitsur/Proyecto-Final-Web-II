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
            if (!$user || !$user['activo'] || !password_verify($password, $user['password'])) { return null; }
            return [
                'id' => (int)$user['id'],
                'role' => $user['rol'],
                'name' => $user['nombre'] ?? '',
            ];
        }
        // Fallback: assume it's a matricula if not an email
        $al = $this->repo->findStudentByMatricula($identity);
        if ($al && $al['activo'] && password_verify($password, $al['password'])) {
            return [
                'id' => (int)$al['id'],
                'role' => 'alumno',
                'name' => $al['nombre'] ?? '',
            ];
        }
        $prof = $this->repo->findProfessorByMatricula($identity);
        if ($prof && $prof['activo'] && password_verify($password, $prof['password'])) {
            return [
                'id' => (int)$prof['id'],
                'role' => 'profesor',
                'name' => $prof['nombre'] ?? '',
            ];
        }
        return null;
        return null;
    }
}
