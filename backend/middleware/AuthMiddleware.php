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
            header('Location: /login');
            exit;
        }
    }
}
