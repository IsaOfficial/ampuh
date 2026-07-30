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
        $this->laporanService->purgeExpiredDeletedKegiatan();

        $laporan = $this->laporanQuery
            ->getLaporanByPegawai($pegawai['id']);

        view('pegawai/laporan', [
            'title'   => 'Riwayat Laporan',
            'laporan' => $laporan
        ]);
    }

    public function sampahLaporan(): void
    {
        $pegawai = $this->authService->pegawai();
        $this->laporanService->purgeExpiredDeletedKegiatan();

        $filter = [
            'start' => trim($_GET['start'] ?? '') ?: null,
            'end' => trim($_GET['end'] ?? '') ?: null,
        ];

        $laporan = $this->laporanQuery->getDeletedLaporanByPegawai(
            (int) $pegawai['id'],
            $filter['start'],
            $filter['end']
        );

        view('pegawai/sampah', [
            'title' => 'Sampah Laporan',
            'laporan' => $laporan,
            'filter' => $filter,
        ]);
    }



    private function rememberCreateInput(array $r): void
    {
        Session::flash('old_pegawai_laporan_create', [
            'tanggal' => (string)($r['tanggal'] ?? ''),
            'kegiatan' => array_values(array_map('strval', (array)($r['kegiatan'] ?? []))),
            'output' => array_values(array_map('strval', (array)($r['output'] ?? []))),
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
            $this->rememberCreateInput($r);

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
                'message' => 'Laporan berhasil dihapus dan masih dapat dipulihkan selama 14 hari.'
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

    public function restore(array $r): void
    {
        try {
            $pegawai = $this->authService->pegawai();

            if (empty($r['id'])) {
                throw new Exception("ID kegiatan tidak valid.");
            }

            $this->laporanService->restoreKegiatanByPegawai(
                (int) $pegawai['id'],
                (int) $r['id']
            );

            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Laporan berhasil dipulihkan.'
            ]);
        } catch (Exception $e) {
            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header("Location: /pegawai/laporan/sampah");
        exit;
    }

    public function bulkProcess(array $r): void
    {
        try {
            $pegawai = $this->authService->pegawai();
            $action = trim((string) ($r['action'] ?? ''));
            $kegiatanIds = array_values(array_unique(array_filter(array_map('intval', (array) ($r['kegiatan_ids'] ?? [])))));

            if (!$kegiatanIds) {
                throw new Exception("Pilih minimal satu kegiatan.");
            }

            $processed = 0;
            foreach ($kegiatanIds as $kegiatanId) {
                match ($action) {
                    'delete' => $this->laporanService->deleteKegiatanByPegawai((int) $pegawai['id'], $kegiatanId),
                    'restore' => $this->laporanService->restoreKegiatanByPegawai((int) $pegawai['id'], $kegiatanId),
                    default => throw new Exception("Aksi bulk tidak valid."),
                };
                $processed++;
            }

            Session::flash('flash', [
                'type' => 'success',
                'message' => "{$processed} kegiatan berhasil diproses."
            ]);
        } catch (Exception $e) {
            Session::flash('flash', [
                'type' => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        $redirect = ($r['action'] ?? '') === 'restore'
            ? '/pegawai/laporan/sampah'
            : '/pegawai/laporan';

        header("Location: {$redirect}");
        exit;
    }
}
