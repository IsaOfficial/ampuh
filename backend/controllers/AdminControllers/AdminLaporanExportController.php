<?php

class AdminLaporanExportController
{
    private PegawaiModel $pegawaiModel;
    private AuthService $authService;
    private LaporanExportService $laporanExportService;

    public function __construct()
    {
        $db = Database::getConnection();

        $this->pegawaiModel = new PegawaiModel();

        $this->authService = new AuthService(
            $this->pegawaiModel,
            new AdminModel()
        );

        $this->laporanExportService = new LaporanExportService(
            new LaporanQueryModel($db),
            $this->pegawaiModel
        );
    }

    private function buildFilter(): array
    {
        return [
            'pegawai_id' => isset($_GET['pegawai_id']) && $_GET['pegawai_id'] !== ''
                ? (int) $_GET['pegawai_id']
                : null,
            'start' => $_GET['start'] ?? null,
            'end'   => $_GET['end'] ?? null,
        ];
    }

    private function resolvePegawaiLabel(?int $pegawaiId): array
    {
        if ($pegawaiId) {
            $pegawai = $this->pegawaiModel->findPegawaiById($pegawaiId);

            return $pegawai ?: ['nama' => 'Pegawai Tidak Ditemukan'];
        }

        return ['nama' => 'Semua Pegawai'];
    }

    public function exportPdf(): void
    {
        $admin  = $this->authService->Admin();
        $filter = $this->buildFilter();

        $result = $this->laporanExportService->exportLaporanByAdmin(
            $filter['pegawai_id'],
            $filter['start'],
            $filter['end'],
            $admin['nama'] ?? 'Administrator'
        );

        view('admin/export/laporan/pdf', [
            'title'   => $result['title'],
            'laporan' => $result['data'],
            'pegawai' => $this->resolvePegawaiLabel($filter['pegawai_id']),
            'start'   => $filter['start'],
            'end'     => $filter['end'],
            'admin'   => $admin,
        ]);
    }

    public function exportExcel(): void
    {
        $admin  = $this->authService->Admin();
        $filter = $this->buildFilter();

        $result = $this->laporanExportService->exportLaporanByAdmin(
            $filter['pegawai_id'],
            $filter['start'],
            $filter['end'],
            $admin['nama'] ?? 'Administrator'
        );

        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename=\"{$result['filename']}\"");
        header('Pragma: no-cache');
        header('Expires: 0');

        view('admin/export/laporan/excel', [
            'laporan' => $result['data'],
            'pegawai' => $this->resolvePegawaiLabel($filter['pegawai_id']),
            'start'   => $filter['start'],
            'end'     => $filter['end'],
            'admin'   => $admin,
        ]);

        exit;
    }
}
