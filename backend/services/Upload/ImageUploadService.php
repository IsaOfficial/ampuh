<?php

class ImageUploadService
{
    private array $allowedExt = ['jpg', 'jpeg', 'png'];
    private array $allowedMime = [
        'image/jpeg',
        'image/png'
    ];

    private int $maxDimension = 800;

    public function upload(
        array $file,
        string $dir,
        int $maxSize = 2_097_152 // 2MB
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
            throw new Exception("Format gambar tidak diizinkan.");
        }

        // 4. Validasi MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $this->allowedMime, true)) {
            throw new Exception("File bukan gambar yang valid.");
        }

        // 5. Ambil dimensi gambar
        $size = getimagesize($file['tmp_name']);
        if ($size === false) {
            throw new Exception("Gagal membaca dimensi gambar.");
        }

        [$width, $height] = $size;

        // 6. Hitung skala resize (tanpa upscale)
        $scale = min(
            $this->maxDimension / $width,
            $this->maxDimension / $height,
            1
        );

        $newWidth  = (int) ($width * $scale);
        $newHeight = (int) ($height * $scale);

        // 7. Buat image resource
        $srcImage = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
            'image/png'  => imagecreatefrompng($file['tmp_name']),
        };

        if (!$srcImage) {
            throw new Exception("Gagal memproses gambar.");
        }

        $dstImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency PNG
        if ($mime === 'image/png') {
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
            $transparent = imagecolorallocatealpha($dstImage, 0, 0, 0, 127);
            imagefilledrectangle(
                $dstImage,
                0,
                0,
                $newWidth,
                $newHeight,
                $transparent
            );
        }

        // 8. Resize
        imagecopyresampled(
            $dstImage,
            $srcImage,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $width,
            $height
        );

        // 9. Pastikan folder ada
        // NORMALISASI PATH
        $dir = rtrim($dir, DIRECTORY_SEPARATOR);
        $realDir = realpath($dir) ?: $dir;

        if (!is_dir($realDir)) {
            mkdir($realDir, 0755, true);
        }

        if (!is_writable($realDir)) {
            throw new Exception("Direktori upload tidak writable: {$realDir}");
        }

        // 10. Simpan file
        $filename = uniqid('foto_', true) . '.' . $ext;
        $target = $realDir . DIRECTORY_SEPARATOR . $filename;

        match ($mime) {
            'image/jpeg' => imagejpeg($dstImage, $target, 75),
            'image/png'  => imagepng($dstImage, $target, 6),
        };

        imagedestroy($srcImage);
        imagedestroy($dstImage);

        return $filename;
    }

    public function delete(string $dir, ?string $filename): void
    {
        if (!$filename) return;

        $path = rtrim($dir, '/') . '/' . $filename;
        if (file_exists($path)) unlink($path);
    }
}
