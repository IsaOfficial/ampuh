<?php

class AdminPegawaiService
{
    public function __construct(
        private PegawaiModel $pegawaiModel,
        private ImageUploadService $image,
        private string $uploadDir = PUBLIC_PATH . '/uploads/foto',
    ) {}

    public function create(array $input): void
    {
        // 1. Validasi & normalisasi
        $data = PegawaiValidator::validateCreate($input);

        // 2. Cek duplikat (domain rule)
        if ($this->pegawaiModel->existsByNik($data['nik'])) {
            throw new Exception("NIK sudah terdaftar.");
        }

        if (!empty($data['nip']) && $this->pegawaiModel->existsByNip($data['nip'])) {
            throw new Exception("NIP sudah terdaftar.");
        }

        if (!empty($data['email']) && $this->pegawaiModel->existsByEmail($data['email'])) {
            throw new Exception("Email sudah terdaftar.");
        }

        // 3. Hash password
        $data['password'] = password_hash(
            $data['password'],
            PASSWORD_DEFAULT
        );

        // 4. Persist
        $this->pegawaiModel->create($data);
    }

    public function update(
        int $id,
        array $input,
        ?array $file = null
    ): void {
        $pegawai = $this->pegawaiModel->findPegawaiById($id);
        if (!$pegawai) {
            throw new Exception("Pegawai tidak ditemukan.");
        }

        // 1. Validasi & normalisasi input teks
        $data = PegawaiValidator::validateUpdate($input);

        // 2. Cek duplikat (jika berubah)
        if (!empty($data['nik']) && $data['nik'] !== $pegawai['nik']) {
            if ($this->pegawaiModel->existsByNik($data['nik'])) {
                throw new Exception("NIK sudah terdaftar.");
            }
        }

        if (!empty($data['nip']) && $data['nip'] !== $pegawai['nip']) {
            if ($this->pegawaiModel->existsByNip($data['nip'])) {
                throw new Exception("NIP sudah terdaftar.");
            }
        }

        if (!empty($data['email']) && $data['email'] !== $pegawai['email']) {
            if ($this->pegawaiModel->existsByEmail($data['email'])) {
                throw new Exception("Email sudah terdaftar.");
            }
        }

        // 3. Password (opsional)
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password']);
        }

        // 4. Normalisasi field opsional (KOSONG → NULL)
        foreach (['email', 'no_wa', 'nip'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = trim((string) $data[$field]) === ''
                    ? null
                    : trim($data[$field]);
            }
        }

        // 5. Foto (opsional)
        if (
            $file &&
            isset($file['error']) &&
            $file['error'] === UPLOAD_ERR_OK
        ) {
            $fotoBaru = $this->image->upload($file, $this->uploadDir);
            $data['foto'] = $fotoBaru;

            // cleanup foto lama (best effort)
            if (
                !empty($pegawai['foto']) &&
                $pegawai['foto'] !== 'default_profile.svg'
            ) {
                try {
                    $this->image->delete($this->uploadDir, $pegawai['foto']);
                } catch (Throwable $e) {
                    // log only
                }
            }
        } else {
            // tidak upload → pakai foto lama
            $data['foto'] = $pegawai['foto'];
        }

        // 6. Jangan ubah role
        unset($data['role']);

        // 7. Persist (TANPA array_filter)
        $this->pegawaiModel->update($id, $data);
    }

    public function delete(int $id): void
    {
        $pegawai = $this->pegawaiModel->findPegawaiById($id);
        if (!$pegawai) {
            throw new Exception("Pegawai tidak ditemukan.");
        }

        // 1. Hapus record DB terlebih dahulu
        $this->pegawaiModel->delete($id);

        // 2. Cleanup file (best effort)
        if (
            !empty($pegawai['foto']) &&
            $pegawai['foto'] !== 'default_profile.svg'
        ) {
            try {
                $this->image->delete($this->uploadDir, $pegawai['foto']);
            } catch (Throwable $e) {
                // optional: log error, jangan lempar ulang
            }
        }
    }

    public function getAll(?string $keyword = null): array
    {
        return $this->pegawaiModel->getAllPegawai($keyword);
    }
}
