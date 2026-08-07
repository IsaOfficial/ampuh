<?php

class AdminJabatanService
{
    public function __construct(
        private JabatanModel $jabatanModel
    ) {}

    public function getAll(): array
    {
        return $this->jabatanModel->all();
    }

    public function create(array $input): void
    {
        $name = $this->normalizeName($input['nama'] ?? '');
        $isActive = !empty($input['is_active']);

        if ($this->jabatanModel->existsByName($name)) {
            throw new Exception('Nama jabatan sudah tersedia.');
        }

        $this->jabatanModel->create($name, $isActive);
    }

    public function update(int $id, array $input): void
    {
        $name = $this->normalizeName($input['nama'] ?? '');
        $isActive = !empty($input['is_active']);

        if (!$this->jabatanModel->findById($id)) {
            throw new Exception('Jabatan tidak ditemukan.');
        }

        if ($this->jabatanModel->existsByName($name, $id)) {
            throw new Exception('Nama jabatan sudah digunakan oleh data lain.');
        }

        $this->jabatanModel->update($id, $name, $isActive);
    }

    public function toggle(int $id): void
    {
        $jabatan = $this->jabatanModel->findById($id);
        if (!$jabatan) {
            throw new Exception('Jabatan tidak ditemukan.');
        }

        $this->jabatanModel->setActive($id, empty($jabatan['is_active']));
    }

    public function delete(int $id): void
    {
        $jabatan = $this->jabatanModel->findById($id);
        if (!$jabatan) {
            throw new Exception('Jabatan tidak ditemukan.');
        }

        if ($this->jabatanModel->countPegawai($jabatan['nama']) > 0) {
            throw new Exception('Jabatan masih dipakai pegawai. Nonaktifkan jabatan jika tidak ingin dipakai lagi pada form.');
        }

        $this->jabatanModel->delete($id);
    }

    private function normalizeName(mixed $value): string
    {
        $name = trim(preg_replace('/\s+/', ' ', (string) $value));

        if ($name === '') {
            throw new Exception('Nama jabatan wajib diisi.');
        }

        if (strlen($name) > 100) {
            throw new Exception('Nama jabatan maksimal 100 karakter.');
        }

        return $name;
    }
}
