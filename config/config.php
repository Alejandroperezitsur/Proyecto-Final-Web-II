<?php
namespace Core;

class Config
{
    private static function env(string $key, $default = null)
    {
        $val = getenv($key);
        return $val !== false ? $val : $default;
    }

    private static ?array $config = null;

    private static function load(): void
    {
        if (self::$config !== null) return;
        self::$config = [
            'app' => [
                'name' => self::env('APP_NAME', 'SICEnet'),
                // Base path (ej. "/PWBII/SICEnet/public") para despliegues en subdirectorio; vacío por defecto
                'base_path' => self::env('APP_BASE_PATH', ''),
                'url' => self::env('APP_URL', ''),
            ],
            'db' => [
                'host' => self::env('DB_HOST', '127.0.0.1'),
                'name' => self::env('DB_NAME', 'sicenet'),
                'user' => self::env('DB_USER', 'root'),
                'pass' => self::env('DB_PASS', 'root'),
            ],
        ];
    }

    public static function get(string $key): array
    {
        self::load();
        return self::$config[$key] ?? [];
    }
}