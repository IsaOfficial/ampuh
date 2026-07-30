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

    private function buildFilter(): array
    {
        return [
            'keyword' => trim($_GET['keyword'] ?? '') ?: null,
            'jabatan' => trim($_GET['jabatan'] ?? '') ?: null,
            'jenis_kelamin' => trim($_GET['jenis_kelamin'] ?? '') ?: null,
        ];
    }

    public function kelolaPegawai(): void
    {
        $this->authorize();

        $filter = $this->buildFilter();
        $pegawaiList = $this->pegawaiModel->getAllPegawai(
            $filter['keyword'],
            $filter['jabatan'],
            $filter['jenis_kelamin']
        );

        view('admin/kelola_pegawai', [
            'title'   => 'Kelola Pegawai',
            'pegawai' => $pegawaiList,
            'filter'  => $filter
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

            $admin = $this->authService->admin();
            $this->adminPegawaiService->deleteByAdmin((int) $_POST['id'], (int) $admin['id']);

            Session::flash('flash', [
                'type'    => 'success',
                'message' => 'Pegawai dipindahkan ke Sampah.'
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

    public function restore(): void
    {
        $this->authorize();

        try {
            if (empty($_POST['id'])) {
                throw new Exception('ID pegawai tidak valid.');
            }

            $this->adminPegawaiService->restore((int) $_POST['id']);

            Session::flash('flash', [
                'type'    => 'success',
                'message' => 'Pegawai berhasil dipulihkan.'
            ]);
        } catch (Throwable $e) {
            Session::flash('flash', [
                'type'    => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header('Location: /admin/kelola/laporan/sampah');
        exit;
    }

    public function forceDelete(): void
    {
        $this->authorize();

        try {
            if (empty($_POST['id'])) {
                throw new Exception('ID pegawai tidak valid.');
            }

            $this->adminPegawaiService->forceDelete((int) $_POST['id']);

            Session::flash('flash', [
                'type'    => 'success',
                'message' => 'Pegawai berhasil dihapus permanen.'
            ]);
        } catch (Throwable $e) {
            Session::flash('flash', [
                'type'    => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header('Location: /admin/kelola/laporan/sampah');
        exit;
    }

    public function bulkProcess(): void
    {
        $this->authorize();

        $action = trim($_POST['action'] ?? '');
        $pegawaiIds = $_POST['pegawai_ids'] ?? [];

        try {
            $admin = $this->authService->admin();
            $processed = $this->adminPegawaiService->bulkProcess(
                (array) $pegawaiIds,
                $action,
                (int) $admin['id']
            );

            Session::flash('flash', [
                'type' => 'success',
                'message' => "{$processed} pegawai berhasil diproses."
            ]);
        } catch (Throwable $e) {
            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        $redirect = in_array($action, ['restore', 'force_delete'], true)
            ? '/admin/kelola/laporan/sampah'
            : '/admin/kelola/pegawai';

        header("Location: {$redirect}");
        exit;
    }
}
