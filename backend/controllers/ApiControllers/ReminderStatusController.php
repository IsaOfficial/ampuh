<?php

class ReminderStatusController
{
    private ReminderTokenModel $reminderToken;
    private LaporanHarianModel $laporanHarian;

    public function __construct()
    {
        $db = Database::getConnection();
        $this->reminderToken = new ReminderTokenModel();
        $this->laporanHarian = new LaporanHarianModel($db);
    }

    public function todayStatus(array $request = []): void
    {
        $token = trim((string) ($request['token'] ?? ''));
        if ($token === '') {
            $this->json([
                'authenticated' => false,
                'sudah_lapor' => true,
                'nama' => '',
            ], 401);
            return;
        }

        $pegawai = $this->reminderToken->findPegawaiByToken($token);
        if (!$pegawai) {
            $this->json([
                'authenticated' => false,
                'sudah_lapor' => true,
                'nama' => '',
            ], 401);
            return;
        }

        $this->reminderToken->touch($token);

        $tanggal = (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d');
        $this->json([
            'authenticated' => true,
            'role' => 'pegawai',
            'tanggal' => $tanggal,
            'sudah_lapor' => $this->laporanHarian->hasSubmittedToday((int) $pegawai['id']),
            'nama' => $pegawai['nama'] ?? '',
        ]);
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
