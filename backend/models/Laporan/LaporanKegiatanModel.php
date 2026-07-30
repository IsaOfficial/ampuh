<?php

class LaporanKegiatanModel
{
    protected string $table = 'laporan_kegiatan';

    public function __construct(
        protected PDO $db
    ) {}

    public function findById(int $id, bool $includeDeleted = false): ?array
    {
        $deletedClause = $includeDeleted ? '' : 'AND deleted_at IS NULL';

        $stmt = $this->db->prepare("
            SELECT *
            FROM {$this->table}
            WHERE id = :id
            {$deletedClause}
            LIMIT 1
        ");

        $stmt->execute([':id' => $id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ?: null;
    }

    public function getByLaporanId(int $laporanId): array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM {$this->table}
            WHERE laporan_id = :laporan_id
              AND deleted_at IS NULL
            ORDER BY id ASC
        ");

        $stmt->execute([
            ':laporan_id' => $laporanId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(
        int $laporanId,
        string $kegiatan,
        string $output,
        ?string $bukti
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table}
                (laporan_id, kegiatan, output, bukti)
            VALUES
                (:laporan_id, :kegiatan, :output, :bukti)
        ");

        $stmt->execute([
            ':laporan_id' => $laporanId,
            ':kegiatan'   => $kegiatan,
            ':output'     => $output,
            ':bukti'      => $bukti,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(
        int $id,
        string $kegiatan,
        string $output,
        ?string $bukti
    ): void {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET
                kegiatan = :kegiatan,
                output   = :output,
                bukti    = :bukti
            WHERE id = :id
              AND deleted_at IS NULL
        ");

        $stmt->execute([
            ':kegiatan' => $kegiatan,
            ':output'   => $output,
            ':bukti'    => $bukti,
            ':id'       => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("
            DELETE FROM {$this->table}
            WHERE id = :id
        ");

        $stmt->execute([':id' => $id]);
    }

    public function softDelete(int $id, int $deletedBy, ?string $reason = null): void
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET
                deleted_at = NOW(),
                deleted_by = :deleted_by,
                delete_reason = :delete_reason
            WHERE id = :id
              AND deleted_at IS NULL
        ");

        $stmt->execute([
            ':deleted_by' => $deletedBy,
            ':delete_reason' => $reason,
            ':id' => $id,
        ]);
    }

    public function restore(int $id): void
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET
                deleted_at = NULL,
                deleted_by = NULL,
                delete_reason = NULL
            WHERE id = :id
              AND deleted_at IS NOT NULL
        ");

        $stmt->execute([':id' => $id]);
    }

    public function forceDelete(int $id): void
    {
        $this->delete($id);
    }

    public function findDeletedOlderThan(int $days): array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM {$this->table}
            WHERE deleted_at IS NOT NULL
              AND deleted_at < DATE_SUB(NOW(), INTERVAL :days DAY)
        ");

        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countByLaporanId(int $laporanId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM {$this->table}
            WHERE laporan_id = :laporan_id
              AND deleted_at IS NULL
        ");

        $stmt->execute([
            ':laporan_id' => $laporanId
        ]);

        return (int) $stmt->fetchColumn();
    }

    /* =========================
 * DASHBOARD
 * ========================= */
    public function countAll(): int
    {
        $stmt = $this->db->prepare("
        SELECT COUNT(*) 
        FROM {$this->table}
        WHERE deleted_at IS NULL
    ");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
}
