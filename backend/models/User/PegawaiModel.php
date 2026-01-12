<?php

class PegawaiModel
{
    private PDO $db;
    private string $table = 'user';
    public function __construct()
    {
        $this->db = Database::getConnection();
    } /* ========================= * READ * ========================= */
    public function getAllPegawai(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE role = 'pegawai' ORDER BY nama ASC, jabatan ASC ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function findPegawaiById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id AND role = 'pegawai' LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    public function findPegawaiByIdentifier(string $value): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE role = 'pegawai' AND (nip = :nip OR nik = :nik) LIMIT 1");
        $stmt->execute([':nip' => $value, ':nik' => $value,]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /* ========================= * EXISTS * ========================= */
    public function existsByNik(string $nik): bool
    {
        return $this->exists('nik', $nik);
    }
    public function existsByNip(string $nip): bool
    {
        return $this->exists('nip', $nip);
    }
    public function existsByEmail(string $email): bool
    {
        return $this->exists('email', $email);
    }
    private function exists(string $field, string $value): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM {$this->table} WHERE {$field} = :v AND role = 'pegawai' LIMIT 1");
        $stmt->execute([':v' => $value]);
        return (bool) $stmt->fetchColumn();
    }

    /* ========================= * WRITE * ========================= */
    public function create(array $data): void
    {
        $data['role'] ??= 'pegawai';
        $sql = "INSERT INTO {$this->table} (foto, nama, nip, nik, jabatan, jenis_kelamin, password, email, no_wa, role) VALUES (:foto, :nama, :nip, :nik, :jabatan, :jenis_kelamin, :password, :email, :no_wa, :role)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
    }

    public function update(int $id, array $data): bool
    {
        $allowed = [
            'foto',
            'nama',
            'nip',
            'nik',
            'jabatan',
            'jenis_kelamin',
            'password',
            'email',
            'no_wa',
        ];

        $fields = [];
        $params = ['id' => $id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            throw new Exception('Tidak ada data yang diperbarui.');
        }

        $sql = "UPDATE {$this->table}
            SET " . implode(', ', $fields) . "
            WHERE id = :id AND role = 'pegawai'";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    /* =========================
 * DASHBOARD METHODS
 * ========================= */

    /**
     * Hitung total pegawai
     */
    public function countPegawai(): int
    {
        $stmt = $this->db->prepare("
        SELECT COUNT(*) 
        FROM {$this->table}
        WHERE role = 'pegawai'
    ");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Hitung pegawai berdasarkan jenis kelamin
     * Return: ['L' => 40, 'P' => 55]
     */
    public function countByGender(): array
    {
        $stmt = $this->db->prepare("
        SELECT jenis_kelamin, COUNT(*) total
        FROM {$this->table}
        WHERE role = 'pegawai'
        GROUP BY jenis_kelamin
    ");
        $stmt->execute();

        $result = [
            'Laki-laki' => 0,
            'Perempuan' => 0
        ];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (isset($result[$row['jenis_kelamin']])) {
                $result[$row['jenis_kelamin']] = (int) $row['total'];
            }
        }

        return $result;
    }
}
