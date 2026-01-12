<?php

class PegawaiLaporanController
{
    private LaporanQueryModel $laporanQuery;
    private AuthService $authService;
    private LaporanService $laporanService;

    public function __construct()
    {
        $db = Database::getConnection();

        $this->laporanQuery = new LaporanQueryModel(
            $db
        );

        $this->authService = new AuthService(
            new PegawaiModel(),
            new AdminModel()
        );

        $this->laporanService = new LaporanService(
            new LaporanHarianModel($db),
            new LaporanKegiatanModel($db),
            new DocumentUploadService(),
        );
    }

    public function riwayatLaporan(): void
    {
        $pegawai = $this->authService->pegawai();

        $laporan = $this->laporanQuery
            ->getLaporanByPegawai($pegawai['id']);

        view('pegawai/laporan', [
            'title'   => 'Riwayat Laporan',
            'laporan' => $laporan
        ]);
    }


    public function create(array $r): void
    {
        try {
            $pegawai = $this->authService->pegawai();

            $this->laporanService->createKegiatan(
                $pegawai['id'],
                $r['tanggal'],
                $r['kegiatan'] ?? [],
                $r['output'] ?? [],
                $r['bukti'] ?? []
            );

            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Laporan berhasil dikirim.'
            ]);
        } catch (Exception $e) {
            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header("Location: /pegawai/dashboard");
        exit;
    }

    public function update(array $r): void
    {
        try {
            $pegawai = $this->authService->pegawai();

            if (empty($r['id'])) {
                throw new Exception("ID kegiatan tidak valid.");
            }

            $this->laporanService->updateKegiatanByPegawai(
                $pegawai['id'],
                (int)$r['id'],
                $r['kegiatan'],
                $r['output'],
                $r['bukti'] ?? []
            );

            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Laporan berhasil diperbarui.'
            ]);
        } catch (Exception $e) {
            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header("Location: /pegawai/laporan");
        exit;
    }

    public function delete(array $r): void
    {
        try {
            $pegawai = $this->authService->pegawai();

            if (empty($r['id'])) {
                throw new Exception("ID kegiatan tidak valid.");
            }

            $this->laporanService->deleteKegiatanByPegawai(
                $pegawai['id'],
                (int)$r['id']
            );

            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Laporan berhasil dihapus.'
            ]);
        } catch (Exception $e) {
            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header("Location: /pegawai/laporan");
        exit;
    }
}
