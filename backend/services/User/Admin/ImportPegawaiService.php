<?php

class PegawaiImportService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function importFromCsv(array $file): void
    {
        $this->validateCsv($file);

        $tmpFile = $file['tmp_name'];
        $folderTempFoto = __DIR__ . "/../../temp_import/";
        $folderFoto     = __DIR__ . "/../../public/uploads/foto/";

        if (!is_dir($folderFoto)) {
            mkdir($folderFoto, 0777, true);
        }

        if (($handle = fopen($tmpFile, "r")) === false) {
            throw new Exception("Gagal membaca file CSV.");
        }

        // Lewati header
        fgetcsv($handle);

        while (($row = fgetcsv($handle, 1000, ";")) !== false) {

            if (count($row) < 8) {
                continue;
            }

            $this->importRow($row, $folderTempFoto, $folderFoto);
        }

        fclose($handle);
    }

    private function importRow(array $row, string $folderTemp, string $folderFoto): void
    {
        $nama          = trim($row[0]);
        $nip           = trim($row[1] ?? '');
        $nik           = trim($row[2]);
        $jabatan       = trim($row[3]);
        $email         = trim($row[4] ?? '');
        $passwordRaw   = trim($row[5]);
        $noWa          = trim($row[6] ?? '');
        $jenisKelamin  = trim($row[7]);
        $foto          = $row[8] ?? null;

        // Cek duplikat
        $cek = $this->db->prepare(
            "SELECT id FROM user WHERE nip = :nip OR nik = :nik LIMIT 1"
        );
        $cek->execute([':nip' => $nip, ':nik' => $nik]);

        if ($cek->fetch()) {
            return;
        }

        // Hash password
        $password = password_hash($passwordRaw, PASSWORD_DEFAULT);

        // Foto (opsional)
        $fotoFinal = null;
        if (!empty($foto)) {
            $src = $folderTemp . $foto;
            $dst = $folderFoto . $foto;

            if (file_exists($src)) {
                copy($src, $dst);
                $fotoFinal = $foto;
            }
        }

        // Insert
        $stmt = $this->db->prepare(
            "INSERT INTO user
            (nama, nip, nik, jabatan, email, password, no_wa, jenis_kelamin, foto)
            VALUES
            (:nama, :nip, :nik, :jabatan, :email, :password, :no_wa, :jenis_kelamin, :foto)"
        );

        $stmt->execute([
            ':nama'          => $nama,
            ':nip'           => $nip,
            ':nik'           => $nik,
            ':jabatan'       => $jabatan,
            ':email'         => $email,
            ':password'      => $password,
            ':no_wa'         => $noWa,
            ':jenis_kelamin' => $jenisKelamin,
            ':foto'          => $fotoFinal
        ]);
    }

    private function validateCsv(array $file): void
    {
        $allowedExt = ['csv'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt)) {
            throw new Exception("File harus berformat CSV.");
        }
    }
}
