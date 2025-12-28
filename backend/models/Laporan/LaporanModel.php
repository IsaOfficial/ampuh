<?php

class LaporanModel
{
    private PDO $db;
    private DocumentUploadService $service;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->service = new DocumentUploadService();
    }

    public function getAdminLaporan(string $keyword, string $start, string $end): array
    {
        $sql = "
            SELECT 
                lk.id AS kegiatan_id,
                lh.tanggal,
                u.nama AS nama_pegawai,
                u.foto AS foto_pegawai,
                lk.kegiatan,
                lk.output,
                lk.bukti
            FROM laporan_kegiatan lk
            JOIN laporan_harian lh ON lk.laporan_id = lh.id
            JOIN user u ON lh.user_id = u.id
            WHERE lh.tanggal BETWEEN ? AND ?
        ";

        $params = [$start, $end];

        if ($keyword !== '') {
            $sql .= " AND (u.nama LIKE ? OR lk.kegiatan LIKE ? OR lk.output LIKE ?)";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
        }

        $sql .= " ORDER BY lh.tanggal DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createHarian(int $userId, string $tanggal): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO laporan_harian (user_id, tanggal, created_at)
             VALUES (?, ?, NOW())"
        );
        $stmt->execute([$userId, $tanggal]);
        return (int)$this->db->lastInsertId();
    }

    public function createKegiatan(
        int $laporanId,
        string $kegiatan,
        string $output,
        ?string $bukti
    ): void {
        $stmt = $this->db->prepare(
            "INSERT INTO laporan_kegiatan (laporan_id, kegiatan, output, bukti)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$laporanId, $kegiatan, $output, $bukti]);
    }

    public function createLaporan(
        int $pegawaiId,
        string $tanggal,
        string $kegiatan,
        string $output,
        ?string $bukti
    ): void {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "INSERT INTO laporan_harian (user_id, tanggal, created_at)
                 VALUES (?, ?, NOW())"
            );
            $stmt->execute([$pegawaiId, $tanggal]);

            $laporanId = $this->db->lastInsertId();

            $stmt = $this->db->prepare(
                "INSERT INTO laporan_kegiatan (laporan_id, kegiatan, output, bukti)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$laporanId, $kegiatan, $output, $bukti]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getKegiatanById(int $id): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM laporan_kegiatan WHERE id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateKegiatan(
        int $id,
        string $kegiatan,
        string $output,
        ?string $bukti
    ): void {
        $stmt = $this->db->prepare(
            "UPDATE laporan_kegiatan
             SET kegiatan = ?, output = ?, bukti = ?
             WHERE id = ?"
        );
        $stmt->execute([$kegiatan, $output, $bukti, $id]);
    }

    public function updateKegiatanCount(int $laporanId): void
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM laporan_kegiatan WHERE laporan_id = ?"
        );
        $stmt->execute([$laporanId]);
        $count = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare(
            "UPDATE laporan_harian SET kegiatan_count = ? WHERE id = ?"
        );
        $stmt->execute([$count, $laporanId]);
    }

    public function updateLaporan(
        int $id,
        string $kegiatan,
        string $output,
        ?string $bukti
    ): void {
        try {
            $this->db->beginTransaction();

            $data = $this->getKegiatanById($id);
            if (!$data) {
                throw new Exception('Data laporan tidak ditemukan.');
            }

            $this->updateKegiatan($id, $kegiatan, $output, $bukti);
            $this->updateKegiatanCount($data['laporan_id']);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteKegiatan(int $id): void
    {
        try {
            $this->db->beginTransaction();

            $data = $this->getKegiatanById($id);
            if (!$data) {
                throw new Exception('Data kegiatan tidak ditemukan.');
            }

            $stmt = $this->db->prepare(
                "DELETE FROM laporan_kegiatan WHERE id = ?"
            );
            $stmt->execute([$id]);

            $this->updateKegiatanCount($data['laporan_id']);

            $this->db->commit();

            // File dihapus setelah DB sukses
            $this->service->delete($data['dir'], $data['file_name']);
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
