<?php

class AuthController
{
    private PegawaiModel $pegawai;

    public function __construct()
    {
        $this->pegawai = new PegawaiModel();
    }

    private function authenticate(string $identifier, string $password): array
    {
        $identifier = trim($identifier);

        if ($identifier === '' || $password === '') {
            return [
                'success' => false,
                'message' => 'NIP/NIK dan password wajib diisi.'
            ];
        }

        $user = $this->pegawai->findByNipOrNik($identifier);

        if (!$user || !password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'NIP/NIK atau password salah.'
            ];
        }

        unset($user['password']);

        return [
            'success' => true,
            'user' => $user
        ];
    }

    public function processLogin(): void
    {
        $result = $this->authenticate(
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

        $user = $result['user'];

        // SESSION MINIMAL
        Session::set('user', [
            'id'   => $user['id'],
            'role' => $user['role']
        ]);

        header(
            $user['role'] === 'admin'
                ? 'Location: /admin/dashboard'
                : 'Location: /pegawai/dashboard'
        );
        exit;
    }

    public function logout(): void
    {
        Session::destroy();
        header('Location: /login');
        exit;
    }
}
