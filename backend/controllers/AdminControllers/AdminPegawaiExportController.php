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

    private function buildFilter(): array
    {
        return [
            'keyword' => trim($_GET['keyword'] ?? '') ?: null,
            'jabatan' => trim($_GET['jabatan'] ?? '') ?: null,
            'jenis_kelamin' => trim($_GET['jenis_kelamin'] ?? '') ?: null,
        ];
    }

    public function exportPdf(): void
    {
        $this->authorize();

        $filter = $this->buildFilter();
        $pegawai = $this->adminPegawaiService->getAll(
            $filter['keyword'],
            $filter['jabatan'],
            $filter['jenis_kelamin']
        );

        view('admin/export/pegawai/pdf', [
            'pegawai' => $pegawai,
            'title'   => 'Data Pegawai',
            'filter'  => $filter
        ]);
        exit;
    }

    public function exportExcel(): void
    {
        $this->authorize();

        $filter = $this->buildFilter();
        $pegawai = $this->adminPegawaiService->getAll(
            $filter['keyword'],
            $filter['jabatan'],
            $filter['jenis_kelamin']
        );

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="data_pegawai.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');

        view('admin/export/pegawai/excel', [
            'pegawai' => $pegawai,
            'title'   => 'Data Pegawai',
            'filter'  => $filter
        ]);
        exit;
    }
}