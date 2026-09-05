<?php

class DocumentUploadService
{
    private int $maxImageDimension = 1600;
    private int $jpegQuality = 75;
    private int $pngCompression = 6;
    private array $unsafeExecutableExt = [
        'php',
        'php3',
        'php4',
        'php5',
        'phtml',
        'phar',
        'cgi',
        'pl',
        'py',
        'rb',
        'asp',
        'aspx',
        'jsp',
        'sh',
        'bat',
        'cmd',
        'com',
        'exe',
        'msi',
        'html',
        'htm',
        'js',
        'mjs',
        'svg',
        'svgz',
        'xml'
    ];

    public function validate(array $file): string
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload file gagal.");
        }

        $mime = $this->detectMime($file);

        if (!$this->isAllowedMime($mime)) {
            throw new Exception("Tipe file tidak valid. Gunakan gambar, PDF, atau video.");
        }

        return $mime;
    }

    private function detectMime(array $file): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']) ?: 'application/octet-stream';
        finfo_close($finfo);

        return $mime;
    }

    public function upload(
        array $file,
        string $dir
    ): string {
        $mime = $this->validate($file);

        // 4. Pastikan folder ada
        // NORMALISASI PATH
        $dir = rtrim($dir, DIRECTORY_SEPARATOR);
        $realDir = realpath($dir) ?: $dir;

        if (!is_dir($realDir)) {
            mkdir($realDir, 0755, true);
        }

        if (!is_writable($realDir)) {
            throw new Exception("Direktori upload tidak writable: {$realDir}");
        }

        $this->protectUploadDirectory($realDir);

        // 5. Simpan file. JPG/PNG dikompresi, format lain disimpan normal.
        $ext = $this->getSafeExtension($file['name'], $mime);
        $filename = uniqid('bukti_', true) . '.' . $ext;
        $target = $realDir . DIRECTORY_SEPARATOR . $filename;

        if ($mime === 'image/jpeg' || $mime === 'image/png') {
            $this->saveCompressedImage($file['tmp_name'], $target, $mime);

            return $filename;
        }

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

    private function isAllowedMime(string $mime): bool
    {
        return str_starts_with($mime, 'image/')
            || str_starts_with($mime, 'video/')
            || $mime === 'application/pdf';
    }

    private function getSafeExtension(string $originalName, string $mime): string
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: '';
        $mimeExt = $this->extensionFromMime($mime);

        if ($mime === 'application/pdf') {
            return 'pdf';
        }

        if ($mime === 'image/jpeg') {
            return 'jpg';
        }

        if ($mime === 'image/png') {
            return 'png';
        }

        if ($ext === '' || in_array($ext, $this->unsafeExecutableExt, true)) {
            return $mimeExt ?? 'bin';
        }

        return $ext;
    }

    private function extensionFromMime(string $mime): ?string
    {
        return match ($mime) {
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/bmp' => 'bmp',
            'image/heic' => 'heic',
            'image/heif' => 'heif',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/webm' => 'webm',
            'video/x-msvideo' => 'avi',
            'video/x-matroska' => 'mkv',
            'video/3gpp' => '3gp',
            'video/3gpp2' => '3g2',
            default => null,
        };
    }

    private function protectUploadDirectory(string $dir): void
    {
        $htaccessPath = $dir . DIRECTORY_SEPARATOR . '.htaccess';

        if (file_exists($htaccessPath)) {
            return;
        }

        $rules = <<<'HTACCESS'
Options -ExecCGI
RemoveHandler .php .php3 .php4 .php5 .phtml .phar .cgi .pl .py .rb .asp .aspx .jsp
RemoveType .php .php3 .php4 .php5 .phtml .phar .cgi .pl .py .rb .asp .aspx .jsp
<FilesMatch "\.(php|php3|php4|php5|phtml|phar|cgi|pl|py|rb|asp|aspx|jsp)$">
    Require all denied
</FilesMatch>
HTACCESS;

        @file_put_contents($htaccessPath, $rules . PHP_EOL);
    }

    private function saveCompressedImage(string $sourcePath, string $targetPath, string $mime): void
    {
        $size = getimagesize($sourcePath);
        if ($size === false) {
            throw new Exception("Gagal membaca dimensi gambar.");
        }

        [$width, $height] = $size;
        $scale = min(
            $this->maxImageDimension / $width,
            $this->maxImageDimension / $height,
            1
        );

        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $srcImage = $this->createImageResource($sourcePath, $mime);
        $dstImage = imagecreatetruecolor($newWidth, $newHeight);

        if ($mime === 'image/png') {
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
            $transparent = imagecolorallocatealpha($dstImage, 0, 0, 0, 127);
            imagefilledrectangle($dstImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

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

        $saved = match ($mime) {
            'image/jpeg' => imagejpeg($dstImage, $targetPath, $this->jpegQuality),
            'image/png' => imagepng($dstImage, $targetPath, $this->pngCompression),
            default => false,
        };

        imagedestroy($srcImage);
        imagedestroy($dstImage);

        if (!$saved) {
            throw new Exception("Gagal menyimpan file gambar.");
        }
    }

    private function createImageResource(string $sourcePath, string $mime): GdImage
    {
        $image = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($sourcePath),
            'image/png' => imagecreatefrompng($sourcePath),
            default => false,
        };

        if (!$image) {
            throw new Exception("Gagal memproses gambar.");
        }

        return $image;
    }
}
