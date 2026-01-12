<?php

class LaporanService
{
    public function __construct(
        private LaporanHarianModel $laporanHarian,
        private LaporanKegiatanModel $laporanKegiatan,
        private DocumentUploadService $documentUploadService,
        private string $uploadDir = PUBLIC_PATH . '/uploads/bukti',
    ) {}

    private function validateCreate(
        string $tanggal,
        array $kegiatan,
        array $output,
        array $files
    ): void {
        // 1. Tanggal wajib & valid
        if (empty($tanggal)) {
            throw new Exception("Tanggal laporan wajib diisi.");
        }

        $date = DateTime::createFromFormat('Y-m-d', $tanggal);
        if (!$date || $date->format('Y-m-d') !== $tanggal) {
            throw new Exception("Format tanggal tidak valid.");
        }

        // (Opsional) tidak boleh di masa depan
        if ($date > new DateTime()) {
            throw new Exception("Tanggal laporan tidak boleh di masa depan.");
        }

        // 2. Kegiatan wajib array & minimal 1
        if (empty($kegiatan) || !is_array($kegiatan)) {
            throw new Exception("Minimal satu kegiatan wajib diisi.");
        }

        // 3. Validasi per kegiatan
        $hasValidKegiatan = false;

        foreach ($kegiatan as $i => $text) {
            $text = trim($text);

            if ($text !== '') {
                $hasValidKegiatan = true;
            }

            // Output harus string
            if (isset($output[$i]) && !is_string($output[$i])) {
                throw new Exception("Output kegiatan tidak valid.");
            }

            // Validasi file (jika ada)
            if (!empty($files['name'][$i])) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    throw new Exception("Gagal upload file pada kegiatan ke-" . ($i + 1));
                }

                if ($files['size'][$i] > 5 * 1024 * 1024) {
                    throw new Exception("Ukuran file maksimal 5MB.");
                }

                $allowedTypes = [
                    'image/jpeg',
                    'image/png',
                    'application/pdf'
                ];

                if (!in_array($files['type'][$i], $allowedTypes, true)) {
                    throw new Exception("Tipe file tidak diizinkan pada kegiatan ke-" . ($i + 1));
                }
            }
        }

        if (!$hasValidKegiatan) {
            throw new Exception("Minimal satu kegiatan harus diisi.");
        }
    }

    private function validateUpdateKegiatan(
        string $kegiatan,
        string $output,
        array $file
    ): void {
        // Kegiatan wajib diisi
        if (trim($kegiatan) === '') {
            throw new Exception("Kegiatan wajib diisi.");
        }

        // Output harus string
        if (!is_string($output)) {
            throw new Exception("Output kegiatan tidak valid.");
        }

        // Validasi file (jika ada)
        if (!empty($file['name'])) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Gagal upload file.");
            }

            if ($file['size'] > 5 * 1024 * 1024) {
                throw new Exception("Ukuran file maksimal 5MB.");
            }

            $allowedTypes = [
                'image/jpeg',
                'image/png',
                'application/pdf'
            ];

            if (!in_array($file['type'], $allowedTypes, true)) {
                throw new Exception("Tipe file tidak diizinkan.");
            }
        }
    }

    private function refreshKegiatanCount(int $laporanId): void
    {
        $count = $this->laporanKegiatan->countByLaporanId($laporanId);

        // Jika kosong, hapus laporan harian
        if ($count === 0) {
            $this->laporanHarian->delete($laporanId);
        }

        $this->laporanHarian->recalculateKegiatanCount($laporanId, $count);
    }

    public function createKegiatan(
        int $pegawaiId,
        string $tanggal,
        array $kegiatan,
        array $output,
        array $files = []
    ): void {

        $this->validateCreate($tanggal, $kegiatan, $output, $files);

        $laporan = $this->laporanHarian->findByUserAndDate($pegawaiId, $tanggal);

        $laporanId = $laporan
            ? (int)$laporan['id']
            : $this->laporanHarian->create($pegawaiId, $tanggal);

        foreach ($kegiatan as $i => $text) {

            $text = trim($text);
            if ($text === '') {
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

        $this->refreshKegiatanCount($laporanId);
    }

    public function updateKegiatanByPegawai(
        int $pegawaiId,
        int $kegiatanId,
        string $kegiatan,
        string $output,
        array $file = []
    ): void {

        $this->validateUpdateKegiatan($kegiatan, $output, $file);

        $data = $this->laporanKegiatan->findById($kegiatanId);

        if (!$data) {
            throw new Exception("Data kegiatan tidak ditemukan.");
        }

        // Ambil laporan berdasarkan kegiatan
        $laporan = $this->laporanHarian->findById((int) $data['laporan_id']);

        if (!$laporan) {
            throw new Exception("Laporan tidak ditemukan.");
        }

        // VALIDASI AKSES YANG BENAR
        if ((int) $laporan['user_id'] !== $pegawaiId) {
            throw new Exception("Akses tidak diizinkan.");
        }

        $bukti = $data['bukti'];

        if (!empty($file['name'])) {

            $buktiBaru = $this->documentUploadService->upload(
                $file,
                $this->uploadDir
            );

            if (!empty($bukti)) {
                $this->documentUploadService->delete(
                    $this->uploadDir,
                    $bukti
                );
            }

            $bukti = $buktiBaru;
        }

        $this->laporanKegiatan->update(
            $kegiatanId,
            $kegiatan,
            $output,
            $bukti
        );

        $laporanId = (int)$data['laporan_id'];

        $this->refreshKegiatanCount($laporanId);
    }

    public function deleteKegiatanByPegawai(
        int $pegawaiId,
        int $kegiatanId
    ): void {

        $data = $this->laporanKegiatan->findById($kegiatanId);

        if (!$data) {
            throw new Exception("Data kegiatan tidak ditemukan.");
        }

        // Ambil laporan berdasarkan kegiatan
        $laporan = $this->laporanHarian->findById((int) $data['laporan_id']);

        if (!$laporan) {
            throw new Exception("Laporan tidak ditemukan.");
        }

        // VALIDASI AKSES YANG BENAR
        if ((int) $laporan['user_id'] !== $pegawaiId) {
            throw new Exception("Akses tidak diizinkan.");
        }

        if (!empty($data['bukti'])) {
            $this->documentUploadService->delete(
                $this->uploadDir,
                $data['bukti']
            );
        }

        $this->laporanKegiatan->delete($kegiatanId);

        $this->refreshKegiatanCount((int) $data['laporan_id']);
    }

    public function updateKegiatanByAdmin(
        int $kegiatanId,
        string $kegiatan,
        string $output,
        array $file = []
    ): void {

        $this->validateUpdateKegiatan($kegiatan, $output, $file);

        $data = $this->laporanKegiatan->findById($kegiatanId);

        if (!$data) {
            throw new Exception("Data laporan tidak ditemukan.");
        }

        $bukti = $data['bukti'];

        if (!empty($file['name'])) {

            $buktiBaru = $this->documentUploadService->upload(
                $file,
                $this->uploadDir
            );

            if (!empty($bukti)) {
                $this->documentUploadService->delete(
                    $this->uploadDir,
                    $bukti
                );
            }

            $bukti = $buktiBaru;
        }

        $this->laporanKegiatan->update(
            $kegiatanId,
            $kegiatan,
            $output,
            $bukti
        );

        $laporanId = (int)$data['laporan_id'];

        $this->refreshKegiatanCount($laporanId);
    }

    public function deleteKegiatanByAdmin(int $kegiatanId): void
    {
        $data = $this->laporanKegiatan->findById($kegiatanId);

        if (!$data) {
            throw new Exception("Data laporan tidak ditemukan.");
        }

        if (!empty($data['bukti'])) {
            $this->documentUploadService->delete(
                $this->uploadDir,
                $data['bukti']
            );
        }

        $this->laporanKegiatan->delete($kegiatanId);

        $laporanId = (int)$data['laporan_id'];

        $this->refreshKegiatanCount($laporanId);
    }
}
