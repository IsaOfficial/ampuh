<?php

class RoleMiddleware
{
    public static function handle(string $roles): void
    {
        $user = Session::get('user');

        if (!$user || empty($user['role'])) {
            if (self::expectsJson()) {
                self::jsonError('Sesi login berakhir. Silakan login ulang.');
            }

            http_response_code(401);
            view('errors/401');
            exit;
        }

        $userRole = strtolower(trim($user['role']));
        $allowedRoles = array_map(
            fn($r) => strtolower(trim($r)),
            explode('|', $roles)
        );

        if (!in_array($userRole, $allowedRoles, true)) {
            if (self::expectsJson()) {
                self::jsonError('Akses hanya untuk role yang diizinkan.');
            }

            http_response_code(403);
            view('errors/403');
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
