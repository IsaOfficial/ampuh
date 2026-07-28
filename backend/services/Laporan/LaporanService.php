<?php

class LaporanService
{
    private const PEGAWAI_EDIT_WINDOW_DAYS = 3;
    private const APP_TIMEZONE = 'Asia/Jakarta';
    public function __construct(
        private LaporanHarianModel $laporanHarian,
        private LaporanKegiatanModel $laporanKegiatan,
        private DocumentUploadService $documentUploadService,
        private string $uploadDir = PUBLIC_PATH . '/uploads/bukti',
    ) {}

    private function appTimezone(): DateTimeZone
    {
        return new DateTimeZone(self::APP_TIMEZONE);
    }

    private function today(): DateTimeImmutable
    {
        return new DateTimeImmutable('today', $this->appTimezone());
    }

    private function parseTanggal(string $tanggal): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $tanggal, $this->appTimezone());

        return $date && $date->format('Y-m-d') === $tanggal ? $date : null;
    }

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

        $date = $this->parseTanggal($tanggal);
        if (!$date) {
            throw new Exception("Format tanggal tidak valid.");
        }

        if ($date > $this->today()) {
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

                try {
                    $this->documentUploadService->validate([
                    'name'     => $files['name'][$i],
                    'type'     => $files['type'][$i] ?? '',
                    'tmp_name' => $files['tmp_name'][$i],
                    'error'    => $files['error'][$i],
                    'size'     => $files['size'][$i],
                    ]);
                } catch (Exception $e) {
                    throw new Exception('Bukti kegiatan ke-' . ($i + 1) . ': ' . $e->getMessage());
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

            $this->documentUploadService->validate($file);
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

    private function assertTanggalEditableByPegawai(string $tanggal): void
    {
        $date = $this->parseTanggal($tanggal);
        $today = $this->today();
        $minimumDate = $today->modify('-' . self::PEGAWAI_EDIT_WINDOW_DAYS . ' days');

        if (!$date || $date < $minimumDate || $date > $today) {
            throw new Exception("Laporan hanya dapat ditambah, diubah, atau dihapus sampai 3 hari sebelumnya.");
        }
    }

    private function assertEditableByPegawai(int $laporanId): void
    {
        $laporan = $this->laporanHarian->findById($laporanId);

        if (!$laporan) {
            throw new Exception("Laporan tidak ditemukan.");
        }

        if (($laporan['approval_status'] ?? 'pending') === 'approved') {
            throw new Exception("Laporan sudah diproses dan ditandatangani admin sehingga tidak dapat diubah.");
        }

        $this->assertTanggalEditableByPegawai((string) $laporan['tanggal']);
    }

    public function bulkProcessByAdmin(
        array $laporanIds,
        string $action,
        int $adminId,
        string $adminName,
        ?string $signatureNote = null,
        ?string $rejectionNote = null
    ): int {
        $laporanIds = array_values(array_unique(array_filter(array_map('intval', $laporanIds))));

        if (!$laporanIds) {
            throw new Exception("Pilih minimal satu kegiatan.");
        }

        return match ($action) {
            'approve' => $this->laporanHarian->approveBulk($laporanIds, $adminId, $adminName, $signatureNote),
            'reject'  => $this->laporanHarian->rejectBulk($laporanIds, $rejectionNote),
            'revoke'  => $this->laporanHarian->revokeBulk($laporanIds),
            'delete'  => $this->deleteKegiatanBulkByAdmin($laporanIds),
            default   => throw new Exception("Aksi bulk tidak valid."),
        };
    }


    private function deleteKegiatanBulkByAdmin(array $kegiatanIds): int
    {
        $deleted = 0;

        foreach ($kegiatanIds as $kegiatanId) {
            $this->deleteKegiatanByAdmin((int) $kegiatanId);
            $deleted++;
        }

        return $deleted;
    }

    private function hasRevokedApproval(array $data): bool
    {
        return !empty($data['approval_revoked_at'])
            && (($data['approval_status'] ?? 'pending') !== 'approved');
    }

    public function createKegiatan(
        int $pegawaiId,
        string $tanggal,
        array $kegiatan,
        array $output,
        array $files = [],
        bool $revokeApprovedOnExisting = false
    ): void {

        $this->validateCreate($tanggal, $kegiatan, $output, $files);

        if (!$revokeApprovedOnExisting) {
            $this->assertTanggalEditableByPegawai($tanggal);
        }

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
        if (($data['approval_status'] ?? 'pending') === 'approved') {
            throw new Exception("Kegiatan sudah diproses dan ditandatangani admin sehingga tidak dapat diubah.");
        }

        $isApprovalRevoked = $this->hasRevokedApproval($data);
        if (!$isApprovalRevoked) {
            $this->assertTanggalEditableByPegawai((string) $laporan['tanggal']);
        }

        $this->laporanHarian->markKegiatanPending($kegiatanId, $isApprovalRevoked);

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
        if (($data['approval_status'] ?? 'pending') === 'approved') {
            throw new Exception("Kegiatan sudah diproses dan ditandatangani admin sehingga tidak dapat dihapus.");
        }

        if (!$this->hasRevokedApproval($data)) {
            $this->assertTanggalEditableByPegawai((string) $laporan['tanggal']);
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

        $this->laporanHarian->revokeApproval($kegiatanId);

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

        $this->laporanHarian->revokeApproval($kegiatanId);

        $this->refreshKegiatanCount($laporanId);
    }
}
