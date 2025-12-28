<?php

class PegawaiProfilController
{
    private AuthService $authService;
    private PegawaiService $pegawaiService;

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
