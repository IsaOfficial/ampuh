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

    /**
     * Digunakan jika suatu hari laporan harian perlu dihapus
     * (misalnya cascading manual)
     */
    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("
            DELETE FROM {$this->table}
            WHERE id = :id
        ");

        $stmt->execute([':id' => $id]);
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
