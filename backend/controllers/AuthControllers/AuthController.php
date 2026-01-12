<?php

class AuthController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService(
            new PegawaiModel(),
            new AdminModel()
        );
    }

    public function processLogin(): void
    {
        $result = $this->auth->authenticate(
            $_POST['identifier'] ?? '',
            $_POST['password'] ?? ''
        );

        if (!$result['success']) {
            Session::flash('flash', [
                'type' => 'error',
                'message' => $result['message']
            ]);
            header('Location: /login');
            exit;
        }

        Session::regenerate();

        Session::set('user', [
            'id'   => $result['user']['id'],
            'role' => $result['user']['role']
        ]);

        header(
            $result['user']['role'] === 'admin'
                ? 'Location: /admin/dashboard'
                : 'Location: /pegawai/dashboard'
        );
        exit;
    }

    public function logout(): void
    {
        Session::destroy();
        Session::regenerate();
        header('Location: /login');
        exit;
    }
}
