CREATE TABLE IF NOT EXISTS `jabatan` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_jabatan_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `jabatan` (`nama`, `is_active`) VALUES
('Arsiparis', 1),
('Administrasi Komite', 1),
('Bendahara Pengeluaran', 1),
('Guru BK', 1),
('Guru Mapel', 1),
('Kepala Madrasah', 1),
('Kepala Lab', 1),
('Kepala Perpustakaan', 1),
('Ka. Ur. TU', 1),
('Koordinator UKM', 1),
('Operator Madrasah', 1),
('Pengadministrasi', 1),
('Pranata Laboratorium Pendidikan', 1),
('Pengelola Administrasi Dan Dokumentasi', 1),
('Pengelola Sistem Administrasi Instansi', 1),
('Petugas UKM', 1),
('Pustakawan', 1),
('Penjaga Malam', 1),
('Satpam', 1),
('Tenaga Kebersihan', 1),
('Waka Kesiswaan', 1),
('Waka Kurikulum', 1),
('Waka Sarpras', 1);

INSERT IGNORE INTO `jabatan` (`nama`, `is_active`)
SELECT DISTINCT TRIM(`jabatan`) AS `nama`, 1
FROM `user`
WHERE `role` = 'pegawai'
  AND `jabatan` IS NOT NULL
  AND TRIM(`jabatan`) <> '';
