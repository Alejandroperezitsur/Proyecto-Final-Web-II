<?php
namespace Core;

class Config
{
    private static array $config = [
        'app' => [
            'name' => 'SICEnet',
        ],
        'db' => [
            'host' => '127.0.0.1',
            'name' => 'sicenet',
            'user' => 'root',
            'pass' => 'root',
        ],
    ];

    public static function get(string $key): array
    {
        return self::$config[$key] ?? [];
    }
}