<?php
namespace Core;

class Url
{
    private static function prefix(): string
    {
        $app = Config::get('app');
        $url = $app['url'] ?? '';
        if (is_string($url) && $url !== '') {
            return rtrim($url, '/');
        }
        $base = $app['base_path'] ?? '';
        if (is_string($base) && $base !== '') {
            return rtrim($base, '/');
        }
        return '';
    }

    public static function route(string $route, array $params = []): string
    {
        $prefix = self::prefix();
        $query = '?route=' . urlencode($route);
        if (!empty($params)) {
            $query .= '&' . http_build_query($params);
        }
        return ($prefix !== '' ? $prefix . '/' : '') . $query;
    }

    public static function asset(string $path): string
    {
        $prefix = self::prefix();
        $clean = ltrim($path, '/');
        // Si ya incluye "assets/", respetar; en caso contrario, anteponer
        if (strpos($clean, 'assets/') !== 0) {
            $clean = 'assets/' . $clean;
        }
        return ($prefix !== '' ? $prefix . '/' : '') . $clean;
    }
}