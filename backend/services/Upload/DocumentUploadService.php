<?php

class DocumentUploadService
{
    private array $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
    private array $allowedMime = [
        'image/jpeg',
        'image/png',
        'application/pdf'
    ];

    public function upload(
        array $file,
        string $dir,
        int $maxSize = 5_242_880 // 5MB
    ): string {
        // 1. Validasi upload
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload file gagal.");
        }

        // 2. Validasi ukuran
        if ($file['size'] > $maxSize) {
            throw new Exception("Ukuran file melebihi batas.");
        }

        // 3. Validasi ekstensi
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedExt, true)) {
            throw new Exception("Format file tidak diizinkan.");
        }

        // 4. Validasi MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $this->allowedMime, true)) {
            throw new Exception("Tipe file tidak valid.");
        }

        // 5. Pastikan folder ada
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // 6. Simpan file (TANPA resize)
        $filename = uniqid('bukti_', true) . '.' . $ext;
        $target   = rtrim($dir, '/') . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new Exception("Gagal menyimpan file.");
        }

        return $filename;
    }

    public function delete(string $dir, ?string $filename): void
    {
        if (!$filename) return;

        $path = rtrim($dir, '/') . '/' . $filename;
        if (file_exists($path)) unlink($path);
    }
}
