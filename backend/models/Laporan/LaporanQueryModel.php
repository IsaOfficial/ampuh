<?php

class LaporanQueryModel
{
    public function __construct(
        private PDO $db
    ) {}

    private function buildAdminConditions(
        ?int $pegawaiId = null,
        ?string $start = null,
        ?string $end = null,
        ?string $status = null,
        ?string $search = null
    ): array {
        $conditions = [];
        $params = [];

        if ($pegawaiId !== null) {
            $conditions[] = 'u.id = :pegawai_id';
            $params['pegawai_id'] = $pegawaiId;
        }

        if ($start !== null) {
            $conditions[] = 'lh.tanggal >= :start';
            $params['start'] = $start;
        }

        if ($end !== null) {
            $conditions[] = 'lh.tanggal <= :end';
            $params['end'] = $end;
        }

        if ($status === 'deleted') {
            $conditions[] = 'lk.deleted_at IS NOT NULL';
        } else {
            $conditions[] = 'lk.deleted_at IS NULL';
        }

        if ($status === 'revoked') {
            $conditions[] = 'lk.approval_revoked_at IS NOT NULL';
        } elseif (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $conditions[] = "COALESCE(lk.approval_status, 'pending') = :status";
            $params['status'] = $status;
        }

        if ($search !== null && $search !== '') {
            $searchColumns = [
                'u.nama',
                'u.nip',
                'u.nik',
                'u.jabatan',
                'lk.kegiatan',
                'lk.output',
                'lk.bukti',
                "COALESCE(lk.approval_status, 'pending')",
            ];
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

    private function adminFromSql(): string
    {
        return "
            FROM laporan_kegiatan lk
            JOIN laporan_harian lh
                ON lh.id = lk.laporan_id
            JOIN user u
                ON u.id = lh.user_id
            LEFT JOIN user admin
                ON admin.id = lk.approved_by
            LEFT JOIN user deleter
                ON deleter.id = lk.deleted_by
        ";
    }

    private function appendWhere(string $sql, array $conditions): string
    {
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        return $sql;
    }

    public function getLaporanByAdmin(
        ?int $pegawaiId = null,
        ?string $start = null,
        ?string $end = null,
        ?string $status = null
    ): array {
        $sql = "
            SELECT
                lh.id        AS laporan_id,
                lh.tanggal,
                lh.kegiatan_count,

                lk.id        AS kegiatan_id,
                lk.kegiatan,
                lk.output,
                lk.bukti,
                COALESCE(lk.approval_status, 'pending') AS status,
                lk.approved_by,
                lk.approved_at,
                lk.verification_token,
                lk.document_hash,
                lk.approval_revoked_at,
                lk.signature_note,
                lk.rejection_note,
                lk.deleted_at,
                lk.deleted_by,
                lk.delete_reason,
                admin.nama AS signed_name,
                deleter.nama AS deleted_by_name,

                u.id         AS pegawai_id,
                u.nama       AS nama_pegawai,
                u.nip,
                u.nik,
                u.jabatan,
                u.foto       AS foto_pegawai
        ";
        $sql .= $this->adminFromSql();

        [$conditions, $params] = $this->buildAdminConditions($pegawaiId, $start, $end, $status);
        $sql = $this->appendWhere($sql, $conditions);

        $sql .= ' ORDER BY lh.tanggal DESC, lk.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countLaporanByAdmin(
        ?int $pegawaiId = null,
        ?string $start = null,
        ?string $end = null,
        ?string $status = null,
        ?string $search = null
    ): int {
        [$conditions, $params] = $this->buildAdminConditions($pegawaiId, $start, $end, $status, $search);
        $sql = $this->appendWhere('SELECT COUNT(*) ' . $this->adminFromSql(), $conditions);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function getLaporanByAdminPage(
        ?int $pegawaiId = null,
        ?string $start = null,
        ?string $end = null,
        ?string $status = null,
        ?string $search = null,
        string $orderColumn = 'lh.tanggal',
        string $orderDirection = 'DESC',
        int $offset = 0,
        int $limit = 10
    ): array {
        $allowedOrderColumns = [
            'lh.tanggal',
            'u.nama',
            'lk.kegiatan',
            'lk.output',
            'lk.bukti',
            "COALESCE(lk.approval_status, 'pending')",
            'lk.deleted_at',
            'lk.id',
        ];

        if (!in_array($orderColumn, $allowedOrderColumns, true)) {
            $orderColumn = 'lh.tanggal';
        }

        $orderDirection = strtoupper($orderDirection) === 'ASC' ? 'ASC' : 'DESC';
        $offset = max(0, $offset);
        $limit = max(1, min(100, $limit));

        $sql = "
            SELECT
                lh.id        AS laporan_id,
                lh.tanggal,
                lh.kegiatan_count,

                lk.id        AS kegiatan_id,
                lk.kegiatan,
                lk.output,
                lk.bukti,
                COALESCE(lk.approval_status, 'pending') AS status,
                lk.approved_by,
                lk.approved_at,
                lk.verification_token,
                lk.document_hash,
                lk.approval_revoked_at,
                lk.signature_note,
                lk.rejection_note,
                lk.deleted_at,
                lk.deleted_by,
                lk.delete_reason,
                admin.nama AS signed_name,
                deleter.nama AS deleted_by_name,

                u.id         AS pegawai_id,
                u.nama       AS nama_pegawai,
                u.nip,
                u.nik,
                u.jabatan,
                u.foto       AS foto_pegawai
        ";
        $sql .= $this->adminFromSql();

        [$conditions, $params] = $this->buildAdminConditions($pegawaiId, $start, $end, $status, $search);
        $sql = $this->appendWhere($sql, $conditions);
        $sql .= " ORDER BY {$orderColumn} {$orderDirection}, lk.id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLaporanByPegawai(
        int $pegawaiId,
        ?string $start = null,
        ?string $end = null,
        bool $approvedOnly = false
    ): array {
        $sql = "
        SELECT
            lh.id        AS laporan_id,
            lh.tanggal,

            lk.id        AS kegiatan_id,
            lk.kegiatan,
            lk.output,
            lk.bukti,
            COALESCE(lk.approval_status, 'pending') AS status,
            lk.approved_by,
            lk.approved_at,
            lk.verification_token,
            lk.document_hash,
            lk.approval_revoked_at,
            lk.signature_note,
            lk.rejection_note,
            lk.deleted_at,
            admin.nama AS signed_name
        FROM laporan_kegiatan lk
        JOIN laporan_harian lh
            ON lh.id = lk.laporan_id
        LEFT JOIN user admin
            ON admin.id = lk.approved_by
        WHERE lh.user_id = :pegawai_id
          AND lk.deleted_at IS NULL
    ";

        $params = [
            'pegawai_id' => $pegawaiId
        ];

        if ($approvedOnly) {
            $sql .= " AND lk.approval_status = 'approved' AND lk.approval_revoked_at IS NULL";
        }

        if ($start !== null) {
            $sql .= " AND lh.tanggal >= :start";
            $params['start'] = $start;
        }

        if ($end !== null) {
            $sql .= " AND lh.tanggal <= :end";
            $params['end'] = $end;
        }

        $sql .= " ORDER BY lh.tanggal DESC, lk.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDeletedLaporanByPegawai(
        int $pegawaiId,
        ?string $start = null,
        ?string $end = null
    ): array {
        $sql = "
        SELECT
            lh.id        AS laporan_id,
            lh.tanggal,

            lk.id        AS kegiatan_id,
            lk.kegiatan,
            lk.output,
            lk.bukti,
            COALESCE(lk.approval_status, 'pending') AS status,
            lk.approved_by,
            lk.approved_at,
            lk.verification_token,
            lk.document_hash,
            lk.approval_revoked_at,
            lk.signature_note,
            lk.rejection_note,
            lk.deleted_at,
            lk.deleted_by,
            lk.delete_reason
        FROM laporan_kegiatan lk
        JOIN laporan_harian lh
            ON lh.id = lk.laporan_id
        WHERE lh.user_id = :pegawai_id
          AND lk.deleted_at IS NOT NULL
    ";

        $params = [
            'pegawai_id' => $pegawaiId
        ];

        if ($start !== null) {
            $sql .= " AND lh.tanggal >= :start";
            $params['start'] = $start;
        }

        if ($end !== null) {
            $sql .= " AND lh.tanggal <= :end";
            $params['end'] = $end;
        }

        $sql .= " ORDER BY lk.deleted_at DESC, lh.tanggal DESC, lk.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buildPegawaiConditions(
        int $pegawaiId,
        bool $deleted = false,
        ?string $start = null,
        ?string $end = null,
        bool $approvedOnly = false,
        ?string $search = null
    ): array {
        $conditions = [
            'lh.user_id = :pegawai_id',
            $deleted ? 'lk.deleted_at IS NOT NULL' : 'lk.deleted_at IS NULL',
        ];
        $params = [
            'pegawai_id' => $pegawaiId,
        ];

        if ($approvedOnly) {
            $conditions[] = "lk.approval_status = 'approved'";
            $conditions[] = 'lk.approval_revoked_at IS NULL';
        }

        if ($start !== null) {
            $conditions[] = 'lh.tanggal >= :start';
            $params['start'] = $start;
        }

        if ($end !== null) {
            $conditions[] = 'lh.tanggal <= :end';
            $params['end'] = $end;
        }

        $search = trim((string) $search);
        if ($search !== '') {
            $searchColumns = [
                'lk.kegiatan',
                'lk.output',
                'lk.bukti',
                "COALESCE(lk.approval_status, 'pending')",
                'lk.rejection_note',
                'lk.signature_note',
            ];
            $searchParts = [];

            foreach ($searchColumns as $index => $column) {
                $param = 'pegawai_search_' . $index;
                $searchParts[] = "{$column} LIKE :{$param}";
                $params[$param] = '%' . $search . '%';
            }

            $conditions[] = '(' . implode(' OR ', $searchParts) . ')';
        }

        return [$conditions, $params];
    }

    private function pegawaiFromSql(): string
    {
        return "
            FROM laporan_kegiatan lk
            JOIN laporan_harian lh
                ON lh.id = lk.laporan_id
            LEFT JOIN user admin
                ON admin.id = lk.approved_by
        ";
    }

    public function countLaporanByPegawaiData(
        int $pegawaiId,
        bool $deleted = false,
        ?string $start = null,
        ?string $end = null,
        bool $approvedOnly = false,
        ?string $search = null
    ): int {
        [$conditions, $params] = $this->buildPegawaiConditions($pegawaiId, $deleted, $start, $end, $approvedOnly, $search);
        $sql = 'SELECT COUNT(*) ' . $this->pegawaiFromSql() . ' WHERE ' . implode(' AND ', $conditions);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function getLaporanByPegawaiPage(
        int $pegawaiId,
        bool $deleted = false,
        ?string $start = null,
        ?string $end = null,
        bool $approvedOnly = false,
        ?string $search = null,
        string $orderColumn = 'lh.tanggal',
        string $orderDirection = 'DESC',
        int $offset = 0,
        int $limit = 10
    ): array {
        $allowedOrderColumns = [
            'lh.tanggal',
            'lk.kegiatan',
            'lk.output',
            'lk.bukti',
            "COALESCE(lk.approval_status, 'pending')",
            'lk.deleted_at',
            'lk.id',
        ];

        if (!in_array($orderColumn, $allowedOrderColumns, true)) {
            $orderColumn = $deleted ? 'lk.deleted_at' : 'lh.tanggal';
        }

        $orderDirection = strtoupper($orderDirection) === 'ASC' ? 'ASC' : 'DESC';
        $offset = max(0, $offset);
        $limit = max(1, min(100, $limit));

        $sql = "
            SELECT
                lh.id AS laporan_id,
                lh.tanggal,
                lk.id AS kegiatan_id,
                lk.kegiatan,
                lk.output,
                lk.bukti,
                COALESCE(lk.approval_status, 'pending') AS status,
                lk.approved_by,
                lk.approved_at,
                lk.verification_token,
                lk.document_hash,
                lk.approval_revoked_at,
                lk.signature_note,
                lk.rejection_note,
                lk.deleted_at,
                lk.deleted_by,
                lk.delete_reason,
                admin.nama AS signed_name
        ";
        $sql .= $this->pegawaiFromSql();

        [$conditions, $params] = $this->buildPegawaiConditions($pegawaiId, $deleted, $start, $end, $approvedOnly, $search);
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
        $sql .= " ORDER BY {$orderColumn} {$orderDirection}, lk.id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDetailLaporan(int $laporanId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                lh.id        AS laporan_id,
                lh.tanggal,

                lk.id        AS kegiatan_id,
                lk.kegiatan,
                lk.output,
                lk.bukti,
                COALESCE(lk.approval_status, 'pending') AS status,
                lk.approved_by,
                lk.approved_at,
                lk.verification_token,
                lk.document_hash,
                lk.approval_revoked_at,
                lk.signature_note,
                lk.rejection_note,
                lk.deleted_at,
                admin.nama AS signed_name,

                u.id         AS pegawai_id,
                u.nama       AS nama_pegawai
            FROM laporan_kegiatan lk
            JOIN laporan_harian lh
                ON lh.id = lk.laporan_id
            JOIN user u
                ON u.id = lh.user_id
            LEFT JOIN user admin
                ON admin.id = lk.approved_by
            WHERE lh.id = :id
              AND lk.deleted_at IS NULL
        ");

        $stmt->execute([
            ':id' => $laporanId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
