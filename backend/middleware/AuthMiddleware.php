<?php

class AuthMiddleware
{
    public static function handle(): void
    {
        try {
            $auth = new AuthService(
                new PegawaiModel(),
                new AdminModel()
            );

            $auth->user(); // validasi sesi
        } catch (Exception $e) {
            if (self::expectsJson()) {
                self::jsonError('Sesi login berakhir. Silakan login ulang.');
            }

            header('Location: /login');
            exit;
        }
    }

    private static function expectsJson(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    private static function jsonError(string $message): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'draw' => max(0, (int) ($_GET['draw'] ?? 0)),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => $message,
        ]);
        exit;
    }
}
