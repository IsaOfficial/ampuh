<?php

class VerificationController
{
    private LaporanHarianModel $laporanHarian;

    public function __construct()
    {
        $this->laporanHarian = new LaporanHarianModel(Database::getConnection());
    }

    public function verify(array $request): void
    {
        header('Content-Type: text/html; charset=UTF-8');

        $token = trim($request['token'] ?? '');
        $result = [
            'status' => 'invalid',
            'title' => 'Dokumen Tidak Valid',
            'message' => 'Token tidak terdaftar atau dokumen tidak ditemukan.',
            'data' => null,
        ];

        if ($token === '' || !preg_match('/\A[a-f0-9]{64}\z/i', $token)) {
            view('verification/laporan', $result);
            return;
        }

        $laporan = $this->laporanHarian->findVerificationResultByToken($token);

        if (!$laporan) {
            view('verification/laporan', $result);
            return;
        }

        if (($laporan['approval_status'] ?? '') !== 'approved' || !empty($laporan['approval_revoked_at'])) {
            view('verification/laporan', [
                'status' => 'revoked',
                'title' => 'Pengesahan Tidak Berlaku',
                'message' => 'Dokumen ini belum disahkan atau pengesahannya telah dicabut.',
                'data' => $laporan,
            ]);
            return;
        }

        $currentHash = $this->laporanHarian->buildDocumentHash(
            (int) $laporan['laporan_id'],
            (string) $laporan['approved_at'],
            (int) $laporan['approved_by']
        );

        if (!hash_equals((string) $laporan['document_hash'], $currentHash)) {
            view('verification/laporan', [
                'status' => 'tampered',
                'title' => 'Integritas Dokumen Tidak Valid',
                'message' => 'Data laporan telah berubah setelah proses pengesahan.',
                'data' => $laporan,
            ]);
            return;
        }

        view('verification/laporan', [
            'status' => 'valid',
            'title' => 'Dokumen Valid',
            'message' => 'Dokumen tercatat dan terverifikasi pada sistem.',
            'data' => $laporan,
        ]);
    }
}