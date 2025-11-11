<?php
namespace App\Http\Middleware;

class RateLimitMiddleware
{
    /**
     * Simple session-based rate limiter.
     * @param string $key Unique key for the action (e.g., 'login')
     * @param int $maxAttempts Max attempts allowed in window
     * @param int $windowSeconds Window length in seconds
     */
    public static function limit(string $key, int $maxAttempts = 10, int $windowSeconds = 600): callable
    {
        return function () use ($key, $maxAttempts, $windowSeconds) {
            $_SESSION['rate_limit'] = $_SESSION['rate_limit'] ?? [];
            $now = time();
            $entries = array_filter((array)($_SESSION['rate_limit'][$key] ?? []), fn($ts) => ($now - (int)$ts) < $windowSeconds);
            if (count($entries) >= $maxAttempts) {
                http_response_code(429);
                echo 'Demasiados intentos, intenta más tarde.';
                return false;
            }
            $entries[] = $now;
            $_SESSION['rate_limit'][$key] = $entries;
            return true;
        };
    }
}