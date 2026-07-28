<?php

class AppConfig
{
    public static function url(string $path = ''): string
    {
        $baseUrl = getenv('APP_URL') ?: self::detectBaseUrl();
        $baseUrl = rtrim($baseUrl, '/');
        $path = '/' . ltrim($path, '/');

        return $baseUrl . $path;
    }

    private static function detectBaseUrl(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? null) == 443);

        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host;
    }
}