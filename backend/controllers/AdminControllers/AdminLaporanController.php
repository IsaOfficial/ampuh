<?php

class AdminLaporanController
{
    private LaporanModel $laporan;
    private PegawaiModel $pegawai;
    private LaporanExportService $export;
    private DocumentUploadService $upload;

    public function __construct()
    {
        AuthMiddleware::handle();

        $this->laporan = new LaporanModel();
        $this->pegawai = new PegawaiModel();
        $this->export  = new LaporanExportService();
        $this->upload  = new DocumentUploadService();
    }

    public function index(): void
    {
        $keyword = $_GET['keyword'] ?? '';
        $start   = $_GET['start']   ?? '0000-01-01';
        $end     = $_GET['end']     ?? '9999-12-31';

        $pegawaiList = $this->pegawai->getAll();
        $laporan     = $this->laporan->getAdminLaporan($keyword, $start, $end);

        view('admin/kelola_laporan', [
            'title'        => 'Kelola Laporan',
            'laporan'      => $laporan,
            'pegawai_list' => $pegawaiList
        ]);
    }

    public function create(array $r): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/kelola/laporan');
            exit;
        }

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            Session::flash('flash', [
                'type' => 'error',
                'message' => 'Sesi tidak valid, silakan login kembali .'
            ]);
            header('Location: /login');
            exit;
        }

        $pegawaiId = (int)($r['pegawai_id'] ?? 0);
        $tanggal   = $r['tanggal']  ?? '';
        $kegiatan  = $r['kegiatan'] ?? '';
        $output    = $r['output']   ?? '';

        if (!$pegawaiId || !$tanggal || !$kegiatan) {
            Session::set('error', 'Semua field wajib diisi.');
            header('Location: /admin/kelola/laporan');
            exit;
        }

        try {
            $bukti = $this->upload->upload($r['bukti'] ?? []);
            $this->laporan->createLaporan(
                $pegawaiId,
                $tanggal,
                $kegiatan,
                $output,
                $bukti
            );

            Session::set('success', 'Laporan berhasil ditambahkan.');
        } catch (Throwable $e) {
            Session::set('error', 'Gagal menambahkan laporan.');
        }

        Csrf::regenerate();
        header('Location: /admin/kelola/laporan');
        exit;
    }

    public function update(array $r): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/kelola/laporan');
            exit;
        }

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            Session::flash('flash', [
                'type' => 'error',
                'message' => 'Sesi tidak valid, silakan login kembali .'
            ]);
            header('Location: /login');
            exit;
        }

        $id       = (int)($r['id'] ?? 0);
        $kegiatan = $r['kegiatan'] ?? '';
        $output   = $r['output'] ?? '';

        if (!$id) {
            Session::set('error', 'ID laporan tidak ditemukan.');
            header('Location: /admin/kelola/laporan');
            exit;
        }

        try {
            $before = $this->laporan->getKegiatanById($id);

            $bukti = $this->upload->upload(
                $before['bukti'],
                $_FILES['bukti'] ?? []
            );

            $this->laporan->updateLaporan(
                $id,
                $kegiatan,
                $output,
                $bukti
            );

            Session::set('success', 'Laporan berhasil diubah.');
        } catch (Throwable $e) {
            Session::set('error', 'Gagal memperbarui laporan.');
        }

        header('Location: /admin/kelola/laporan');
        exit;
    }

    public function delete(array $r): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/kelola/laporan');
            exit;
        }

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            Session::flash('flash', [
                'type' => 'error',
                'message' => 'Sesi tidak valid, silakan login kembali .'
            ]);
            header('Location: /login');
            exit;
        }

        $id = (int)($r['id'] ?? 0);

        if (!$id) {
            Session::set('error', 'ID laporan tidak valid.');
            header('Location: /admin/kelola/laporan');
            exit;
        }

        try {
            $this->laporan->deleteKegiatan($id);
            Session::set('success', 'Laporan berhasil dihapus.');
        } catch (Throwable $e) {
            Session::set('error', 'Gagal menghapus laporan.');
        }

        Csrf::regenerate();
        header('Location: /admin/kelola/laporan');
        exit;
    }

    public function exportPdf(): void
    {
        try {
            $export = new LaporanExportService();
            $export->exportPdfAdmin([
                'keyword' => $_GET['keyword'] ?? '',
                'start'   => $_GET['start']   ?? '',
                'end'     => $_GET['end']     ?? '',
            ]);
        } catch (Throwable $e) {
            Session::set('error', 'Gagal mengekspor laporan PDF.');
            header('Location: /admin/kelola/laporan');
            exit;
        }
    }

    public function exportExcel(): void
    {
        try {
            $export = new LaporanExportService();
            $export->exportExcelAdmin([
                'keyword' => $_GET['keyword'] ?? '',
                'start'   => $_GET['start']   ?? '',
                'end'     => $_GET['end']     ?? '',
            ]);
        } catch (Throwable $e) {
            Session::set('error', 'Gagal mengekspor laporan Excel.');
            header('Location: /admin/kelola/laporan');
            exit;
        }
    }
}
