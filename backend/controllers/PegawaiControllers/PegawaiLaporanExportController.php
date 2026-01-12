<?php

class PegawaiLaporanExportController
{
    private AuthService $authService;
    private LaporanExportService $laporanExportService;

    public function __construct()
    {
        $db = Database::getConnection();

        $pegawaiModel = new PegawaiModel();

        $this->authService = new AuthService(
            $pegawaiModel,
            new AdminModel()
        );

        $this->laporanExportService = new LaporanExportService(
            new LaporanQueryModel($db),
            $pegawaiModel
        );
    }

    private function buildFilter(array $pegawai): array
    {
        $start = trim($_GET['start_date'] ?? '');
        $end   = trim($_GET['end_date'] ?? '');

        return [
            'pegawai_id' => (int) $pegawai['id'],
            'start'      => $start !== '' ? $start : null,
            'end'        => $end   !== '' ? $end   : null,
        ];
    }

    public function exportPdf(): void
    {
        $pegawai = $this->authService->pegawai();
        $filter  = $this->buildFilter($pegawai);

        $result = $this->laporanExportService->exportLaporanByPegawai(
            $pegawaiId = $pegawai['id'],
            $filter['start'],
            $filter['end']
        );

        view('pegawai/export/pdf', [
            'title'   => $result['title'],
            'laporan' => $result['data'],
            'pegawai'    => $pegawai,
            'start'   => $filter['start'],
            'end'     => $filter['end'],
        ]);
    }

    public function exportExcel(): void
    {
        $pegawai = $this->authService->pegawai();
        $filter  = $this->buildFilter($pegawai);

        $result = $this->laporanExportService->exportLaporanByPegawai(
            $filter['pegawai_id'],
            $filter['start'],
            $filter['end']
        );

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        view('pegawai/export/excel', [
            'laporan' => $result['data'],
            'pegawai' => $pegawai,
            'start'   => $filter['start'],
            'end'     => $filter['end'],
        ]);
    }
}
