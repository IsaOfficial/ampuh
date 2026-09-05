<?php

class CsrfMiddleware
{
    public static function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (self::isPostBodyTooLarge()) {
            Session::flash('flash', [
                'type' => 'danger',
                'message' => 'Ukuran upload melebihi batas konfigurasi server. Jika perlu menerima file lebih besar, naikkan upload_max_filesize dan post_max_size pada konfigurasi hosting.'
            ]);

            $redirect = $_SERVER['HTTP_REFERER'] ?? '/login';
            header('Location: ' . $redirect);
            exit;
        }

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            view('errors/403');
            exit;
        }

        Csrf::regenerate();
    }

    private static function isPostBodyTooLarge(): bool
    {
        $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($contentLength <= 0 || !empty($_POST)) {
            return false;
        }

        $postMaxSize = self::iniSizeToBytes((string) ini_get('post_max_size'));

        return $postMaxSize > 0 && $contentLength > $postMaxSize;
    }

    private static function iniSizeToBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return match ($unit) {
            'g' => (int) ($number * 1024 * 1024 * 1024),
            'm' => (int) ($number * 1024 * 1024),
            'k' => (int) ($number * 1024),
            default => (int) $number,
        };
    }
}
