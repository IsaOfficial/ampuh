<?php

class LaporanHarianModel
{
    protected string $table = 'laporan_harian';

    public function __construct(
        protected PDO $db
    ) {}

    public function findByUserAndDate(int $pegawaiId, string $tanggal): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM {$this->table}
            WHERE user_id = :pegawai_id
              AND tanggal = :tanggal
            LIMIT 1
        ");

        $stmt->execute([
            ':pegawai_id' => $pegawaiId,
            ':tanggal'    => $tanggal,
        ]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM {$this->table}
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([':id' => $id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ?: null;
    }

    public function create(int $pegawaiId, string $tanggal): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table}
                (user_id, tanggal, kegiatan_count)
            VALUES
                (:pegawai_id, :tanggal, 0)
        ");

        $stmt->execute([
            ':pegawai_id' => $pegawaiId,
            ':tanggal'    => $tanggal,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("
            DELETE FROM {$this->table}
            WHERE id = :id
        ");

        $stmt->execute([':id' => $id]);
    }

    public function approveBulk(array $kegiatanIds, int $adminId, string $adminName, ?string $signatureNote = null): int
    {
        $kegiatanIds = array_values(array_unique(array_filter(array_map('intval', $kegiatanIds))));

        if (!$kegiatanIds) {
            return 0;
        }

        $updated = 0;

        foreach ($kegiatanIds as $kegiatanId) {
            $updated += $this->approveOnce($kegiatanId, $adminId, $signatureNote);
        }

        return $updated;
    }

    private function approveOnce(int $kegiatanId, int $adminId, ?string $signatureNote = null): int
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
            SELECT *
            FROM laporan_kegiatan
            WHERE id = :id
              AND deleted_at IS NULL
            FOR UPDATE
            ");
            $stmt->execute([':id' => $kegiatanId]);

            $kegiatan = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$kegiatan) {
                $this->db->rollBack();
                return 0;
            }

            if (($kegiatan['approval_status'] ?? 'pending') === 'approved' && !empty($kegiatan['verification_token'])) {
                $this->db->commit();
                return 0;
            }

            $approvedAt = date('Y-m-d H:i:s');
            $token = $this->generateUniqueVerificationToken();
            $documentHash = $this->buildDocumentHash($kegiatanId, $approvedAt, $adminId);

            $update = $this->db->prepare("
                UPDATE laporan_kegiatan
                SET
                    approval_status = 'approved',
                    approved_by = :approved_by,
                    approved_at = :approved_at,
                    verification_token = :verification_token,
                    document_hash = :document_hash,
                    approval_revoked_at = NULL,
                    signature_note = :signature_note,
                    rejection_note = NULL
                WHERE id = :id
                  AND deleted_at IS NULL
            ");

            $update->execute([
                ':approved_by'        => $adminId,
                ':approved_at'        => $approvedAt,
                ':verification_token' => $token,
                ':document_hash'      => $documentHash,
                ':signature_note'     => $signatureNote,
                ':id'                 => $kegiatanId,
            ]);

            $this->db->commit();
            return $update->rowCount();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function rejectBulk(array $kegiatanIds, ?string $rejectionNote = null): int
    {
        $kegiatanIds = array_values(array_unique(array_filter(array_map('intval', $kegiatanIds))));

        if (!$kegiatanIds) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($kegiatanIds), '?'));

        $stmt = $this->db->prepare("
            UPDATE laporan_kegiatan
            SET
                approval_status = 'rejected',
                approved_by = NULL,
                approved_at = NULL,
                verification_token = NULL,
                document_hash = NULL,
                approval_revoked_at = NULL,
                signature_note = NULL,
                rejection_note = ?
            WHERE id IN ({$placeholders})
              AND deleted_at IS NULL
        ");

        $stmt->execute(array_merge([$rejectionNote], $kegiatanIds));

        return $stmt->rowCount();
    }

    public function revokeApproval(int $kegiatanId): void
    {
        $stmt = $this->db->prepare("
            UPDATE laporan_kegiatan
            SET
                approval_status = 'pending',
                approval_revoked_at = NOW(),
                rejection_note = NULL
            WHERE id = :id
              AND approval_status = 'approved'
              AND deleted_at IS NULL
        ");

        $stmt->execute([':id' => $kegiatanId]);
    }

    public function revokeBulk(array $kegiatanIds): int
    {
        $kegiatanIds = array_values(array_unique(array_filter(array_map('intval', $kegiatanIds))));

        if (!$kegiatanIds) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($kegiatanIds), '?'));

        $stmt = $this->db->prepare("
            UPDATE laporan_kegiatan
            SET
                approval_status = 'pending',
                approval_revoked_at = NOW(),
                rejection_note = NULL
            WHERE id IN ({$placeholders})
              AND approval_status = 'approved'
              AND deleted_at IS NULL
        ");

        $stmt->execute($kegiatanIds);

        return $stmt->rowCount();
    }
    public function markPending(int $laporanId): void
    {
        $stmt = $this->db->prepare("
            UPDATE laporan_kegiatan
            SET
                approval_status = 'pending',
                approved_by = NULL,
                approved_at = NULL,
                verification_token = NULL,
                document_hash = NULL,
                approval_revoked_at = NULL,
                signature_note = NULL,
                rejection_note = NULL
            WHERE laporan_id = :id
              AND approval_status <> 'approved'
              AND deleted_at IS NULL
        ");

        $stmt->execute([':id' => $laporanId]);
    }


    public function markKegiatanPending(int $kegiatanId, bool $preserveRevokedAt = false): void
    {
        $revokedAtSql = $preserveRevokedAt
            ? 'approval_revoked_at = approval_revoked_at,'
            : 'approval_revoked_at = NULL,';

        $stmt = $this->db->prepare("
            UPDATE laporan_kegiatan
            SET
                approval_status = 'pending',
                approved_by = NULL,
                approved_at = NULL,
                verification_token = NULL,
                document_hash = NULL,
                {$revokedAtSql}
                signature_note = NULL,
                rejection_note = NULL
            WHERE id = :id
              AND approval_status <> 'approved'
              AND deleted_at IS NULL
        ");

        $stmt->execute([':id' => $kegiatanId]);
    }
    public function countByApprovalStatus(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                SUM(CASE
                    WHEN lk.approval_revoked_at IS NOT NULL THEN 1
                    ELSE 0
                END) AS revoked,
                SUM(CASE
                    WHEN lk.approval_revoked_at IS NULL
                     AND COALESCE(lk.approval_status, 'pending') = 'pending' THEN 1
                    ELSE 0
                END) AS pending,
                SUM(CASE
                    WHEN lk.approval_revoked_at IS NULL
                     AND lk.approval_status = 'approved' THEN 1
                    ELSE 0
                END) AS approved,
                SUM(CASE
                    WHEN lk.approval_revoked_at IS NULL
                     AND lk.approval_status = 'rejected' THEN 1
                    ELSE 0
                END) AS rejected
            FROM laporan_kegiatan lk
            JOIN laporan_harian lh ON lh.id = lk.laporan_id
            JOIN user u ON u.id = lh.user_id
            WHERE lk.deleted_at IS NULL
              AND u.deleted_at IS NULL
        ");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'pending'  => (int) ($row['pending'] ?? 0),
            'approved' => (int) ($row['approved'] ?? 0),
            'rejected' => (int) ($row['rejected'] ?? 0),
            'revoked'  => (int) ($row['revoked'] ?? 0),
        ];
    }

    public function countKirimHariIni(?string $tanggal = null): int
    {
        if ($tanggal === null) {
            $tanggal = (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d');
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT lh.user_id)
            FROM {$this->table} lh
            JOIN user u ON u.id = lh.user_id
            WHERE lh.tanggal = :tanggal
              AND u.deleted_at IS NULL
              AND EXISTS (
                  SELECT 1
                  FROM laporan_kegiatan lk
                  WHERE lk.laporan_id = lh.id
                    AND lk.deleted_at IS NULL
              )
        ");
        $stmt->execute([':tanggal' => $tanggal]);
        return (int) $stmt->fetchColumn();
    }

    public function deleteBulk(array $kegiatanIds): int
    {
        $kegiatanIds = array_values(array_unique(array_filter(array_map('intval', $kegiatanIds))));

        if (!$kegiatanIds) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($kegiatanIds), '?'));

        $stmt = $this->db->prepare("
            DELETE FROM laporan_kegiatan
            WHERE id IN ({$placeholders})
        ");

        $stmt->execute($kegiatanIds);

        return $stmt->rowCount();
    }

    private function generateUniqueVerificationToken(): string
    {
        do {
            $token = bin2hex(random_bytes(32));

            $stmt = $this->db->prepare("
                SELECT 1
                FROM laporan_kegiatan
                WHERE verification_token = :token
                LIMIT 1
            ");
            $stmt->execute([':token' => $token]);
        } while ($stmt->fetchColumn());

        return $token;
    }

    public function buildDocumentHash(int $kegiatanId, string $approvedAt, int $adminId): string
    {
        $data = $this->buildDocumentData($kegiatanId, $approvedAt, $adminId);

        return hash('sha256', json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
    }

    public function buildDocumentData(int $kegiatanId, string $approvedAt, int $adminId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                lk.id AS kegiatan_id,
                lk.kegiatan,
                lk.output,
                lk.bukti,
                lh.id AS laporan_id,
                lh.tanggal,
                lh.user_id AS pegawai_id,
                u.nip,
                u.nik,
                u.nama AS nama_pegawai
            FROM laporan_kegiatan lk
            JOIN {$this->table} lh ON lh.id = lk.laporan_id
            JOIN user u ON u.id = lh.user_id
            WHERE lk.id = :id
              AND lk.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':id' => $kegiatanId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new Exception('Kegiatan laporan tidak ditemukan.');
        }

        return [
            'kegiatan_id'      => (int) $row['kegiatan_id'],
            'laporan_id'       => (int) $row['laporan_id'],
            'pegawai_id'       => (int) $row['pegawai_id'],
            'nip'              => (string) ($row['nip'] ?? ''),
            'nik'              => (string) ($row['nik'] ?? ''),
            'nama_pegawai'     => (string) ($row['nama_pegawai'] ?? ''),
            'tanggal'          => (string) $row['tanggal'],
            'kegiatan'         => (string) $row['kegiatan'],
            'output'           => (string) $row['output'],
            'bukti'            => (string) ($row['bukti'] ?? ''),
            'approved_at'      => $approvedAt,
            'approved_by'      => $adminId,
        ];
    }

    public function findVerificationResultByToken(string $token): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                lk.id AS kegiatan_id,
                lk.kegiatan,
                lk.output,
                lk.bukti,
                lk.approval_status,
                lk.approved_by,
                lk.approved_at,
                lk.verification_token,
                lk.document_hash,
                lk.approval_revoked_at,
                lk.deleted_at,
                lh.id AS laporan_id,
                lh.tanggal,
                u.nama AS nama_pegawai,
                u.nip,
                u.nik,
                admin.nama AS nama_admin
            FROM laporan_kegiatan lk
            JOIN {$this->table} lh ON lh.id = lk.laporan_id
            JOIN user u ON u.id = lh.user_id
            LEFT JOIN user admin ON admin.id = lk.approved_by
            WHERE lk.verification_token = :token
              AND lk.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':token' => $token]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ?: null;
    }

    public function findVerificationResultByCode(string $code): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                lk.id AS kegiatan_id,
                lk.kegiatan,
                lk.output,
                lk.bukti,
                lk.approval_status,
                lk.approved_by,
                lk.approved_at,
                lk.verification_token,
                lk.document_hash,
                lk.approval_revoked_at,
                lk.deleted_at,
                lh.id AS laporan_id,
                lh.tanggal,
                u.nama AS nama_pegawai,
                u.nip,
                u.nik,
                admin.nama AS nama_admin
            FROM laporan_kegiatan lk
            JOIN {$this->table} lh ON lh.id = lk.laporan_id
            JOIN user u ON u.id = lh.user_id
            LEFT JOIN user admin ON admin.id = lk.approved_by
            WHERE lk.verification_token LIKE CONCAT(:code, '%')
              AND lk.deleted_at IS NULL
            LIMIT 2
        ");
        $stmt->execute([':code' => strtolower($code)]);

        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return count($matches) === 1 ? $matches[0] : null;
    }
    public function recalculateKegiatanCount(int $laporanId): int
    {
        $stmt = $this->db->prepare("
        SELECT COUNT(*) FROM laporan_kegiatan
        WHERE laporan_id = :id
          AND deleted_at IS NULL
    ");
        $stmt->execute(['id' => $laporanId]);

        $count = (int) $stmt->fetchColumn();

        $update = $this->db->prepare("
        UPDATE laporan_harian
        SET kegiatan_count = :count
        WHERE id = :id
    ");
        $update->execute([
            'count' => $count,
            'id'    => $laporanId
        ]);

        return $count;
    }

    public function deleteIfEmpty(int $laporanId): void
    {
        $stmt = $this->db->prepare("
        DELETE FROM laporan_harian
        WHERE id = :id AND kegiatan_count = 0
    ");

        $stmt->execute(['id' => $laporanId]);
    }

    public function hasSubmittedToday(int $userId): bool
    {
        $tanggal = (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d');
        $stmt = $this->db->prepare("
            SELECT 1
            FROM {$this->table} lh
            JOIN laporan_kegiatan lk ON lk.laporan_id = lh.id
            WHERE lh.user_id = :user_id
              AND lh.tanggal = :tanggal
              AND lk.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':tanggal' => $tanggal,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function countAll(): int
    {
        $stmt = $this->db->prepare("
        SELECT COUNT(DISTINCT lh.id)
        FROM {$this->table} lh
        JOIN laporan_kegiatan lk ON lk.laporan_id = lh.id
        JOIN user u ON u.id = lh.user_id
        WHERE lk.deleted_at IS NULL
          AND u.deleted_at IS NULL
    ");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function countBelumKirimHariIni(?string $tanggal = null): int
    {
        if ($tanggal === null) {
            $tanggal = (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d');
        }

        $stmt = $this->db->prepare("
        SELECT COUNT(*)
        FROM user u
        WHERE u.role = 'pegawai'
        AND u.deleted_at IS NULL
        AND u.id NOT IN (
            SELECT lh.user_id
            FROM {$this->table} lh
            WHERE lh.tanggal = :tanggal
              AND EXISTS (
                  SELECT 1
                  FROM laporan_kegiatan lk
                  WHERE lk.laporan_id = lh.id
                    AND lk.deleted_at IS NULL
              )
        )
    ");
        $stmt->execute([':tanggal' => $tanggal]);
        return (int) $stmt->fetchColumn();
    }

    public function chart30Hari(): array
    {
        $stmt = $this->db->prepare("
        SELECT 
            DATE(lh.tanggal) AS tgl,
            COUNT(DISTINCT lh.id) AS total
        FROM {$this->table} lh
        JOIN laporan_kegiatan lk ON lk.laporan_id = lh.id
        JOIN user u ON u.id = lh.user_id
        WHERE lh.tanggal >= CURDATE() - INTERVAL 29 DAY
          AND lk.deleted_at IS NULL
          AND u.deleted_at IS NULL
        GROUP BY DATE(lh.tanggal)
        ORDER BY tgl ASC
    ");
        $stmt->execute();

        $labels = [];
        $data   = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $labels[] = date('d M', strtotime($row['tgl']));
            $data[]   = (int) $row['total'];
        }

        return [
            'labels' => $labels,
            'data'   => $data
        ];
    }

    public function latest(int $limit = 5): array
    {
        $stmt = $this->db->prepare("
        SELECT 
            u.nama,
            u.foto,
            lh.tanggal,
            lh.created_at,
            lk.id AS kegiatan_id,
            lk.kegiatan,
            COALESCE(lk.approval_status, 'pending') AS status,
            lk.approval_revoked_at
        FROM laporan_kegiatan lk
        JOIN {$this->table} lh ON lh.id = lk.laporan_id
        JOIN user u ON u.id = lh.user_id
        WHERE u.role = 'pegawai'
          AND u.deleted_at IS NULL
          AND lk.deleted_at IS NULL
        ORDER BY lh.created_at DESC, lk.id DESC
        LIMIT :limit
    ");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function latestPendingKegiatan(int $limit = 5): array
    {
        $stmt = $this->db->prepare("
        SELECT
            u.nama,
            u.foto,
            lh.tanggal,
            lh.created_at,
            lk.id AS kegiatan_id,
            lk.kegiatan
        FROM laporan_kegiatan lk
        JOIN {$this->table} lh ON lh.id = lk.laporan_id
        JOIN user u ON u.id = lh.user_id
        WHERE u.role = 'pegawai'
          AND u.deleted_at IS NULL
          AND lk.approval_revoked_at IS NULL
          AND COALESCE(lk.approval_status, 'pending') = 'pending'
          AND lk.deleted_at IS NULL
        ORDER BY lh.created_at DESC, lk.id DESC
        LIMIT :limit
    ");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function belumKirimHariIni(int $limit = 5, ?string $tanggal = null): array
    {
        if ($tanggal === null) {
            $tanggal = (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d');
        }

        $stmt = $this->db->prepare("
        SELECT u.id, u.nama, u.jabatan
        FROM user u
        WHERE u.role = 'pegawai'
          AND u.deleted_at IS NULL
          AND NOT EXISTS (
              SELECT 1
              FROM laporan_harian today_lh
              JOIN laporan_kegiatan lk ON lk.laporan_id = today_lh.id
              WHERE today_lh.user_id = u.id
                AND today_lh.tanggal = :tanggal
                AND lk.deleted_at IS NULL
          )
        ORDER BY u.nama ASC
        LIMIT :limit
    ");

        $stmt->bindValue(':tanggal', $tanggal);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
