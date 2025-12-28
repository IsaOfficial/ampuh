<?php

class PegawaiController
{
    private AuthService $authService;
    private PegawaiService $pegawaiService;
    private LaporanHarianModel $laporanHarian;
    private LaporanKegiatanModel $laporanKegiatan;

    public function __construct()
    {
        $this->authService = new AuthService(
            new PegawaiModel(),
            new AdminModel()
        );
        $this->pegawaiService = new PegawaiService(
            new PegawaiModel(),
            new ImageUploadService()
        );
        $this->laporanHarian = new LaporanHarianModel();
        $this->laporanKegiatan = new LaporanKegiatanModel();
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

    public function laporan(): void
    {
        $pegawai = $this->authService->pegawai();
        $laporan = $this->laporanKegiatan->getByUserId($pegawai['id']);

        view('pegawai/laporan', [
            'title' => 'Riwayat Laporan',
            'laporan' => $laporan
        ]);
    }

    public function profil(): void
    {
        $pegawai = $this->authService->pegawai();

        view('pegawai/profil', [
            'title'   => 'Profil Pegawai',
            'pegawai' => $pegawai,
        ]);
    }

    public function updateProfil(): void
    {
        try {
            $pegawai = $this->authService->pegawai();

            $this->pegawaiService->update($pegawai['id'], $_POST);

            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Profil berhasil diperbarui.'
            ]);
        } catch (Exception $e) {
            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header('Location: /pegawai/profil');
        exit;
    }

    public function updateFoto(): void
    {
        try {
            $pegawai = $this->authService->pegawai();

            if (
                !isset($_FILES['foto']) ||
                $_FILES['foto']['error'] !== UPLOAD_ERR_OK
            ) {
                throw new Exception("Tidak ada file yang diunggah.");
            }

            $this->pegawaiService->updateFotoProfil($pegawai['id'], $_FILES['foto']);

            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Foto profil berhasil diperbarui.'
            ]);
        } catch (Exception $e) {
            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header("Location: /pegawai/profil");
        exit;
    }
}
