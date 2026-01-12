<?php

class AdminPegawaiImportService
{
    public function __construct(
        private AdminPegawaiService $pegawaiService
    ) {}

    public function importFromCsv(array $file): void
    {
        $this->validateCsv($file);

        if (($handle = fopen($file['tmp_name'], 'r')) === false) {
            throw new Exception("Gagal membaca file CSV.");
        }

        // Skip header
        fgetcsv($handle, 0, ';');

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) < 6) {
                continue;
            }

            $data = $this->mapRowToInput($row);

            try {
                $this->pegawaiService->create($data);
            } catch (Throwable $e) {
                // Ideal: log error + baris CSV
                continue;
            }
        }

        fclose($handle);
    }

    private function mapRowToInput(array $row): array
    {
        return [
            'nama'          => trim($row[0] ?? ''),
            'nip'           => trim($row[1] ?? ''),
            'nik'           => trim($row[2] ?? ''),
            'jabatan'       => trim($row[3] ?? ''),
            'jenis_kelamin' => trim($row[4] ?? ''),
            'password'      => trim($row[5] ?? ''),
            'email'         => trim($row[6] ?? ''),
            'no_wa'         => trim($row[7] ?? ''),
        ];
    }

    private function validateCsv(array $file): void
    {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception("File CSV tidak valid.");
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            throw new Exception("File harus berformat CSV.");
        }
    }
}
