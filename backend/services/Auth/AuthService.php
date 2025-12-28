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

        $pegawai = $this->pegawai->findById($this->id());

        if (!$pegawai) {
            throw new Exception('Data pegawai tidak ditemukan.');
        }

        return $pegawai;
    }

    public function admin(): array
    {
        $this->requireAdmin();

        $admin = $this->admin->findById($this->id());

        if (!$admin) {
            throw new Exception('Data admin tidak ditemukan.');
        }

        return $admin;
    }
}
