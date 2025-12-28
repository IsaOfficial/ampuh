<?php

class AdminModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, nama, role FROM user WHERE id = ? AND role = 'admin'"
        );
        $stmt->execute([$id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ?: null;
    }

    public function countPegawai(): int
    {
        return (int) $this->db
            ->query("SELECT COUNT(*) FROM user WHERE role = 'pegawai'")
            ->fetchColumn();
    }

    public function countLaporan(): int
    {
        return (int) $this->db
            ->query("SELECT COUNT(*) FROM laporan_harian")
            ->fetchColumn();
    }
}
