<?php

class PegawaiProfilController
{
    private AuthService $authService;
    private PegawaiService $pegawaiService;

    public function __construct()
    {
        $pegawaiModel = new PegawaiModel();

        $this->authService = new AuthService(
            $pegawaiModel,
            new AdminModel()
        );

        $this->pegawaiService = new PegawaiService(
            $pegawaiModel,
            new ImageUploadService()
        );
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

            $this->pegawaiService->updateProfil(
                $pegawai['id'],
                $_POST
            );

            Session::flash('flash', [
                'type'    => 'success',
                'message' => 'Profil berhasil diperbarui.'
            ]);
        } catch (Throwable $e) {
            Session::flash('flash', [
                'type'    => 'danger',
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

            $this->pegawaiService->updateFoto(
                $pegawai['id'],
                $_FILES['foto']
            );

            Session::flash('flash', [
                'type'    => 'success',
                'message' => 'Foto profil berhasil diperbarui.'
            ]);
        } catch (Throwable $e) {
            Session::flash('flash', [
                'type'    => 'danger',
                'message' => $e->getMessage()
            ]);
        }

        header('Location: /pegawai/profil');
        exit;
    }
}
