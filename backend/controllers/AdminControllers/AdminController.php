<?php

class AdminController
{
    private AdminModel $admin;

    public function __construct()
    {
        $this->admin = new AdminModel();
    }

    public function index(): void
    {
        $userSession = Session::get('user');

        // Ambil data admin dari database (via model)
        $admin = $this->admin->findById($userSession['id']);

        // Data dashboard
        $stats = [
            'total_pegawai' => $this->admin->countPegawai(),
            'total_laporan' => $this->admin->countLaporan()
        ];

        view('admin/dashboard', [
            'title' => 'Dashboard Admin',
            'admin' => $admin,
            'stats' => $stats
        ]);
    }
}
