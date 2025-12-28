<?php

class LaporanKegiatanModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create($laporan_id, $kegiatan, $output, $bukti)
    {
        $stmt = $this->db->prepare("
                INSERT INTO laporan_kegiatan (laporan_id, kegiatan, output, bukti)
                VALUES (?, ?, ?, ?)
            ");
        return $stmt->execute([$laporan_id, $kegiatan, $output, $bukti]);
    }

    public function getByLaporanId(int $laporanId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM laporan_kegiatan WHERE laporan_id = ?"
        );
        $stmt->execute([$laporanId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM laporan_kegiatan WHERE id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function update(
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

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM laporan_kegiatan WHERE id = ?"
        );
        $stmt->execute([$id]);
    }

    // Ambil semua kegiatan (join dengan laporan_harian)
    public function getAll()
    {
        $sql = "
                SELECT lk.*, lh.tanggal 
                FROM laporan_kegiatan lk
                JOIN laporan_harian lh ON lk.laporan_id = lh.id
                ORDER BY lh.tanggal DESC, lk.id DESC
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ambil semua kegiatan untuk user tertentu
    public function getByUserId($userId)
    {
        $sql = "
                SELECT lk.*, lh.tanggal
                FROM laporan_kegiatan lk
                JOIN laporan_harian lh ON lk.laporan_id = lh.id
                WHERE lh.user_id = :user_id
                ORDER BY lh.tanggal DESC, lk.id DESC
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // Filter berdasarkan user + rentang tanggal (userId wajib)
    public function filterTanggal($userId, $start, $end)
    {
        $sql = "
                SELECT lk.*, lh.tanggal
                FROM laporan_kegiatan lk
                JOIN laporan_harian lh ON lk.laporan_id = lh.id
                WHERE lh.user_id = :user_id
                AND lh.tanggal >= :start
                AND lh.tanggal <= :end
                ORDER BY lh.tanggal DESC, lk.id DESC
            ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':start'   => $start,
            ':end'     => $end
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function filterAdmin($keyword, $start, $end)
    {
        $sql = "SELECT 
                k.id,
                k.kegiatan,
                k.output,
                k.bukti,
                h.tanggal,
                p.nama AS nama_pegawai
            FROM laporan_kegiatan k
            JOIN laporan_harian h ON h.id = k.laporan_id
            JOIN user p ON p.id = h.user_id
            WHERE DATE(h.tanggal) BETWEEN :start AND :end";

        $params = [
            ':start' => $start,
            ':end'   => $end
        ];

        // Filter by keyword
        if (!empty($keyword)) {
            $sql .= " AND (
                    k.kegiatan LIKE :keyword OR
                    k.output LIKE :keyword OR
                    p.nama LIKE :keyword
                 )";
            $params[':keyword'] = '%' . $keyword . '%';
        }

        $sql .= " ORDER BY h.tanggal DESC, k.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countByLaporanId(int $laporanId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM laporan_kegiatan WHERE laporan_id = ?"
        );
        $stmt->execute([$laporanId]);

        return (int) $stmt->fetchColumn();
    }
}
