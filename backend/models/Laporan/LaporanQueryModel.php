<?php

class LaporanQueryModel
{
    public function __construct(
        private PDO $db
    ) {}

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
                admin.nama AS signed_name,

                u.id         AS pegawai_id,
                u.nama       AS nama_pegawai,
                u.nip,
                u.nik,
                u.jabatan,
                u.foto       AS foto_pegawai
            FROM laporan_kegiatan lk
            JOIN laporan_harian lh
                ON lh.id = lk.laporan_id
            JOIN user u
                ON u.id = lh.user_id
            LEFT JOIN user admin
                ON admin.id = lk.approved_by
        ";

        $conditions = [];
        $params     = [];

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

        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $conditions[] = "COALESCE(lk.approval_status, 'pending') = :status";
            $params['status'] = $status;
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY lh.tanggal DESC, lk.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

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
            admin.nama AS signed_name
        FROM laporan_kegiatan lk
        JOIN laporan_harian lh
            ON lh.id = lk.laporan_id
        LEFT JOIN user admin
            ON admin.id = lk.approved_by
        WHERE lh.user_id = :pegawai_id
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
        ");

        $stmt->execute([
            ':id' => $laporanId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
