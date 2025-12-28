<?php

class PegawaiModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // Mendapatkan semua pegawai + keyword search untuk ekspor => Fitur Admin
    public function getAll(string $keyword = ''): array
    {
        $sql = "SELECT * FROM user WHERE role = 'pegawai'";
        $params = [];

        if ($keyword !== '') {
            $sql .= " AND (
                nama LIKE :kw OR nip LIKE :kw OR jabatan LIKE :kw
                OR email LIKE :kw OR no_wa LIKE :kw
            )";
            $params[':kw'] = "%{$keyword}%";
        }

        $sql .= " ORDER BY nama ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM user WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByNipOrNik(string $identifier): ?array
    {
        $sql = "SELECT * FROM user 
            WHERE nip = :nip OR nik = :nik
            LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nip' => $identifier,
            ':nik' => $identifier
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(array $data): void
    {
        $sql = "INSERT INTO user 
        (foto, nama, nip, nik, jabatan, jenis_kelamin, password, email, no_wa)
        VALUES (:foto, :nama, :nip, :nik, :jabatan, :jenis_kelamin, :password, :email, :no_wa)";

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
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (!$fields) {
            return false;
        }

        $sql = "UPDATE user SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM user WHERE id = ?");
        $stmt->execute([$id]);
    }
}
