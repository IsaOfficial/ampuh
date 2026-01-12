<?php

class PegawaiService
{
    public function __construct(
        private PegawaiModel $pegawaiModel,
        private ImageUploadService $image,
        private string $uploadDir = PUBLIC_PATH . '/uploads/foto',
    ) {}

    public function updateProfil(int $pegawaiId, array $input): void
    {
        $pegawai = $this->pegawaiModel->findPegawaiById($pegawaiId);
        if (!$pegawai) {
            throw new Exception("Pegawai tidak ditemukan.");
        }

        // Validasi & normalisasi
        $data = PegawaiProfileValidator::validateUpdate($input);

        // Email unik (jika berubah)
        if (!empty($data['email']) && $data['email'] !== $pegawai['email']) {
            if ($this->pegawaiModel->existsByEmail($data['email'])) {
                throw new Exception("Email sudah digunakan.");
            }
        }

        // Extra safety: cegah kolom sensitif
        unset(
            $data['nip'],
            $data['nik'],
            $data['role'],
            $data['password']
        );

        $this->pegawaiModel->update(
            $pegawaiId,
            array_filter($data, static fn($v) => $v !== null)
        );
    }

    public function updateFoto(int $pegawaiId, array $file): void
    {
        $pegawai = $this->pegawaiModel->findPegawaiById($pegawaiId);
        if (!$pegawai) {
            throw new Exception("Pegawai tidak ditemukan.");
        }

        $fotoBaru = $this->image->upload($file, $this->uploadDir);

        if (!empty($pegawai['foto']) && $pegawai['foto'] !== 'default_profile.svg') {
            $this->image->delete($this->uploadDir, $pegawai['foto']);
        }

        $this->pegawaiModel->update($pegawaiId, [
            'foto' => $fotoBaru
        ]);
    }
}
