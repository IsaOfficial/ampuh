<?php

class AdminService
{
    private PegawaiModel $pegawai;
    private ImageUploadService $upload;

    public function __construct()
    {
        $this->pegawai = new PegawaiModel();
        $this->upload = new ImageUploadService();
    }

    private function validate(array $data): void
    {
        if (empty($data['nama']) || empty($data['password'])) {
            throw new Exception("Nama dan password wajib diisi.");
        }
    }

    public function create(array $data, array $foto): void
    {
        $this->validate($data);

        $fotoName = null;
        if (!empty($foto['name'])) {
            $fotoName = $this->upload->upload(
                $foto,
                __DIR__ . "/../../public/uploads/foto/",
            );
        }

        $this->pegawai->create([
            'foto' => $fotoName,
            'nama' => $data['nama'],
            'nip' => $data['nip'],
            'nik' => $data['nik'],
            'jabatan' => $data['jabatan'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'no_wa' => $data['no_wa'],
            'jenis_kelamin' => $data['jenis_kelamin'],
            'role' => 'pegawai'
        ]);
    }
}
