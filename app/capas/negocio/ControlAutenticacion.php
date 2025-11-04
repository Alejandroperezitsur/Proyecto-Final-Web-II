<?php
/**
 * Wrapper en español para el controlador de autenticación existente.
 * Provee métodos con nombres en español para facilitar la migración
 * hacia una estructura de capas (presentación -> negocio -> datos).
 */
namespace App\Capas\Negocio;

require_once __DIR__ . '/../../controllers/AuthController.php';

use AuthController;

class ControlAutenticacion
{
    private $auth;

    public function __construct()
    {
        // Reutilizamos el controlador existente para no duplicar lógica
        $this->auth = new \AuthController();
    }

    public function iniciarSesion(string $identificador, string $contrasena): bool
    {
        return $this->auth->login($identificador, $contrasena);
    }

    public function requerirAutenticacion(): void
    {
        $this->auth->requireAuth();
    }

    public function obtenerUsuarioActual(): array
    {
        return $this->auth->getCurrentUser();
    }

    public function generarTokenCSRF(): string
    {
        return $this->auth->generateCSRFToken();
    }

    public function validarTokenCSRF(string $token): bool
    {
        return $this->auth->validateCSRFToken($token);
    }
}
