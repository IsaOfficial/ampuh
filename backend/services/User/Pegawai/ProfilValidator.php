<?php

class PegawaiProfileValidator
{
    private const ALLOWED_GENDER = [
        'Laki-laki',
        'Perempuan',
        'Tidak diketahui'
    ];

    private const DEGREE_MAP = [
        // DIPLOMA
        'A.Md'        => 'A.Md.',
        'A.Md.Ak'     => 'A.Md.Ak.',
        'A.Md.Kom'    => 'A.Md.Kom.',
        'A.Md.T'      => 'A.Md.T.',
        'A.Md.Kes'    => 'A.Md.Kes.',
        'A.Md.Farm'   => 'A.Md.Farm.',
        'A.Md.Kep'    => 'A.Md.Kep.',
        'A.Md.Keb'    => 'A.Md.Keb.',

        // SARJANA
        'S.Ag'        => 'S.Ag.',
        'S.Ars'       => 'S.Ars.',
        'S.E'         => 'S.E.',
        'S.Farm'      => 'S.Farm.',
        'S.Gz'        => 'S.Gz.',
        'S.H'         => 'S.H.',
        'S.Hum'       => 'S.Hum.',
        'S.I.Kom'     => 'S.I.Kom.',
        'S.IP'        => 'S.IP.',
        'S.Ked'       => 'S.Ked.',
        'S.Kep'       => 'S.Kep.',
        'S.KG'        => 'S.KG.',
        'S.Kom'       => 'S.Kom.',
        'S.M'         => 'S.M.',
        'S.P'         => 'S.P.',
        'S.Pd'        => 'S.Pd.',
        'S.Pd.I'      => 'S.Pd.I.',
        'S.Psi'       => 'S.Psi.',
        'S.S'         => 'S.S.',
        'S.Si'        => 'S.Si.',
        'S.Sn'        => 'S.Sn.',
        'S.Sos'       => 'S.Sos.',
        'S.ST'        => 'S.ST.',
        'S.T'         => 'S.T.',

        // PROFESI
        'Ir'          => 'Ir.',
        'Dr'          => 'Dr.',
        'Drs'         => 'Drs.',
        'Dra'         => 'Dra.',
        'Drg'         => 'drg.',
        'Dr.'         => 'Dr.',
        'dr'          => 'dr.',
        'Apt'         => 'Apt.',
        'Ners'        => 'Ners.',
        'Bidan'       => 'Bdn.',
        'Psikolog'    => 'Psikolog',

        // MAGISTER
        'M.Ag'        => 'M.Ag.',
        'M.E'         => 'M.E.',
        'M.Farm'      => 'M.Farm.',
        'M.H'         => 'M.H.',
        'M.Hum'       => 'M.Hum.',
        'M.Ked'       => 'M.Ked.',
        'M.Kes'       => 'M.Kes.',
        'M.Kom'       => 'M.Kom.',
        'M.M'         => 'M.M.',
        'M.Pd'        => 'M.Pd.',
        'M.Psi'       => 'M.Psi.',
        'M.Sc'        => 'M.Sc.',
        'M.Si'        => 'M.Si.',
        'M.Sn'        => 'M.Sn.',
        'M.T'         => 'M.T.',

        // DOKTOR / INTERNASIONAL
        'Ph.D'        => 'Ph.D',

        // KEAGAMAAN
        'H'           => 'H.',
        'Hj'          => 'Hj.',
        'KH'          => 'KH.',
        'K.H'         => 'KH.',
        'Habib'       => 'Habib',

        // KEGURUAN
        'Gr'          => 'Gr.',
        'Guru Besar'  => 'Guru Besar',
    ];

    /* =========================
     * VALIDATOR
     * ========================= */

    public static function validateUpdate(array $data): array
    {
        self::require($data, 'nama');

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
            'nama'          => self::normalizeName($data['nama']),
            'email'         => trim($data['email'] ?? null),
            'password'      => $data['password'] ?? null,
            'jenis_kelamin' => $data['jenis_kelamin'] ?? 'Tidak diketahui',
            'no_wa'         => trim($data['no_wa'] ?? null),
        ];
    }

    private static function normalizeName(string $name): string
    {
        $name = trim($name);

        // 1. Rapikan spasi
        $name = preg_replace('/\s+/', ' ', $name);

        // 2. Title Case untuk nama orang (aman)
        $name = mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');

        // 3. Normalisasi gelar (CASE-INSENSITIVE)
        foreach (self::DEGREE_MAP as $raw => $proper) {
            $pattern = '/\b' . preg_quote($raw, '/') . '\b/i';
            $name = preg_replace($pattern, $proper, $name);
        }

        return $name;
    }
}
