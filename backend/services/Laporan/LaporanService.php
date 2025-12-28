<?php

class LaporanService
{
    public function __construct(
        private LaporanHarianModel $laporanHarian,
        private LaporanKegiatanModel $laporanKegiatan,
        private DocumentUploadService $documentUploadService,
        private string $uploadDir = __DIR__ . '/../../../public/uploads/bukti/',
    ) {}

    public function create(
        int $pegawaiId,
        string $tanggal,
        array $kegiatan,
        array $output,
        array $files = []
    ): void {
        // 1. Pastikan laporan harian ada
        $laporan = $this->laporanHarian->findByUserAndDate($pegawaiId, $tanggal);

        $laporanId = $laporan
            ? $laporan['id']
            : $this->laporanHarian->create($pegawaiId, $tanggal);
        // 2. Simpan setiap kegiatan
        foreach ($kegiatan as $i => $text) {

            if (trim($text) === '') {
                continue;
            }

            $bukti = null;

            if (!empty($files['name'][$i])) {
                $file = [
                    'name'     => $files['name'][$i],
                    'type'     => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error'    => $files['error'][$i],
                    'size'     => $files['size'][$i],
                ];

                $bukti = $this->documentUploadService->upload(
                    $file,
                    $this->uploadDir
                );
            }

            $this->laporanKegiatan->create(
                $laporanId,
                $text,
                $output[$i] ?? '',
                $bukti
            );
        }

        // 3. Update kegiatan_count
        $count = $this->laporanKegiatan->countByLaporanId($laporanId);
        $this->laporanHarian->updateKegiatanCount($laporanId, $count);
    }

    public function update(
        int $pegawaiId,
        int $kegiatanId,
        string $kegiatan,
        string $output,
        array $file = []
    ): void {
        // Ambil data lama
        $data = $this->laporanKegiatan->findById($kegiatanId);

        if (!$data) {
            throw new Exception("Data laporan tidak ditemukan.");
        }

        if ($pegawaiId !== null && (int)$data['user_id'] !== $pegawaiId) {
            throw new Exception("Anda tidak berhak mengubah laporan ini.");
        }

        $laporanId = $data['laporan_id'];
        $bukti     = $data['bukti'];

        // Jika ada file baru
        if (!empty($file['name'])) {

            // Upload file baru
            $buktiBaru = $this->documentUploadService->upload(
                $file,
                $this->uploadDir,
            );

            // Hapus file lama (jika ada)
            if (!empty($bukti)) {
                $this->documentUploadService->delete(
                    $this->uploadDir,
                    $bukti
                );
            }

            $bukti = $buktiBaru;
        }

        // Update kegiatan
        $this->laporanKegiatan->update(
            $kegiatanId,
            $kegiatan,
            $output,
            $bukti
        );

        // Update kegiatan_count
        $count = $this->laporanKegiatan->countByLaporanId($laporanId);

        $this->laporanHarian->updateKegiatanCount($laporanId, $count);
    }

    public function delete(int $kegiatanId, ?int $pegawaiId = null): void
    {
        $data = $this->laporanKegiatan->findById($kegiatanId);

        if (!$data) {
            throw new Exception("Laporan tidak ditemukan.");
        }

        if ($pegawaiId !== null && (int)$data['user_id'] !== $pegawaiId) {
            throw new Exception("Anda tidak berhak mengubah laporan ini.");
        }

        $laporanId = (int) $data['laporan_id'];

        // Hapus file bukti
        if (!empty($data['bukti'])) {
            $this->documentUploadService->delete(
                $this->uploadDir,
                $data['bukti']
            );
        }

        // Hapus kegiatan
        $this->laporanKegiatan->delete($kegiatanId);

        // Update kegiatan_count
        $count = $this->laporanKegiatan->countByLaporanId($laporanId);
        $this->laporanHarian->updateKegiatanCount($laporanId, $count);
    }
}
