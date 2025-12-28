<?php

class LaporanExportService
{
    private LaporanKegiatanModel $laporan;

    public function __construct()
    {
        $this->laporan = new LaporanKegiatanModel();
    }

    public function getData(
        int $userId,
        ?string $start,
        ?string $end
    ): array {

        // Normalisasi tanggal
        if (empty($start) && empty($end)) {
            return $this->laporan->getByUserId($userId);
        }

        $start = $start ?: '0000-01-01';
        $end   = $end   ?: '9999-12-31';

        return $this->laporan
            ->filterTanggal($userId, $start, $end);
    }

    private function normalizeFilter(array $filter): array
    {
        return [
            'keyword' => trim($filter['keyword'] ?? ''),
            'start'   => $filter['start'] !== '' ? $filter['start'] : '0000-01-01',
            'end'     => $filter['end']   !== '' ? $filter['end']   : '9999-12-31',
        ];
    }

    public function exportExcelAdmin(array $filter): void
    {
        $filter = $this->normalizeFilter($filter);

        $data = $this->laporan->filterAdmin(
            $filter['keyword'],
            $filter['start'],
            $filter['end']
        );

        $fileName = "Laporan_Admin_{$filter['start']}_sampai_{$filter['end']}.xls";

        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        header('Pragma: no-cache');
        header('Expires: 0');

        $viewFile = __DIR__ . '/../../public/views/admin/export/laporan/excel.php';

        if (!file_exists($viewFile)) {
            throw new Exception('View export Excel admin tidak ditemukan.');
        }

        extract([
            'laporan' => $data,
            'filter'  => $filter
        ]);

        require $viewFile;
    }

    public function exportPdfAdmin(array $filter): void
    {
        $filter = $this->normalizeFilter($filter);

        $data = $this->laporan->filterAdmin(
            $filter['keyword'],
            $filter['start'],
            $filter['end']
        );

        $viewFile = __DIR__ . '/../../public/views/admin/export/laporan/pdf.php';

        if (!file_exists($viewFile)) {
            throw new Exception('View export PDF admin tidak ditemukan.');
        }

        extract([
            'laporan' => $data,
            'filter'  => $filter
        ]);

        require $viewFile;
    }
}
