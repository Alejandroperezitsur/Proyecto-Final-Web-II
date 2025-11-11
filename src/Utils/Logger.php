<?php
namespace App\Utils;

class Logger
{
    private static function path(): string
    {
        $base = __DIR__ . '/../../logs/app.log';
        return $base;
    }

    public static function info(string $message, array $context = []): void
    {
        $entry = [
            'ts' => date('c'),
            'user_id' => $_SESSION['user_id'] ?? null,
            'role' => $_SESSION['role'] ?? null,
            'path' => $_SERVER['REQUEST_URI'] ?? null,
            'msg' => $message,
            'ctx' => $context,
        ];
        $line = json_encode($entry) . PHP_EOL;
        @file_put_contents(self::path(), $line, FILE_APPEND | LOCK_EX);
    }
}