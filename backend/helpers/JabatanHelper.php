<?php

class JabatanHelper
{
    public static function list(bool $activeOnly = true): array
    {
        $fromDatabase = self::fromDatabase($activeOnly);

        return $fromDatabase ?: self::defaults();
    }

    public static function listForSelection(?string $current = null): array
    {
        $list = self::list(true);
        $current = trim((string) $current);

        if ($current !== '' && !in_array($current, $list, true)) {
            $list[] = $current;
            sort($list, SORT_NATURAL | SORT_FLAG_CASE);
        }

        return $list;
    }

    private static function fromDatabase(bool $activeOnly): array
    {
        try {
            $db = Database::getConnection();
            $sql = 'SELECT nama FROM jabatan';

            if ($activeOnly) {
                $sql .= ' WHERE is_active = 1';
            }

            $sql .= ' ORDER BY nama ASC';
            $stmt = $db->prepare($sql);
            $stmt->execute();

            return array_values(array_filter(array_map('strval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'nama'))));
        } catch (Throwable $e) {
            error_log('JabatanHelper fallback: ' . $e->getMessage());
            return [];
        }
    }

    private static function defaults(): array
    {
        return [
            'Arsiparis',
            'Administrasi Komite',
            'Bendahara Pengeluaran',
            'Guru BK',
            'Guru Mapel',
            'Kepala Madrasah',
            'Kepala Lab',
            'Kepala Perpustakaan',
            'Ka. Ur. TU',
            'Koordinator UKM',
            'Operator Madrasah',
            'Pengadministrasi',
            'Pranata Laboratorium Pendidikan',
            'Pengelola Administrasi Dan Dokumentasi',
            'Pengelola Sistem Administrasi Instansi',
            'Petugas UKM',
            'Pustakawan',
            'Penjaga Malam',
            'Satpam',
            'Tenaga Kebersihan',
            'Waka Kesiswaan',
            'Waka Kurikulum',
            'Waka Sarpras',
        ];
    }
}
