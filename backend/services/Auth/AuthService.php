<?php

class AuthService
{
    public function __construct(
        private PegawaiModel $pegawai,
        private AdminModel $admin
    ) {}

    /* =========================
     * USER SESSION
     * ========================= */

    public function user(): array
    {
        $user = Session::get('user');

        if (!$user || empty($user['id']) || empty($user['role'])) {
            throw new Exception('Sesi pengguna tidak valid.');
        }

        return $user;
    }

    public function id(): int
    {
        return (int) $this->user()['id'];
    }

    public function role(): string
    {
        return $this->user()['role'];
    }

    /* =========================
     * ROLE CHECK
     * ========================= */

    public function isAdmin(): bool
    {
        return $this->role() === 'admin';
    }

    public function isPegawai(): bool
    {
        return $this->role() === 'pegawai';
    }

    public function requireAdmin(): void
    {
        if (!$this->isAdmin()) {
            throw new Exception('Akses hanya untuk admin.');
        }
    }

    public function requirePegawai(): void
    {
        if (!$this->isPegawai()) {
            throw new Exception('Akses hanya untuk pegawai.');
        }
    }

    /* =========================
     * DOMAIN OBJECT
     * ========================= */

    public function pegawai(): array
    {
        $this->requirePegawai();

        $pegawai = $this->pegawai->findPegawaiById($this->id());

        if (!$pegawai) {
            throw new Exception('Data pegawai tidak ditemukan.');
        }

        return $pegawai;
    }

    public function admin(): array
    {
        $this->requireAdmin();

        $admin = $this->admin->findAdminById($this->id());

        if (!$admin) {
            throw new Exception('Data admin tidak ditemukan.');
        }

        return $admin;
    }

    public function authenticate(string $identifier, string $password): array
    {
        $identifier = trim($identifier);

        if ($identifier === '' || $password === '') {
            return [
                'success' => false,
                'message' => 'NIP/NIK dan password wajib diisi.'
            ];
        }

        // 1️⃣ Coba login sebagai admin
        $admin = $this->admin->findAdminByIdentifier($identifier);
        if ($admin && password_verify($password, $admin['password'])) {
            unset($admin['password']);
            return [
                'success' => true,
                'user'    => $admin
            ];
        }

        // 2️⃣ Coba login sebagai pegawai
        $pegawai = $this->pegawai->findPegawaiByIdentifier($identifier);
        if ($pegawai && password_verify($password, $pegawai['password'])) {
            unset($pegawai['password']);
            return [
                'success' => true,
                'user'    => $pegawai
            ];
        }

        return [
            'success' => false,
            'message' => 'NIP/NIK atau password salah.'
        ];
    }
}
