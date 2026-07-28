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
            WHERE id = :id AND approval_status = 'approved'
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
            WHERE laporan_id = :id AND approval_status <> 'approved'
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
            WHERE id = :id AND approval_status <> 'approved'
        ");

        $stmt->execute([':id' => $kegiatanId]);
    }
    public function countByApprovalStatus(): array
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(approval_status, 'pending') AS status, COUNT(*) AS total
            FROM laporan_kegiatan
            GROUP BY COALESCE(approval_status, 'pending')
        ");
        $stmt->execute();

        $counts = [
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
        ];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $status = $row['status'] ?? 'pending';
            if (array_key_exists($status, $counts)) {
                $counts[$status] = (int) $row['total'];
            }
        }

        return $counts;
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
        return (bool) $this->findByUserAndDate(
            $userId,
            date('Y-m-d')
        );
    }

    public function countAll(): int
    {
        $stmt = $this->db->prepare("
        SELECT COUNT(*) 
        FROM {$this->table}
    ");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function countBelumKirimHariIni(): int
    {
        $stmt = $this->db->prepare("
        SELECT COUNT(*)
        FROM user u
        WHERE u.role = 'pegawai'
        AND u.id NOT IN (
            SELECT user_id
            FROM {$this->table}
            WHERE tanggal = CURDATE()
        )
    ");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function chart30Hari(): array
    {
        $stmt = $this->db->prepare("
        SELECT 
            DATE(tanggal) AS tgl,
            COUNT(*) AS total
        FROM {$this->table}
        WHERE tanggal >= CURDATE() - INTERVAL 29 DAY
        GROUP BY DATE(tanggal)
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
            l.created_at
        FROM {$this->table} l
        JOIN user u ON u.id = l.user_id
        WHERE u.role = 'pegawai'
        ORDER BY l.created_at DESC
        LIMIT :limit
    ");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function belumKirimHariIni(int $limit = 5): array
    {
        $stmt = $this->db->prepare("
        SELECT u.id, u.nama, u.jabatan
        FROM user u
        LEFT JOIN laporan_harian lh
            ON lh.user_id = u.id
           AND lh.tanggal = CURDATE()
        WHERE u.role = 'pegawai'
          AND lh.id IS NULL
        ORDER BY u.nama ASC
        LIMIT :limit
    ");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
