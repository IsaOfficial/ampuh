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
        $filter = [
            'pegawai_id' => isset($_GET['pegawai_id']) && $_GET['pegawai_id'] !== '' ? (int) $_GET['pegawai_id'] : null,
            'start' => trim($_GET['start'] ?? '') ?: null,
            'end' => trim($_GET['end'] ?? '') ?: null,
            'status' => trim($_GET['status'] ?? '') ?: null,
        ];
        $laporan = $this->laporanQuery->getLaporanByAdmin(
            $filter['pegawai_id'],
            $filter['start'],
            $filter['end'],
            $filter['status']
        );

        view('admin/kelola_laporan', [
            'title'        => 'Kelola Laporan',
            'laporan'      => $laporan,
            'pegawai_list' => $pegawaiList,
            'filter'       => $filter
        ]);
    }


    private function rememberCreateInput(array $r): void
    {
        Session::flash('old_admin_laporan_create', [
            'pegawai_id' => (string)($r['pegawai_id'] ?? ''),
            'tanggal' => (string)($r['tanggal'] ?? ''),
            'kegiatan' => array_values(array_map('strval', (array)($r['kegiatan'] ?? []))),
            'output' => array_values(array_map('strval', (array)($r['output'] ?? []))),
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
                $files,
                true
            );

            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Laporan berhasil ditambahkan.'
            ]);
        } catch (Exception $e) {
            $this->rememberCreateInput($r);

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

    public function bulkProcess(array $r): void
    {
        $this->authorize();

        try {
            $admin = $this->authService->admin();
            $processed = $this->laporanService->bulkProcessByAdmin(
                $r['kegiatan_ids'] ?? $r['laporan_ids'] ?? [],
                $r['action'] ?? '',
                (int) $admin['id'],
                $admin['nama'] ?? 'Ka. TU MTs Negeri 1 Jepara',
                trim($r['signature_note'] ?? '') ?: null,
                trim($r['rejection_note'] ?? '') ?: null
            );

            Session::flash('flash', [
                'type' => 'success',
                'message' => $processed . ' kegiatan berhasil diproses.'
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
