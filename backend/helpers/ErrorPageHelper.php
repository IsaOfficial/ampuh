<?php

class ErrorPage
{
    public static function render(
        int $statusCode,
        string $title,
        string $message,
        string $reason,
        string $icon = 'fa-exclamation-triangle',
        string $variant = 'warning',
        ?Throwable $exception = null
    ): void {
        if (!headers_sent()) {
            http_response_code($statusCode);
        }

        if (self::expectsJson()) {
            self::renderJson($statusCode, $title, $message, $reason);
            return;
        }

        $debugMode = filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN);

        try {
            view('errors/page', [
                'statusCode' => $statusCode,
                'title' => $title,
                'message' => $message,
                'reason' => $reason,
                'icon' => $icon,
                'variant' => $variant,
                'debugMessage' => ($debugMode && $exception) ? $exception->getMessage() : null,
            ]);
        } catch (Throwable $viewError) {
            self::renderPlain($statusCode, $title, $message);
        }
    }

    public static function notFound(): void
    {
        self::render(
            404,
            'Halaman Tidak Ditemukan',
            'Alamat yang Anda buka tidak tersedia atau sudah dipindahkan.',
            'Route atau URL tidak terdaftar pada sistem AMPUH.',
            'fa-search',
            'warning'
        );
    }

    public static function serverError(Throwable $exception): void
    {
        error_log($exception);

        self::render(
            500,
            'Terjadi Kesalahan Sistem',
            'Sistem sedang mengalami kendala saat memproses permintaan.',
            'Terjadi error di sisi aplikasi/server. Silakan coba beberapa saat lagi atau hubungi admin.',
            'fa-tools',
            'danger',
            $exception
        );
    }

    public static function httpVersionNotSupported(): void
    {
        self::render(
            505,
            'Versi HTTP Tidak Didukung',
            'Permintaan tidak dapat diproses karena versi protokol HTTP tidak didukung.',
            'Browser, proxy, atau server mengirim versi HTTP yang tidak kompatibel dengan aplikasi.',
            'fa-network-wired',
            'danger'
        );
    }

    public static function databaseUnavailable(Throwable $exception): void
    {
        error_log($exception);

        self::render(
            503,
            'Database Tidak Terhubung',
            'Sistem tidak dapat terhubung ke database untuk sementara waktu.',
            'Kemungkinan penyebab: server database mati, kredensial .env salah, nama database tidak tersedia, atau koneksi jaringan database bermasalah.',
            'fa-database',
            'danger',
            $exception
        );
    }

    private static function expectsJson(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    private static function renderJson(int $statusCode, string $title, string $message, string $reason): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode([
            'success' => false,
            'status' => $statusCode,
            'title' => $title,
            'message' => $message,
            'reason' => $reason,
        ], JSON_UNESCAPED_UNICODE);
    }

    private static function renderPlain(int $statusCode, string $title, string $message): void
    {
        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=utf-8');
        }

        echo "{$statusCode} {$title}\n{$message}";
    }
}
