<?php

class AdminPegawaiExportController
{
    private AuthService $authService;
    private AdminPegawaiService $adminPegawaiService;

    public function __construct()
    {
        $this->authService = new AuthService(
            new PegawaiModel(),
            new AdminModel()
        );

        $this->adminPegawaiService = new AdminPegawaiService(
            new PegawaiModel(),
            new ImageUploadService()
        );
    }

    private function authorize(): void
    {
        $this->authService->requireAdmin();
    }

    public function exportPdf(): void
    {
        $this->authorize();

        $pegawai = $this->adminPegawaiService->getAll();

        view('admin/export/pegawai/pdf', [
            'pegawai' => $pegawai,
            'title'   => 'Data Pegawai'
        ]);
        exit;
    }

    public function exportExcel(): void
    {
        $this->authorize();

        $pegawai = $this->adminPegawaiService->getAll();

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="data_pegawai.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');

        view('admin/export/pegawai/excel', [
            'pegawai' => $pegawai,
            'title'   => 'Data Pegawai'
        ]);
        exit;
    }
}
