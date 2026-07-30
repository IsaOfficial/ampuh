<?php

class AdminDashboardController
{
    private AuthService $authService;
    private PegawaiModel $pegawaiModel;
    private LaporanHarianModel $laporanHarianModel;
    private LaporanKegiatanModel $laporanKegiatanModel;

    public function __construct()
    {
        $this->pegawaiModel = new PegawaiModel();

        $this->laporanHarianModel = new LaporanHarianModel(
            Database::getConnection()
        );

        $this->laporanKegiatanModel = new LaporanKegiatanModel(
            Database::getConnection()
        );

        $this->authService = new AuthService(
            $this->pegawaiModel,
            new AdminModel()
        );
    }

    /**
     * Dashboard Admin
     */
    public function dashboard(): void
    {
        // =========================
        // AUTHORIZATION
        // =========================
        $admin = $this->authService->admin();
        $today = (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d');

        // =========================
        // STATISTIK UTAMA
        // =========================
        $stats = [
            'totalPegawai'         => $this->pegawaiModel->countPegawai(),
            'totalLaporanHarian'   => $this->laporanHarianModel->countAll(),
            'totalLaporanKegiatan' => $this->laporanKegiatanModel->countAll(),
            'kirimHariIni'         => $this->laporanHarianModel->countKirimHariIni($today),
            'belumKirimHariIni'    => $this->laporanHarianModel->countBelumKirimHariIni($today),
            'approvalStatus'       => $this->laporanHarianModel->countByApprovalStatus(),
            'tanggalHariIni'       => $today,
        ];

        // =========================
        // CHART DATA
        // =========================
        $areaChart = $this->laporanHarianModel->chart30Hari();

        $gender = $this->pegawaiModel->countByGender();
        $pieChart = [
            'laki'      => $gender['Laki-laki'] ?? 0,
            'perempuan' => $gender['Perempuan'] ?? 0,
        ];

        // =========================
        // TABLE DATA
        // =========================
        $laporanTerbaru       = $this->laporanHarianModel->latest(5);
        $menungguDisetujui    = $this->laporanHarianModel->latestPendingKegiatan(4);
        $tidakKirim           = $this->laporanHarianModel->belumKirimHariIni(5, $today);

        // =========================
        // RENDER VIEW
        // =========================
        view('admin/dashboard', [
            'title'           => 'Dashboard Admin',
            'admin'           => $admin,

            // Cards
            'stats'           => $stats,

            // Charts
            'areaChart'       => $areaChart,
            'pieChart'        => $pieChart,

            // Tables
            'laporanTerbaru'      => $laporanTerbaru,
            'menungguDisetujui'   => $menungguDisetujui,
            'tidakKirim'          => $tidakKirim,
        ]);
    }
}
