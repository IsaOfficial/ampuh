<?php

class AdminPegawaiController
{
    private PegawaiModel $pegawai;

    public function __construct()
    {
        AuthMiddleware::handle();
        $this->pegawai = new PegawaiModel();
    }

    // =========================================================
    // MENAMPILKAN DAFTAR PEGAWAI
    // =========================================================
    public function index(): void
    {
        $keyword = $_GET['keyword'] ?? '';
        $pegawai = $this->pegawai->getAll($keyword);

        view('admin/kelola_pegawai', [
            'title'   => 'Kelola Pegawai',
            'pegawai' => $pegawai
        ]);
    }

    public function create($r): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/kelola/pegawai');
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

        try {
            $service = new PegawaiService();

            $service->create(
                [
                    'nama'           => trim($r['nama']),
                    'nip'            => trim($r['nip']),
                    'nik'            => trim($r['nik']),
                    'jabatan'        => trim($r['jabatan']),
                    'email'          => trim($r['email']),
                    'password'       => trim($r['password']),
                    'no_wa'          => trim($r['no_wa']),
                    'jenis_kelamin'  => trim($r['jenis_kelamin']),
                    'role'           => $r['role'] ?? 'pegawai',
                ],
                $_FILES['foto'] ?? []
            );

            Session::set('success', "Pegawai berhasil ditambahkan.");
        } catch (Exception $e) {
            Session::set('error', $e->getMessage());
        }

        header("Location: /admin/kelola/pegawai");
        exit;
    }

    public function update($r)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/kelola/pegawai');
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

        try {
            $service = new PegawaiService();
            $service->update(
                (int)$r['id'],
                $r,
                $_FILES['foto'] ?? []
            );

            Session::set('success', "Data pegawai berhasil diperbarui.");
        } catch (Exception $e) {
            Session::set('error', $e->getMessage());
        }

        header("Location: /admin/kelola/pegawai");
        exit;
    }

    public function delete($r)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /admin/kelola/pegawai");
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

        try {
            $service = new PegawaiService();
            $service->delete((int)$r['id']);

            Session::set("success", "Pegawai berhasil dihapus.");
        } catch (Exception $e) {
            Session::set("error", $e->getMessage());
        }

        header("Location: /admin/kelola/pegawai");
        exit;
    }

    // ============================
    // EXPORT PDF
    // ============================
    public function exportPdf()
    {
        $keyword = $_GET['keyword'] ?? null;

        $service = new PegawaiService();
        $data = $service->getForExport($keyword);

        header("Content-Disposition: inline; filename=data_pegawai.pdf");

        require __DIR__ . "/../../public/views/admin/export/pegawai/pdf.php";
        exit;
    }

    // ============================
    // EXPORT EXCEL
    // ============================
    public function exportExcel()
    {
        $keyword = $_GET['keyword'] ?? null;

        $service = new PegawaiService();
        $data = $service->getForExport($keyword);

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=data_pegawai.xls");
        header("Pragma: no-cache");
        header("Expires: 0");

        require __DIR__ . "/../../public/views/admin/export/pegawai/excel.php";
        exit;
    }

    // ============================
    // IMPORT PEGAWAI DARI CSV
    // ============================
    public function importPegawai()
    {
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            Session::flash('flash', [
                'type' => 'error',
                'message' => 'Sesi tidak valid, silakan login kembali .'
            ]);
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file_csv']['tmp_name'])) {
            Session::set('error', "File CSV tidak ditemukan.");
            header("Location: /admin/kelola/pegawai");
            exit;
        }

        try {
            $service = new PegawaiImportService();
            $service->importFromCsv($_FILES['file_csv']);

            Session::set('success', "Berhasil import data pegawai.");
        } catch (Exception $e) {
            Session::set('error', $e->getMessage());
        }

        header("Location: /admin/kelola/pegawai");
        exit;
    }
}
