<?php

class AdminLaporanController
{
    private PegawaiModel $pegawaiModel;
    private LaporanQueryModel $laporanQuery;
    private AuthService $authService;
    private LaporanService $laporanService;

    public function __construct()
    {
        $db = Database::getConnection();

        $this->pegawaiModel = new PegawaiModel();

        $this->laporanQuery = new LaporanQueryModel(
            $db
        );

        $this->authService = new AuthService(
            $this->pegawaiModel,
            new AdminModel()
        );

        $this->laporanService = new LaporanService(
            new LaporanHarianModel($db),
            new LaporanKegiatanModel($db),
            new DocumentUploadService()
        );
    }

    private function authorize(): void
    {
        $this->authService->requireAdmin();
    }

    public function kelolaLaporan(): void
    {
        $this->authorize();

        $pegawaiList = $this->pegawaiModel->getAllPegawai();
        $laporan     = $this->laporanQuery->getLaporanByAdmin();

        view('admin/kelola_laporan', [
            'title'        => 'Kelola Laporan',
            'laporan'      => $laporan,
            'pegawai_list' => $pegawaiList
        ]);
    }

    public function create(array $r): void
    {
        $this->authorize();

        $pegawaiId = (int) ($r['pegawai_id'] ?? 0);
        $tanggal   = $r['tanggal']  ?? '';
        $kegiatan  = $r['kegiatan'] ?? [];
        $output    = $r['output']   ?? [];
        $files     = $r['bukti'] ?? [];

        try {
            $this->laporanService->createKegiatan(
                $pegawaiId,
                $tanggal,
                $kegiatan,
                $output,
                $files
            );

            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Laporan berhasil ditambahkan.'
            ]);
        } catch (Exception $e) {
            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header('Location: /admin/kelola/laporan');
        exit;
    }

    public function update(array $r): void
    {
        $this->authorize();

        try {
            if (empty($r['id'])) {
                throw new Exception("ID kegiatan tidak valid.");
            }

            $this->laporanService->updateKegiatanByAdmin(
                (int)$r['id'],
                $r['kegiatan'],
                $r['output'],
                $r['bukti'] ?? []
            );

            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Laporan berhasil diubah.'
            ]);
        } catch (Exception $e) {
            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header('Location: /admin/kelola/laporan');
        exit;
    }

    public function delete(array $r): void
    {
        $this->authorize();

        try {
            if (empty($r['id'])) {
                throw new Exception("ID kegiatan tidak valid.");
            }

            $this->laporanService->deleteKegiatanByAdmin(
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

        header('Location: /admin/kelola/laporan');
        exit;
    }
}
