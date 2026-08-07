<?php

class PegawaiController
{
    private AuthService $authService;
    private LaporanHarianModel $laporanHarian;

    public function __construct()
    {
        $db = Database::getConnection();

        $this->authService = new AuthService(
            new PegawaiModel(),
            new AdminModel()
        );

        $this->laporanHarian = new LaporanHarianModel(
            $db
        );
    }

    public function dashboard(): void
    {
        $pegawai = $this->authService->pegawai();
        $sudahLapor = $this->laporanHarian->hasSubmittedToday($pegawai['id']);

        if (!$sudahLapor) {
            Session::flash('flash', [
                'type'    => 'danger',
                'message' => 'Anda belum membuat laporan hari ini!'
            ]);
        }

        view('pegawai/dashboard', [
            'title'   => 'Dashboard Pegawai',
            'pegawai' => $pegawai,
        ]);
    }

    public function todayReportStatus(): void
    {
        $pegawai = $this->authService->pegawai();
        $tanggal = (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d');
        $sudahLapor = $this->laporanHarian->hasSubmittedToday((int) $pegawai['id']);

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo json_encode([
            'authenticated' => true,
            'role' => 'pegawai',
            'tanggal' => $tanggal,
            'sudah_lapor' => $sudahLapor,
            'nama' => $pegawai['nama'] ?? '',
        ], JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
