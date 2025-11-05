<?php
// Endurecimiento de sesión: cookies seguras, SameSite y modo estricto
if (session_status() === PHP_SESSION_NONE) {
    // Configuración defensiva antes de iniciar la sesión
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    // Samesite para mitigar CSRF básico en navegaciones
    // Para PHP >= 7.3 se puede pasar como opción; por compatibilidad, también seteamos ini.
    if (PHP_VERSION_ID >= 70300) {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'
        );
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.cookie_samesite', 'Lax');
    } else {
        // Fallback para versiones antiguas
        ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? '1' : '0');
        ini_set('session.cookie_samesite', 'Lax');
    }

    session_start();
}
?>