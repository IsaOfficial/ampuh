<?php

class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        $token = Session::get(self::KEY);

        if (!$token) {
            $token = bin2hex(random_bytes(32));
            Session::set(self::KEY, $token);
        }

        return $token;
    }

    public static function input(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::token() . '">';
    }

    public static function verify(?string $token): bool
    {
        if (!$token) {
            return false;
        }

        $sessionToken = Session::get(self::KEY);

        return is_string($sessionToken)
            && hash_equals($sessionToken, $token);
    }

    public static function regenerate(): void
    {
        Session::set(self::KEY, bin2hex(random_bytes(32)));
    }
}
