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

        $token = strtolower(preg_replace('/\s+/', '', trim($request['token'] ?? '')));
        $code = strtolower(preg_replace('/\s+/', '', trim($request['kode'] ?? '')));
        $verificationInput = $token !== '' ? $token : $code;
        $result = [
            'status' => 'invalid',
            'title' => 'Dokumen Tidak Valid',
            'message' => 'Kode verifikasi tidak terdaftar atau dokumen tidak ditemukan.',
            'data' => null,
            'query' => $verificationInput,
        ];

        if ($verificationInput === '') {
            view('verification/laporan', [
                'status' => 'neutral',
                'title' => 'Verifikasi Dokumen',
                'message' => 'Masukkan kode verifikasi yang tercetak pada dokumen atau pindai QR Code laporan.',
                'data' => null,
                'query' => '',
            ]);
            return;
        }

        if (!preg_match('/\A[a-f0-9]{12,64}\z/i', $verificationInput)) {
            view('verification/laporan', [
                'status' => 'invalid',
                'title' => 'Format Kode Tidak Valid',
                'message' => 'Kode verifikasi harus berupa 12 sampai 64 karakter heksadesimal.',
                'data' => null,
                'query' => $verificationInput,
            ]);
            return;
        }

        $laporan = strlen($verificationInput) === 64
            ? $this->laporanHarian->findVerificationResultByToken($verificationInput)
            : $this->laporanHarian->findVerificationResultByCode($verificationInput);

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
                'query' => $verificationInput,
            ]);
            return;
        }

        $currentHash = $this->laporanHarian->buildDocumentHash(
            (int) $laporan['kegiatan_id'],
            (string) $laporan['approved_at'],
            (int) $laporan['approved_by']
        );

        if (!hash_equals((string) $laporan['document_hash'], $currentHash)) {
            view('verification/laporan', [
                'status' => 'tampered',
                'title' => 'Integritas Dokumen Tidak Valid',
                'message' => 'Data laporan telah berubah setelah proses pengesahan.',
                'data' => $laporan,
                'query' => $verificationInput,
            ]);
            return;
        }

        view('verification/laporan', [
            'status' => 'valid',
            'title' => 'Dokumen Valid',
            'message' => 'Dokumen tercatat dan terverifikasi pada sistem.',
            'data' => $laporan,
            'query' => $verificationInput,
        ]);
    }
}