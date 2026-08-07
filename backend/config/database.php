<?php
// config/Database.php

declare(strict_types=1);

class Database
{
    private static ?PDO $conn = null;

    public static function getConnection(): PDO
    {
        if (self::$conn === null) {
            $host    = getenv('DB_HOST') ?: 'localhost';
            $db      = getenv('DB_DATABASE') ?: 'ampuh';
            $user    = getenv('DB_USERNAME') ?: 'root';
            $pass    = getenv('DB_PASSWORD') ?: '';
            $charset = getenv('DB_CHARSET') ?: 'utf8mb4';

            $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            self::$conn = new PDO($dsn, $user, $pass, $options);
        }

        return self::$conn;
    }
}
