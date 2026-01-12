<?php

class AdminPegawaiController
{
    private PegawaiModel $pegawaiModel;
    private AuthService $authService;
    private AdminPegawaiService $adminPegawaiService;

    public function __construct()
    {
        $this->pegawaiModel = new PegawaiModel();

        $this->authService = new AuthService(
            $this->pegawaiModel,
            new AdminModel()
        );

        $this->adminPegawaiService = new AdminPegawaiService(
            $this->pegawaiModel,
            new ImageUploadService()
        );
    }

    private function authorize(): void
    {
        $this->authService->requireAdmin();
    }

    public function kelolaPegawai(): void
    {
        $this->authorize();

        $pegawaiList = $this->pegawaiModel->getAllPegawai();

        view('admin/kelola_pegawai', [
            'title'   => 'Kelola Pegawai',
            'pegawai' => $pegawaiList
        ]);
    }

    public function create(): void
    {
        $this->authorize();

        try {
            $this->adminPegawaiService->create($_POST);

            Session::flash('flash', [
                'type'    => 'success',
                'message' => 'Pegawai berhasil ditambahkan.'
            ]);
        } catch (Throwable $e) {
            Session::flash('flash', [
                'type'    => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header('Location: /admin/kelola/pegawai');
        exit;
    }

    public function update(): void
    {
        $this->authorize();

        try {
            if (empty($_POST['id'])) {
                throw new Exception('ID pegawai tidak valid.');
            }

            $this->adminPegawaiService->update(
                (int) $_POST['id'],
                $_POST,
                $_FILES['foto'] ?? null
            );

            Session::flash('flash', [
                'type'    => 'success',
                'message' => 'Data pegawai berhasil diperbarui.'
            ]);
        } catch (Throwable $e) {
            Session::flash('flash', [
                'type'    => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header('Location: /admin/kelola/pegawai');
        exit;
    }

    public function delete(): void
    {
        $this->authorize();

        try {
            if (empty($_POST['id'])) {
                throw new Exception('ID pegawai tidak valid.');
            }

            $this->adminPegawaiService->delete((int) $_POST['id']);

            Session::flash('flash', [
                'type'    => 'success',
                'message' => 'Pegawai berhasil dihapus.'
            ]);
        } catch (Throwable $e) {
            Session::flash('flash', [
                'type'    => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header('Location: /admin/kelola/pegawai');
        exit;
    }
}
