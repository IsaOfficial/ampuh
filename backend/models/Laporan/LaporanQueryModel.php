<?php

class LaporanQueryModel
{
    public function __construct(
        private PDO $db
    ) {}

    /**
     * Digunakan AdminLaporanController
     * Menampilkan seluruh laporan (grouped per hari & pegawai)
     */
    public function getLaporanByAdmin(
        ?int $pegawaiId = null,
        ?string $start = null,
        ?string $end = null
    ): array {
        $sql = "
            SELECT
                lh.id        AS laporan_id,
                lh.tanggal,
                lh.kegiatan_count,

                u.id         AS pegawai_id,
                u.nama       AS nama_pegawai,
                u.nip,
                u.nik,
                u.jabatan,
                u.foto       AS foto_pegawai,

                lk.id        AS kegiatan_id,
                lk.kegiatan,
                lk.output,
                lk.bukti
            FROM laporan_harian lh
            JOIN user u
                ON u.id = lh.user_id
            LEFT JOIN laporan_kegiatan lk
                ON lk.laporan_id = lh.id
        ";

        $conditions = [];
        $params     = [];

        if ($pegawaiId !== null) {
            $conditions[] = 'u.id = :pegawai_id';
            $params['pegawai_id'] = $pegawaiId;
        }

        if ($start !== null && $end !== null) {
            $conditions[] = 'lh.tanggal BETWEEN :start AND :end';
            $params['start'] = $start;
            $params['end']   = $end;
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY lh.tanggal DESC, lk.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Digunakan PegawaiController
     * Riwayat laporan milik pegawai tertentu
     */
    public function getLaporanByPegawai(
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
            lk.bukti
        FROM laporan_harian lh
        LEFT JOIN laporan_kegiatan lk
            ON lk.laporan_id = lh.id
        WHERE lh.user_id = :pegawai_id
    ";

        $params = [
            'pegawai_id' => $pegawaiId
        ];

        // 🔑 FILTER TANGGAL OPSIONAL
        if ($start !== null && $end !== null) {
            $sql .= " AND lh.tanggal BETWEEN :start AND :end";
            $params['start'] = $start;
            $params['end']   = $end;
        }

        $sql .= " ORDER BY lh.tanggal DESC, lk.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Optional – detail satu laporan (admin / pegawai)
     */
    public function getDetailLaporan(int $laporanId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                lh.id        AS laporan_id,
                lh.tanggal,

                p.id         AS pegawai_id,
                p.nama       AS nama_pegawai,

                lk.id        AS kegiatan_id,
                lk.kegiatan,
                lk.output,
                lk.bukti
            FROM laporan_harian lh
            JOIN pegawai p
                ON p.id = lh.pegawai_id
            LEFT JOIN laporan_kegiatan lk
                ON lk.laporan_id = lh.id
            WHERE lh.id = :id
        ");

        $stmt->execute([
            ':id' => $laporanId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
