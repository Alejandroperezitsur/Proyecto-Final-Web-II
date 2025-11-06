<?php
namespace Core;

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