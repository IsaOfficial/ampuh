<?php

class AdminJabatanController
{
    private AuthService $authService;
    private AdminJabatanService $jabatanService;

    public function __construct()
    {
        $this->authService = new AuthService(
            new PegawaiModel(),
            new AdminModel()
        );

        $this->jabatanService = new AdminJabatanService(
            new JabatanModel()
        );
    }

    private function authorize(): void
    {
        $this->authService->requireAdmin();
    }

    public function index(): void
    {
        $this->authorize();

        view('admin/kelola_jabatan', [
            'title' => 'Kelola Jabatan',
            'jabatanList' => $this->jabatanService->getAll(),
        ]);
    }

    public function store(): void
    {
        $this->authorize();

        try {
            $this->jabatanService->create($_POST);

            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Jabatan berhasil ditambahkan.',
            ]);
        } catch (Throwable $e) {
            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage(),
            ]);
        }

        header('Location: /admin/kelola/jabatan');
        exit;
    }

    public function update(): void
    {
        $this->authorize();

        try {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID jabatan tidak valid.');
            }

            $this->jabatanService->update($id, $_POST);

            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Jabatan berhasil diperbarui.',
            ]);
        } catch (Throwable $e) {
            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage(),
            ]);
        }

        header('Location: /admin/kelola/jabatan');
        exit;
    }

    public function toggle(): void
    {
        $this->authorize();

        try {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID jabatan tidak valid.');
            }

            $this->jabatanService->toggle($id);

            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Status jabatan berhasil diubah.',
            ]);
        } catch (Throwable $e) {
            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage(),
            ]);
        }

        header('Location: /admin/kelola/jabatan');
        exit;
    }

    public function delete(): void
    {
        $this->authorize();

        try {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID jabatan tidak valid.');
            }

            $this->jabatanService->delete($id);

            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Jabatan berhasil dihapus.',
            ]);
        } catch (Throwable $e) {
            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage(),
            ]);
        }

        header('Location: /admin/kelola/jabatan');
        exit;
    }
}
