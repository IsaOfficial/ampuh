<?php

class RoleMiddleware
{
    public static function handle(string $roles): void
    {
        $user = Session::get('user');

        if (!$user || empty($user['role'])) {
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
            http_response_code(403);
            view('errors/403');
            exit;
        }
    }
}
