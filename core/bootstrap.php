<?php
namespace Core;

// Intentar cargar Composer autoload si existe
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Cargar variables de entorno desde .env si existe
// Formato simple KEY=VALUE, ignora líneas vacías y comentarios (#)
function _sicenet_load_env(string $envPath): void {
    if (!file_exists($envPath)) return;
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        $pos = strpos($line, '=');
        if ($pos === false) continue;
        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));
        // Quitar comillas si están presentes
        if ((str_starts_with($val, '"') && str_ends_with($val, '"')) || (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
            $val = substr($val, 1, -1);
        }
        $_ENV[$key] = $val;
        putenv($key . '=' . $val);
    }
}

_sicenet_load_env(__DIR__ . '/../.env');

// Autoload sencillo para controllers, models y core
spl_autoload_register(function ($class) {
    $prefixes = [
        'Core\\' => __DIR__ . '/',
        'Controllers\\' => __DIR__ . '/../controllers/',
        'Models\\' => __DIR__ . '/../models/',
    ];
    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

require_once __DIR__ . '/../config/config.php';

// Inicializar base de datos y autoinstalación si aplica
Installer::ensureDatabaseReady();