<?php

class AdminModel
{
    private PDO $db;
    private string $table = 'user';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAdminById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, nama, role FROM user WHERE id = ? AND role = 'admin'"
        );
        $stmt->execute([$id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ?: null;
    }

    public function findAdminByIdentifier(string $value): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table}
         WHERE role = 'admin'
         AND (nip = :nip OR nik = :nik)
         LIMIT 1"
        );

        $stmt->execute([
            ':nip' => $value,
            ':nik' => $value,
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
