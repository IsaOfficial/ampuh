<?php

class PegawaiLaporanExportController
{
    private AuthService $authService;
    private LaporanExportService $laporanExportService;

    public function __construct()
    {
        $this->authService = new AuthService(
            new PegawaiModel(),
            new AdminModel()
        );

        $this->laporanExportService = new LaporanExportService(
            new LaporanKegiatanModel()
        );
    }

    // =====================
    // EXPORT LAPORAN PDF
    // =====================
    public function exportPdf(): void
    {
        $pegawai = $this->authService->pegawai();

        $start = $_GET['start_date'] ?? null;
        $end   = $_GET['end_date'] ?? null;

        // Ambil data laporan pegawai
        $laporan = $this->laporanExportService->getData($pegawai['id'], $start, $end);

        // Render view PDF
        view('pegawai/export/pdf', [
            'title'   => 'Ekspor Laporan Pegawai',
            'laporan' => $laporan,
            'start'   => $start,
            'end'     => $end,
            'user'    => $pegawai
        ]);
    }

    // =====================
    // EXPORT LAPORAN EXCEL
    // =====================
    public function exportExcel(): void
    {
        $pegawai = $this->authService->pegawai();

        $start = $_GET['start_date'] ?? null;
        $end   = $_GET['end_date'] ?? null;

        // Ambil data laporan pegawai
        $laporan = $this->laporanExportService->getData($pegawai['id'], $start, $end);

        // Normalisasi tanggal untuk nama file
        if (!$start && !$end) {
            $fileName = sprintf(
                'Laporan_%s_SemuaData.xls',
                preg_replace('/\s+/', '_', $pegawai['nama'])
            );
        } else {
            $fileStart = $start ?: 'awal';
            $fileEnd   = $end   ?: 'akhir';

            $fileName = sprintf(
                'Laporan_%s_%s_sampai_%s.xls',
                preg_replace('/\s+/', '_', $pegawai['nama']),
                $fileStart,
                $fileEnd
            );
        }

        // Set header Excel
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        header('Pragma: no-cache');
        header('Expires: 0');

        // Render view Excel
        view('pegawai/export/excel', [
            'title'   => 'Ekspor Laporan Pegawai',
            'laporan' => $laporan,
            'start'   => $start,
            'end'     => $end,
            'user'    => $pegawai
        ]);
    }
}
