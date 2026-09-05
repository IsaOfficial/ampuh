<?php

class AppLoginController
{
    private AuthService $authService;
    private AppLoginTokenModel $appLoginToken;

    public function __construct()
    {
        $this->authService = new AuthService(
            new PegawaiModel(),
            new AdminModel()
        );
        $this->appLoginToken = new AppLoginTokenModel();
    }

    public function login(array $request = []): void
    {
        $token = trim((string) ($request['token'] ?? ''));
        if ($token === '') {
            header('Location: /login');
            exit;
        }

        $pegawai = $this->appLoginToken->findPegawaiByToken($token);
        if (!$pegawai) {
            Session::flash('flash', [
                'type' => 'danger',
                'message' => 'Sesi aplikasi berakhir. Silakan login ulang.'
            ]);
            header('Location: /login');
            exit;
        }

        $this->appLoginToken->touch($token);
        Session::regenerate();
        Session::set('user', [
            'id' => (int) $pegawai['id'],
            'role' => 'pegawai',
        ]);

        header('Location: /pegawai/dashboard');
        exit;
    }

    public function issueToken(): void
    {
        $pegawai = $this->authService->pegawai();
        $token = $this->appLoginToken->issueForPegawai(
            (int) $pegawai['id'],
            $_GET['current_token'] ?? null
        );

        $this->json([
            'authenticated' => true,
            'token' => $token['token'],
            'expires_at' => $token['expires_at'],
            'nama' => $pegawai['nama'] ?? '',
        ]);
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
