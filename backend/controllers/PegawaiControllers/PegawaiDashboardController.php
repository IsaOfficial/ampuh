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
}
