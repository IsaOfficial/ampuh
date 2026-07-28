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
                COALESCE(lh.approval_status, 'pending') AS status,
                lh.approved_by,
                lh.approved_at,
                lh.verification_token,
                lh.document_hash,
                lh.approval_revoked_at,
                lh.signature_note,
                lh.rejection_note,
                admin.nama AS signed_name,

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
            LEFT JOIN user admin
                ON admin.id = lh.approved_by
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

        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $conditions[] = "COALESCE(lh.approval_status, 'pending') = :status";
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
            COALESCE(lh.approval_status, 'pending') AS status,
            lh.approved_by,
            lh.approved_at,
            lh.verification_token,
            lh.document_hash,
            lh.approval_revoked_at,
            lh.signature_note,
            lh.rejection_note,
            admin.nama AS signed_name,

            lk.id        AS kegiatan_id,
            lk.kegiatan,
            lk.output,
            lk.bukti
        FROM laporan_harian lh
        LEFT JOIN user admin
            ON admin.id = lh.approved_by
        LEFT JOIN laporan_kegiatan lk
            ON lk.laporan_id = lh.id
        WHERE lh.user_id = :pegawai_id
    ";

        $params = [
            'pegawai_id' => $pegawaiId
        ];

        if ($approvedOnly) {
            $sql .= " AND lh.approval_status = 'approved' AND lh.approval_revoked_at IS NULL";
        }

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

    public function getDetailLaporan(int $laporanId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                lh.id        AS laporan_id,
                lh.tanggal,
                COALESCE(lh.approval_status, 'pending') AS status,
                lh.approved_by,
                lh.approved_at,
                lh.verification_token,
                lh.document_hash,
                lh.approval_revoked_at,
                lh.signature_note,
                lh.rejection_note,
                admin.nama AS signed_name,

                u.id         AS pegawai_id,
                u.nama       AS nama_pegawai,

                lk.id        AS kegiatan_id,
                lk.kegiatan,
                lk.output,
                lk.bukti
            FROM laporan_harian lh
            JOIN user u
                ON u.id = lh.user_id
            LEFT JOIN user admin
                ON admin.id = lh.approved_by
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
