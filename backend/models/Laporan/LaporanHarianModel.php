<?php

class LaporanHarianModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create($user_id, $tanggal)
    {
        $stmt = $this->db->prepare("
                INSERT INTO laporan_harian (user_id, tanggal, created_at) 
                VALUES (?, ?, NOW())
            ");
        $stmt->execute([$user_id, $tanggal]);
        return $this->db->lastInsertId();
    }

    public function findByUserAndDate($user_id, $tanggal)
    {
        $stmt = $this->db->prepare("
                SELECT * FROM laporan_harian 
                WHERE user_id = ? AND tanggal = ?
            ");
        $stmt->execute([$user_id, $tanggal]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateKegiatanCount($laporan_id, $count)
    {
        $stmt = $this->db->prepare("
                UPDATE laporan_harian SET kegiatan_count = ? 
                WHERE id = ?
            ");
        return $stmt->execute([$count, $laporan_id]);
    }

    public function getAll()
    {
        $stmt = $this->db->prepare("SELECT * FROM laporan_harian ORDER BY tanggal DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function hasSubmittedToday(int $userId): bool
    {
        $sql = "
        SELECT 1
        FROM laporan_harian
        WHERE user_id = ?
          AND tanggal = CURDATE()
        LIMIT 1
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        return (bool) $stmt->fetchColumn();
    }
}
