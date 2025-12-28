<?php

class DateHelper
{
    private static array $hari = [
        'Sunday'    => 'Minggu',
        'Monday'    => 'Senin',
        'Tuesday'   => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday'  => 'Kamis',
        'Friday'    => 'Jumat',
        'Saturday'  => 'Sabtu',
    ];

    private static array $bulan = [
        'January'   => 'Januari',
        'February'  => 'Februari',
        'March'     => 'Maret',
        'April'     => 'April',
        'May'       => 'Mei',
        'June'      => 'Juni',
        'July'      => 'Juli',
        'August'    => 'Agustus',
        'September' => 'September',
        'October'   => 'Oktober',
        'November'  => 'November',
        'December'  => 'Desember',
    ];

    public static function hariIndo(string $tanggal): string
    {
        $hariEn = date('l', strtotime($tanggal));
        return self::$hari[$hariEn] ?? $hariEn;
    }

    public static function tanggalIndo(string $tanggal): string
    {
        $day   = date('d', strtotime($tanggal));
        $bulan = date('F', strtotime($tanggal));
        $tahun = date('Y', strtotime($tanggal));

        $bulanIndo = self::$bulan[$bulan] ?? $bulan;

        return "{$day} {$bulanIndo} {$tahun}";
    }

    public static function hariTanggalIndo(string $tanggal): string
    {
        return self::hariIndo($tanggal) . ', ' . self::tanggalIndo($tanggal);
    }
}
