<?php
namespace App;

use App\Http\SecurityHeaders;

class Kernel
{
    public static function boot(): void
    {
        // Session hardening
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_strict_mode', '1');
        // Estricto para evitar CSRF en contextos de terceros
        ini_set('session.cookie_samesite', 'Strict');
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            ini_set('session.cookie_secure', '1');
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        SecurityHeaders::apply();
    }
}