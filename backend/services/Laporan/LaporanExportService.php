<?php

class LaporanExportService
{
    public function __construct(
        private LaporanQueryModel $laporanQuery,
        private PegawaiModel $pegawaiModel,
    ) {}

    public function exportLaporanByAdmin(
        ?int $pegawaiId,
        ?string $start,
        ?string $end,
        string $adminName
    ): array {
        // Simpan filter asli (untuk nama file)
        $rawStart = $start;
        $rawEnd   = $end;

        // Normalisasi tanggal (untuk query)
        [$start, $end] = $this->normalizeDateRange($start, $end);

        // Ambil nama pegawai jika difilter
        $pegawaiName = null;

        if ($pegawaiId !== null) {
            $pegawai = $this->pegawaiModel->findPegawaiById($pegawaiId);

            if (!$pegawai) {
                throw new Exception("Pegawai tidak ditemukan.");
            }

            $pegawaiName = $pegawai['nama'];
        }

        // Ambil data
        $data = $this->laporanQuery->getLaporanByAdmin(
            $pegawaiId,
            $start,
            $end
        );

        // Nama file excel
        $filename = $this->buildExcelFileNameAdmin(
            $adminName,
            $pegawaiName,
            $rawStart,
            $rawEnd
        );

        $title = $this->buildPdfFileNameAdmin(
            $adminName,
            $pegawaiName,
            $rawStart,
            $rawEnd
        );

        return [
            'data'     => $data,
            'filename' => $filename,
            'title'    => $title,
        ];
    }

    private function buildPdfFileNameAdmin(
        string $adminName,
        ?string $pegawaiName,
        ?string $start,
        ?string $end
    ): string {
        $parts = ['Rekapitulasi_Laporan_Kegiatan'];

        // 1. Tidak ada filter sama sekali
        if (!$pegawaiName && !$start && !$end) {
            $parts[] = 'Semua_Data';
            $parts[] = 'By_' . preg_replace('/\s+/', '_', trim($adminName));

            return implode('_', $parts) . '.pdf';
        }

        // 2. Hanya pegawai
        if ($pegawaiName && !$start && !$end) {
            $parts[] = preg_replace('/\s+/', '_', trim($pegawaiName));
            $parts[] = 'Semua_Periode';
            $parts[] = 'By_' . preg_replace('/\s+/', '_', trim($adminName));

            return implode('_', $parts) . '.pdf';
        }

        // 3. Hanya start
        if (!$pegawaiName && $start && !$end) {
            $parts[] = 'Semua_Pegawai';
            $parts[] = "dari_{$start}_sampai_saat_ini";
            $parts[] = 'By_' . preg_replace('/\s+/', '_', trim($adminName));

            return implode('_', $parts) . '.pdf';
        }

        // 4. Hanya end
        if (!$pegawaiName && !$start && $end) {
            $parts[] = 'Semua_Pegawai';
            $parts[] = "sampai_{$end}";
            $parts[] = 'By_' . preg_replace('/\s+/', '_', trim($adminName));

            return implode('_', $parts) . '.pdf';
        }

        // Kombinasi lainnya (pegawai + tanggal)
        if ($pegawaiName) {
            $parts[] = preg_replace('/\s+/', '_', trim($pegawaiName));
        } else {
            $parts[] = 'Semua_Pegawai';
        }

        if ($start && $end) {
            $parts[] = "dari_{$start}_sampai_{$end}";
        } elseif ($start) {
            $parts[] = "dari_{$start}_sampai_saat_ini";
        } elseif ($end) {
            $parts[] = "sampai_{$end}";
        }

        $parts[] = 'By_' . preg_replace('/\s+/', '_', trim($adminName));

        return implode('_', $parts) . '.pdf';
    }

    private function buildExcelFileNameAdmin(
        string $adminName,
        ?string $pegawaiName,
        ?string $start,
        ?string $end
    ): string {
        $parts = ['Rekapitulasi_Laporan_Kegiatan'];

        $parts[] = $pegawaiName
            ? preg_replace('/\s+/', '_', trim($pegawaiName))
            : 'Semua_Pegawai';

        if ($start && $end) {
            $parts[] = "dari_{$start}_sampai_{$end}";
        } elseif ($start) {
            $parts[] = "dari_{$start}_sampai_saat_ini";
        } elseif ($end) {
            $parts[] = "sampai_{$end}";
        } else {
            $parts[] = 'Semua_Data';
        }

        $parts[] = 'By_' . preg_replace('/\s+/', '_', trim($adminName));

        return implode('_', $parts) . '.xls';
    }

    public function exportLaporanByPegawai(
        int $pegawaiId,
        ?string $start,
        ?string $end
    ): array {
        // Simpan nilai asli untuk nama file
        $rawStart = $start;
        $rawEnd   = $end;

        // Normalisasi tanggal untuk query
        [$start, $end] = $this->normalizeDateRange($start, $end);

        // Ambil data pegawai (source of truth)
        $pegawai = $this->pegawaiModel->findPegawaiById($pegawaiId);

        if (!$pegawai) {
            throw new Exception('Pegawai tidak ditemukan.');
        }

        $pegawaiName = $pegawai['nama'];

        // Ambil data laporan
        $data = $this->laporanQuery->getLaporanByPegawai(
            $pegawaiId,
            $start,
            $end
        );

        // Bangun nama file
        $filename = $this->buildExcelFileNamePegawai(
            $pegawaiName,
            $rawStart,
            $rawEnd
        );

        $title = $this->buildPdfFileNamePegawai(
            $pegawaiName,
            $rawStart,
            $rawEnd
        );

        return [
            'data'     => $data,
            'filename' => $filename,
            'title'    => $title
        ];
    }

    private function buildPdfFileNamePegawai(
        ?string $pegawaiName,
        ?string $start,
        ?string $end
    ): string {
        $parts = ['Rekapitulasi_Laporan_Kegiatan'];

        // Kombinasi lainnya (pegawai + tanggal)
        if ($pegawaiName) {
            $parts[] = preg_replace('/\s+/', '_', trim($pegawaiName));
        }

        if ($start && $end) {
            $parts[] = "dari_{$start}_sampai_{$end}";
        } elseif ($start) {
            $parts[] = "dari_{$start}_sampai_saat_ini";
        } elseif ($end) {
            $parts[] = "sampai_{$end}";
        }

        return implode('_', $parts) . '.pdf';
    }

    private function buildExcelFileNamePegawai(
        string $pegawaiName,
        ?string $start,
        ?string $end
    ): string {
        $parts = ['Rekapitulasi_Laporan_Kegiatan'];

        $parts[] = preg_replace('/\s+/', '_', trim($pegawaiName));

        if ($start && $end) {
            $parts[] = "dari_{$start}_sampai_{$end}";
        } elseif ($start) {
            $parts[] = "dari_{$start}_sampai_saat_ini";
        } elseif ($end) {
            $parts[] = "sampai_{$end}";
        } else {
            $parts[] = 'Semua_Data';
        }

        return implode('_', $parts) . '.xls';
    }

    private function normalizeDateRange(
        ?string $start,
        ?string $end
    ): array {
        $start = $start ?: '1970-01-01';
        $end   = $end   ?: date('Y-m-d');

        $this->assertValidDate($start);
        $this->assertValidDate($end);

        if ($start > $end) {
            throw new Exception("Rentang tanggal tidak valid.");
        }

        return [$start, $end];
    }

    private function assertValidDate(string $date): void
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);

        if (!$d || $d->format('Y-m-d') !== $date) {
            throw new Exception("Format tanggal tidak valid.");
        }
    }
}
