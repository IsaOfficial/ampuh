<?php

class PegawaiModel
{
    private PDO $db;
    private string $table = 'user';
    public function __construct()
    {
        $this->db = Database::getConnection();
    } /* ========================= * READ * ========================= */
    public function getAllPegawai(?string $keyword = null, ?string $jabatan = null, ?string $jenisKelamin = null): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE role = 'pegawai' AND deleted_at IS NULL";
        $conditions = [];
        $params = [];

        if ($keyword !== null && trim($keyword) !== '') {
            $conditions[] = "(nama LIKE :keyword OR nip LIKE :keyword OR nik LIKE :keyword OR jabatan LIKE :keyword OR email LIKE :keyword OR no_wa LIKE :keyword)";
            $params[':keyword'] = '%' . trim($keyword) . '%';
        }

        if ($jabatan !== null && trim($jabatan) !== '') {
            $conditions[] = "jabatan = :jabatan";
            $params[':jabatan'] = trim($jabatan);
        }

        if ($jenisKelamin !== null && trim($jenisKelamin) !== '') {
            $conditions[] = "jenis_kelamin = :jenis_kelamin";
            $params[':jenis_kelamin'] = trim($jenisKelamin);
        }

        if ($conditions) {
            $sql .= ' AND ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY nama ASC, jabatan ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDeletedPegawai(?string $keyword = null, ?string $jabatan = null, ?string $jenisKelamin = null): array
    {
        $sql = "
            SELECT u.*, deleted_by_user.nama AS deleted_by_name
            FROM {$this->table} u
            LEFT JOIN {$this->table} deleted_by_user ON deleted_by_user.id = u.deleted_by
            WHERE u.role = 'pegawai'
              AND u.deleted_at IS NOT NULL
        ";
        $conditions = [];
        $params = [];

        if ($keyword !== null && trim($keyword) !== '') {
            $conditions[] = "(u.nama LIKE :keyword OR u.nip LIKE :keyword OR u.nik LIKE :keyword OR u.jabatan LIKE :keyword OR u.email LIKE :keyword OR u.no_wa LIKE :keyword)";
            $params[':keyword'] = '%' . trim($keyword) . '%';
        }

        if ($jabatan !== null && trim($jabatan) !== '') {
            $conditions[] = "u.jabatan = :jabatan";
            $params[':jabatan'] = trim($jabatan);
        }

        if ($jenisKelamin !== null && trim($jenisKelamin) !== '') {
            $conditions[] = "u.jenis_kelamin = :jenis_kelamin";
            $params[':jenis_kelamin'] = trim($jenisKelamin);
        }

        if ($conditions) {
            $sql .= ' AND ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY u.deleted_at DESC, u.nama ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buildPegawaiDataConditions(
        bool $deleted,
        ?string $keyword = null,
        ?string $jabatan = null,
        ?string $jenisKelamin = null,
        ?string $search = null
    ): array {
        $conditions = [
            "u.role = 'pegawai'",
            $deleted ? 'u.deleted_at IS NOT NULL' : 'u.deleted_at IS NULL',
        ];
        $params = [];

        $keyword = trim((string) $keyword);
        if ($keyword !== '') {
            $conditions[] = "(u.nama LIKE :keyword OR u.nip LIKE :keyword OR u.nik LIKE :keyword OR u.jabatan LIKE :keyword OR u.email LIKE :keyword OR u.no_wa LIKE :keyword)";
            $params['keyword'] = '%' . $keyword . '%';
        }

        $jabatan = trim((string) $jabatan);
        if ($jabatan !== '') {
            $conditions[] = 'u.jabatan = :jabatan';
            $params['jabatan'] = $jabatan;
        }

        $jenisKelamin = trim((string) $jenisKelamin);
        if ($jenisKelamin !== '') {
            $conditions[] = 'u.jenis_kelamin = :jenis_kelamin';
            $params['jenis_kelamin'] = $jenisKelamin;
        }

        $search = trim((string) $search);
        if ($search !== '') {
            $searchColumns = ['u.nama', 'u.nip', 'u.nik', 'u.jabatan', 'u.jenis_kelamin', 'u.email', 'u.no_wa'];
            $searchParts = [];
            foreach ($searchColumns as $index => $column) {
                $param = 'search_' . $index;
                $searchParts[] = "{$column} LIKE :{$param}";
                $params[$param] = '%' . $search . '%';
            }
            $conditions[] = '(' . implode(' OR ', $searchParts) . ')';
        }

        return [$conditions, $params];
    }

    private function pegawaiDataFromSql(bool $deleted): string
    {
        $sql = " FROM {$this->table} u";
        if ($deleted) {
            $sql .= " LEFT JOIN {$this->table} deleted_by_user ON deleted_by_user.id = u.deleted_by";
        }

        return $sql;
    }

    public function countPegawaiData(
        bool $deleted = false,
        ?string $keyword = null,
        ?string $jabatan = null,
        ?string $jenisKelamin = null,
        ?string $search = null
    ): int {
        [$conditions, $params] = $this->buildPegawaiDataConditions($deleted, $keyword, $jabatan, $jenisKelamin, $search);
        $sql = 'SELECT COUNT(*)' . $this->pegawaiDataFromSql($deleted) . ' WHERE ' . implode(' AND ', $conditions);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function getPegawaiDataPage(
        bool $deleted = false,
        ?string $keyword = null,
        ?string $jabatan = null,
        ?string $jenisKelamin = null,
        ?string $search = null,
        string $orderColumn = 'u.nama',
        string $orderDirection = 'ASC',
        int $offset = 0,
        int $limit = 10
    ): array {
        $allowedOrderColumns = [
            'u.nama',
            'u.nip',
            'u.nik',
            'u.jabatan',
            'u.jenis_kelamin',
            'u.email',
            'u.no_wa',
            'u.deleted_at',
            'u.id',
        ];

        if (!in_array($orderColumn, $allowedOrderColumns, true)) {
            $orderColumn = $deleted ? 'u.deleted_at' : 'u.nama';
        }

        $orderDirection = strtoupper($orderDirection) === 'DESC' ? 'DESC' : 'ASC';
        $offset = max(0, $offset);
        $limit = max(1, min(100, $limit));

        $select = $deleted
            ? 'SELECT u.*, deleted_by_user.nama AS deleted_by_name'
            : 'SELECT u.*';
        [$conditions, $params] = $this->buildPegawaiDataConditions($deleted, $keyword, $jabatan, $jenisKelamin, $search);
        $sql = $select
            . $this->pegawaiDataFromSql($deleted)
            . ' WHERE ' . implode(' AND ', $conditions)
            . " ORDER BY {$orderColumn} {$orderDirection}, u.id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findPegawaiById(int $id, bool $includeDeleted = false): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id AND role = 'pegawai'";
        if (!$includeDeleted) {
            $sql .= " AND deleted_at IS NULL";
        }
        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    public function findPegawaiByIdentifier(string $value): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE role = 'pegawai' AND deleted_at IS NULL AND (nip = :nip OR nik = :nik) LIMIT 1");
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

    public function softDelete(int $id, int $deletedBy, ?string $reason = null): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET deleted_at = NOW(),
                deleted_by = :deleted_by,
                delete_reason = :reason
            WHERE id = :id
              AND role = 'pegawai'
              AND deleted_at IS NULL
        ");

        $stmt->execute([
            ':id' => $id,
            ':deleted_by' => $deletedBy,
            ':reason' => $reason,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET deleted_at = NULL,
                deleted_by = NULL,
                delete_reason = NULL
            WHERE id = :id
              AND role = 'pegawai'
              AND deleted_at IS NOT NULL
        ");

        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function forceDelete(int $id): void
    {
        $this->delete($id);
    }

    public function countLaporanByPegawai(int $id): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM laporan_harian
            WHERE user_id = :id
        ");
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn();
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
          AND deleted_at IS NULL
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
          AND deleted_at IS NULL
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
