<?php

class PegawaiValidator
{
    private const ALLOWED_GENDER = [
        'Laki-laki',
        'Perempuan',
        'Tidak diketahui'
    ];

    public static function validateCreate(array $data): array
    {
        self::require($data, 'nama');
        self::require($data, 'nik');
        self::require($data, 'jabatan');
        self::require($data, 'password');

        if (!empty($data['email'])) {
            self::email($data['email']);
        }

        self::password($data['password']);
        self::gender($data['jenis_kelamin'] ?? 'Tidak diketahui');
        self::phone($data['no_wa'] ?? null);

        return self::normalize($data);
    }

    public static function validateUpdate(array $data): array
    {
        if (array_key_exists('nama', $data)) {
            self::require($data, 'nama');
        }

        if (array_key_exists('jabatan', $data)) {
            self::require($data, 'jabatan');
        }

        if (!empty($data['email'])) {
            self::email($data['email']);
        }

        if (!empty($data['password'])) {
            self::password($data['password']);
        }

        self::gender($data['jenis_kelamin'] ?? null);
        self::phone($data['no_wa'] ?? null);

        return self::normalize($data);
    }

    /* =========================
     * RULES
     * ========================= */

    private static function require(array $data, string $field): void
    {
        if (empty(trim($data[$field] ?? ''))) {
            throw new Exception("Field {$field} wajib diisi.");
        }
    }

    private static function email(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Format email tidak valid.");
        }
    }

    private static function password(string $password): void
    {
        if (strlen($password) < 6) {
            throw new Exception("Password minimal 6 karakter.");
        }
    }

    private static function gender(?string $gender): void
    {
        if ($gender && !in_array($gender, self::ALLOWED_GENDER, true)) {
            throw new Exception("Jenis kelamin tidak valid.");
        }
    }

    private static function phone(?string $phone): void
    {
        if ($phone && !preg_match('/^\+?\d+$/', $phone)) {
            throw new Exception("Nomor WhatsApp tidak valid.");
        }
    }

    /* =========================
     * NORMALIZATION
     * ========================= */

    private static function normalize(array $data): array
    {
        return [
            'nama'          => isset($data['nama']) ? trim($data['nama']) : null,
            'nip'           => isset($data['nip']) && trim($data['nip']) !== '' ? trim($data['nip']) : null,
            'nik'           => isset($data['nik']) ? trim($data['nik']) : null,
            'jabatan'       => isset($data['jabatan']) ? trim($data['jabatan']) : null,
            'jenis_kelamin' => $data['jenis_kelamin'] ?? 'Tidak diketahui',
            'password'      => $data['password'] ?? null,
            'email'         => isset($data['email']) ? trim($data['email']) : null,
            'no_wa'         => isset($data['no_wa']) && trim($data['no_wa']) !== '' ? trim($data['no_wa']) : null,
            'role'          => $data['role'] ?? 'pegawai',
            'foto'          => $data['foto'] ?? 'default_profile.svg',
        ];
    }
}
