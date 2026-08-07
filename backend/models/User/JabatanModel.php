<?php

class JabatanModel
{
    private PDO $db;
    private string $table = 'jabatan';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function all(bool $activeOnly = false): array
    {
        $sql = "
            SELECT j.*,
                   COUNT(u.id) AS pegawai_count
            FROM {$this->table} j
            LEFT JOIN user u
              ON u.jabatan = j.nama
             AND u.role = 'pegawai'
             AND u.deleted_at IS NULL
        ";

        if ($activeOnly) {
            $sql .= " WHERE j.is_active = 1";
        }

        $sql .= " GROUP BY j.id ORDER BY j.nama ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function activeNames(): array
    {
        $stmt = $this->db->prepare("SELECT nama FROM {$this->table} WHERE is_active = 1 ORDER BY nama ASC");
        $stmt->execute();

        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'nama');
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function existsByName(string $name, ?int $exceptId = null): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE nama = :nama";
        $params = ['nama' => $name];

        if ($exceptId !== null) {
            $sql .= " AND id <> :id";
            $params['id'] = $exceptId;
        }

        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function countPegawai(string $name): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM user
            WHERE role = 'pegawai'
              AND deleted_at IS NULL
              AND jabatan = :nama
        ");
        $stmt->execute(['nama' => $name]);

        return (int) $stmt->fetchColumn();
    }

    public function create(string $name, bool $isActive = true): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (nama, is_active)
            VALUES (:nama, :is_active)
        ");
        $stmt->execute([
            'nama' => $name,
            'is_active' => $isActive ? 1 : 0,
        ]);
    }

    public function update(int $id, string $name, bool $isActive): void
    {
        $current = $this->findById($id);
        if (!$current) {
            throw new Exception('Jabatan tidak ditemukan.');
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                UPDATE {$this->table}
                SET nama = :nama,
                    is_active = :is_active,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmt->execute([
                'id' => $id,
                'nama' => $name,
                'is_active' => $isActive ? 1 : 0,
            ]);

            if ($current['nama'] !== $name) {
                $updatePegawai = $this->db->prepare("
                    UPDATE user
                    SET jabatan = :new_name
                    WHERE role = 'pegawai'
                      AND jabatan = :old_name
                ");
                $updatePegawai->execute([
                    'new_name' => $name,
                    'old_name' => $current['nama'],
                ]);
            }

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function setActive(int $id, bool $isActive): void
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET is_active = :is_active,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        $stmt->execute([
            'id' => $id,
            'is_active' => $isActive ? 1 : 0,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}
