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

    public function approveBulk(array $laporanIds, int $adminId, string $adminName, ?string $signatureNote = null): int
    {
        $laporanIds = array_values(array_unique(array_filter(array_map('intval', $laporanIds))));

        if (!$laporanIds) {
            return 0;
        }

        $updated = 0;

        foreach ($laporanIds as $laporanId) {
            $updated += $this->approveOnce($laporanId, $adminId, $signatureNote);
        }

        return $updated;
    }

    private function approveOnce(int $laporanId, int $adminId, ?string $signatureNote = null): int
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                SELECT *
                FROM {$this->table}
                WHERE id = :id
                FOR UPDATE
            ");
            $stmt->execute([':id' => $laporanId]);

            $laporan = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$laporan) {
                $this->db->rollBack();
                return 0;
            }

            if (($laporan['approval_status'] ?? 'pending') === 'approved' && !empty($laporan['verification_token'])) {
                $this->db->commit();
                return 0;
            }

            $approvedAt = date('Y-m-d H:i:s');
            $token = $this->generateUniqueVerificationToken();
            $documentHash = $this->buildDocumentHash($laporanId, $approvedAt, $adminId);

            $update = $this->db->prepare("
                UPDATE {$this->table}
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
                ':id'                 => $laporanId,
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

    public function rejectBulk(array $laporanIds, ?string $rejectionNote = null): int
    {
        $laporanIds = array_values(array_unique(array_filter(array_map('intval', $laporanIds))));

        if (!$laporanIds) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($laporanIds), '?'));

        $stmt = $this->db->prepare("
            UPDATE {$this->table}
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

        $stmt->execute(array_merge([$rejectionNote], $laporanIds));

        return $stmt->rowCount();
    }

    public function revokeApproval(int $laporanId): void
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET
                approval_status = 'pending',
                approval_revoked_at = NOW()
            WHERE id = :id AND approval_status = 'approved'
        ");

        $stmt->execute([':id' => $laporanId]);
    }

    public function markPending(int $laporanId): void
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET
                approval_status = 'pending',
                approved_by = NULL,
                approved_at = NULL,
                verification_token = NULL,
                document_hash = NULL,
                approval_revoked_at = NULL,
                signature_note = NULL,
                rejection_note = NULL
            WHERE id = :id AND approval_status <> 'approved'
        ");

        $stmt->execute([':id' => $laporanId]);
    }

    public function countByApprovalStatus(): array
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(approval_status, 'pending') AS status, COUNT(*) AS total
            FROM {$this->table}
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
    public function deleteBulk(array $laporanIds): int
    {
        $laporanIds = array_values(array_unique(array_filter(array_map('intval', $laporanIds))));

        if (!$laporanIds) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($laporanIds), '?'));

        $stmt = $this->db->prepare("
            DELETE FROM {$this->table}
            WHERE id IN ({$placeholders})
        ");

        $stmt->execute(array_merge([$rejectionNote], $laporanIds));

        return $stmt->rowCount();
    }

    private function generateUniqueVerificationToken(): string
    {
        do {
            $token = bin2hex(random_bytes(32));

            $stmt = $this->db->prepare("
                SELECT 1
                FROM {$this->table}
                WHERE verification_token = :token
                LIMIT 1
            ");
            $stmt->execute([':token' => $token]);
        } while ($stmt->fetchColumn());

        return $token;
    }

    public function buildDocumentHash(int $laporanId, string $approvedAt, int $adminId): string
    {
        $data = $this->buildDocumentData($laporanId, $approvedAt, $adminId);

        return hash('sha256', json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
    }

    public function buildDocumentData(int $laporanId, string $approvedAt, int $adminId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                lh.id AS laporan_id,
                lh.tanggal,
                lh.user_id AS pegawai_id,
                u.nip,
                u.nik,
                u.nama AS nama_pegawai
            FROM {$this->table} lh
            JOIN user u ON u.id = lh.user_id
            WHERE lh.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $laporanId]);

        $laporan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$laporan) {
            throw new Exception('Laporan tidak ditemukan.');
        }

        $kegiatanStmt = $this->db->prepare("
            SELECT id, kegiatan, output
            FROM laporan_kegiatan
            WHERE laporan_id = :laporan_id
            ORDER BY id ASC
        ");
        $kegiatanStmt->execute([':laporan_id' => $laporanId]);

        $kegiatan = [];
        foreach ($kegiatanStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $kegiatan[] = [
                'id'       => (int) $row['id'],
                'tanggal'  => $laporan['tanggal'],
                'kegiatan' => (string) $row['kegiatan'],
                'output'   => (string) $row['output'],
            ];
        }

        return [
            'laporan_id'       => (int) $laporan['laporan_id'],
            'pegawai_id'       => (int) $laporan['pegawai_id'],
            'nip'              => (string) ($laporan['nip'] ?? ''),
            'nik'              => (string) ($laporan['nik'] ?? ''),
            'tanggal'          => (string) $laporan['tanggal'],
            'kegiatan'         => $kegiatan,
            'approved_at'      => $approvedAt,
            'approved_by'      => $adminId,
        ];
    }

    public function findVerificationResultByToken(string $token): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                lh.id AS laporan_id,
                lh.tanggal,
                lh.approval_status,
                lh.approved_by,
                lh.approved_at,
                lh.verification_token,
                lh.document_hash,
                lh.approval_revoked_at,
                u.nama AS nama_pegawai,
                u.nip,
                u.nik,
                admin.nama AS nama_admin
            FROM {$this->table} lh
            JOIN user u ON u.id = lh.user_id
            LEFT JOIN user admin ON admin.id = lh.approved_by
            WHERE lh.verification_token = :token
            LIMIT 1
        ");
        $stmt->execute([':token' => $token]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ?: null;
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