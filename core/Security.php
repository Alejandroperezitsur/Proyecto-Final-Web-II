<?php
namespace Core;

class Security
{
    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function input(string $key, string $method = 'POST', int $filter = FILTER_SANITIZE_SPECIAL_CHARS)
    {
        $source = $method === 'GET' ? $_GET : $_POST;
        $value = $source[$key] ?? null;
        if ($value === null) return null;
        if (is_array($value)) return $value; // para arrays (p.ej. materias[])
        return filter_var($value, $filter);
    }
}