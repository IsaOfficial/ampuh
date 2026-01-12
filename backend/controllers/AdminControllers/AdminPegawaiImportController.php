<?php

class AdminPegawaiImportController
{
    private AuthService $authService;
    private AdminPegawaiImportService $pegawaiImportService;

    public function __construct()
    {
        $pegawaiModel = new PegawaiModel();

        $this->authService = new AuthService(
            $pegawaiModel,
            new AdminModel()
        );

        $this->pegawaiImportService = new AdminPegawaiImportService(
            new AdminPegawaiService(
                $pegawaiModel,
                new ImageUploadService()
            )
        );
    }

    private function authorize(): void
    {
        $this->authService->requireAdmin();
    }

    public function importPegawai(): void
    {
        // 1. Authorization guard
        $this->authorize();

        // 2. HTTP method guard
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method Not Allowed');
        }

        try {
            // 3. Basic request validation
            if (!isset($_FILES['file_csv'])) {
                throw new Exception('File CSV wajib diunggah.');
            }

            // 4. Optional: size limit (misal 2MB)
            if ($_FILES['file_csv']['size'] > 2 * 1024 * 1024) {
                throw new Exception('Ukuran file terlalu besar (maksimal 2MB).');
            }

            $this->pegawaiImportService->importFromCsv($_FILES['file_csv']);

            Session::flash('flash', [
                'type'    => 'success',
                'message' => 'Berhasil import data pegawai.'
            ]);
        } catch (Exception $e) {
            Session::flash('flash', [
                'type'    => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header("Location: /admin/kelola/pegawai");
        exit;
    }
}
