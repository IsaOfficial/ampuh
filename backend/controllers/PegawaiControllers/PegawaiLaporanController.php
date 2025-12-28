<?php

class PegawaiLaporanController
{
    private AuthService $authService;
    private LaporanService $laporanService;

    public function __construct()
    {
        $this->authService = new AuthService(
            new PegawaiModel(),
            new AdminModel()
        );

        $this->laporanService = new LaporanService(
            new LaporanHarianModel(),
            new LaporanKegiatanModel(),
            new DocumentUploadService(),
        );
    }

    public function create(array $r): void
    {
        try {
            $pegawai = $this->authService->pegawai();
            $kegiatan = $r['kegiatan'] ?? [];
            $output   = $r['output'] ?? [];
            $files    = $r['bukti'] ?? [];

            $this->laporanService->create(
                $pegawai['id'],
                $r['tanggal'],
                $kegiatan,
                $output,
                $files
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
            if (empty($r['id'])) {
                throw new Exception("ID laporan tidak valid.");
            }

            $pegawai = $this->authService->pegawai();

            $this->laporanService->update(
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
            if (empty($r['id'])) {
                throw new Exception("ID laporan tidak valid.");
            }

            $pegawai = $this->authService->pegawai();

            $this->laporanService->delete((int) $r['id'], $pegawai['id']);

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
