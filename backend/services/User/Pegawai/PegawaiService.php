<?php

class PegawaiService
{
    public function __construct(
        private PegawaiModel $pegawai,
        private ImageUploadService $image,
        private string $uploadDir = __DIR__ . '/../../../public/uploads/foto/',
    ) {}

    private function validateUpdate(array $data): void
    {
        // 1. Nama wajib diisi
        if (empty(trim($data['nama'] ?? ''))) {
            throw new Exception("Nama wajib diisi.");
        }

        // 2. Jenis kelamin validasi opsional, misal: Laki-laki / Perempuan / Tidak disebutkan
        $allowedGender = ['Laki-laki', 'Perempuan', 'Tidak disebutkan'];
        if (!empty($data['jenis_kelamin']) && !in_array($data['jenis_kelamin'], $allowedGender, true)) {
            throw new Exception("Jenis kelamin tidak valid.");
        }

        // 3. New password (opsional), jika diisi minimal 6 karakter
        if (!empty($data['password']) && strlen($data['password']) < 6) {
            throw new Exception("Password minimal 6 karakter.");
        }

        // 4. Jika email diisi, validasi format
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Format email tidak valid.");
        }

        // 5. Jika no_wa diisi, validasi format sederhana (hanya angka dan +)
        if (!empty($data['no_wa']) && !preg_match('/^\+?\d+$/', $data['no_wa'])) {
            throw new Exception("Nomor WhatsApp tidak valid.");
        }
    }

    public function update(int $id, array $input): array
    {
        $pegawai = $this->pegawai->findById($id);

        if (!$pegawai) {
            throw new Exception("Pegawai tidak ditemukan.");
        }

        // Panggil validasi sebelum update
        $this->validateUpdate($input);

        $data = [
            'nama'          => trim($input['nama']),
            'jenis_kelamin' => $input['jenis_kelamin'] ?? 'Tidak disebutkan',
            'email'         => trim($input['email'] ?? null),
            'no_wa'         => trim($input['no_wa'] ?? null),
        ];

        if (!empty($input['password'])) {
            $data['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
        }

        try {
            $this->pegawai->update($id, $data);
            return $this->pegawai->findById($id);
        } catch (Exception $e) {
            throw new Exception("Gagal memperbarui data pegawai: " . $e->getMessage());
        }
    }

    public function delete(int $id): void
    {
        $pegawai = $this->pegawai->findById($id);

        if (!$pegawai) {
            throw new Exception("Pegawai tidak ditemukan.");
        }

        try {
            // Hapus foto jika ada
            $this->image->delete(
                $this->uploadDir,
                $pegawai['foto']
            );

            // Hapus data pegawai
            $this->pegawai->delete($id);
        } catch (Exception $e) {
            throw new Exception("Gagal menghapus pegawai: " . $e->getMessage());
        }
    }

    public function updateFotoProfil(int $id, array $file): array
    {
        $pegawai = $this->pegawai->findById($id);

        if (!$pegawai) {
            throw new Exception("Pegawai tidak ditemukan.");
        }

        try {
            $fotoBaru = $this->image->upload(
                $file,
                $this->uploadDir
            );
        } catch (Exception $e) {
            // Tangani error upload dan buat pesan user-friendly
            throw new Exception("Upload foto gagal: " . $e->getMessage());
        }

        if (!empty($pegawai['foto']) && $pegawai['foto'] !== 'default_profile.svg') {
            $this->image->delete(
                $this->uploadDir,
                $pegawai['foto']
            );
        }

        $this->pegawai->update($id, [
            'foto' => $fotoBaru
        ]);
        return $this->pegawai->findById($id);
    }
}
