<?php

class AdminPegawaiService
{
    public function __construct(
        private PegawaiModel $pegawai,
        private ImageUploadService $image,
        private string $uploadDir = __DIR__ . '/../../../public/uploads/foto/'
    ) {}

    private function validate(array $data, bool $isNew = false): void
    {
        // Nama wajib diisi
        if (empty(trim($data['nama'] ?? ''))) {
            throw new Exception("Nama wajib diisi.");
        }

        // Email wajib untuk pegawai baru
        if ($isNew && empty(trim($data['email'] ?? ''))) {
            throw new Exception("Email wajib diisi.");
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Format email tidak valid.");
        }

        if (!empty($data['no_wa']) && !preg_match('/^\+?\d+$/', $data['no_wa'])) {
            throw new Exception("Nomor WhatsApp tidak valid.");
        }

        $allowedGender = ['Laki-laki', 'Perempuan', 'Tidak disebutkan'];
        if (!empty($data['jenis_kelamin']) && !in_array($data['jenis_kelamin'], $allowedGender, true)) {
            throw new Exception("Jenis kelamin tidak valid.");
        }

        if (!empty($data['password']) && strlen($data['password']) < 6) {
            throw new Exception("Password minimal 6 karakter.");
        }
    }

    public function create(array $data): void
    {
        $this->validate($data, true);

        $pegawaiData = [
            'nama'          => trim($data['nama']),
            'nip'           => trim($data['nip'] ?? null),
            'nik'           => trim($data['nik']),
            'jabatan'       => trim($data['jabatan'] ?? 'Tidak diketahui'),
            'email'         => trim($data['email'] ?? null),
            'password'      => password_hash($data['password'], PASSWORD_DEFAULT),
            'no_wa'         => trim($data['no_wa'] ?? null),
            'jenis_kelamin' => $data['jenis_kelamin'] ?? 'Tidak diketahui',
            'role'          => $data['role'] ?? 'pegawai',
            'foto'          => $data['foto'] ?? 'default_profile.svg',
        ];

        $this->pegawai->create($pegawaiData);
    }

    public function update(int $id, array $data): void
    {
        $pegawai = $this->pegawai->findById($id);
        if (!$pegawai) {
            throw new Exception("Pegawai tidak ditemukan.");
        }

        $this->validate($data);

        $updateData = [
            'nama'          => trim($data['nama']),
            'nip'           => trim($data['nip'] ?? null),
            'nik'           => trim($data['nik']),
            'jabatan'       => trim($data['jabatan'] ?? 'Tidak diketahui'),
            'email'         => trim($data['email'] ?? null),
            'password'      => password_hash($data['password'], PASSWORD_DEFAULT),
            'no_wa'         => trim($data['no_wa'] ?? null),
            'jenis_kelamin' => $data['jenis_kelamin'] ?? 'Tidak diketahui',
            'role'          => $data['role'] ?? 'pegawai',
            'foto'          => $data['foto'] ?? 'default_profile.svg',
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $this->pegawai->update($id, $updateData);
    }

    public function delete(int $id): void
    {
        $pegawai = $this->pegawai->findById($id);
        if (!$pegawai) {
            throw new Exception("Pegawai tidak ditemukan.");
        }

        if (!empty($pegawai['foto']) && $pegawai['foto'] !== 'default_profile.svg') {
            $this->image->delete($this->uploadDir, $pegawai['foto']);
        }

        $this->pegawai->delete($id);
    }

    public function updateFoto(int $id, array $file): void
    {
        $pegawai = $this->pegawai->findById($id);
        if (!$pegawai) {
            throw new Exception("Pegawai tidak ditemukan.");
        }

        $fotoBaru = $this->image->upload($file, $this->uploadDir);

        if (!empty($pegawai['foto']) && $pegawai['foto'] !== 'default_profile.svg') {
            $this->image->delete($this->uploadDir, $pegawai['foto']);
        }

        $this->pegawai->update($id, ['foto' => $fotoBaru]);
    }

    public function getAll(?string $keyword = null): array
    {
        return $this->pegawai->getAll($keyword);
    }
}
