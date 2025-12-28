<?php

// Auth service
require_once __DIR__ . '/../services/Auth/AuthService.php';

// Upload services
require_once __DIR__ . '/../services/Upload/ImageUploadService.php';
require_once __DIR__ . '/../services/Upload/DocumentUploadService.php';

// Admin services
require_once __DIR__ . '/../services/User/Admin/AdminService.php';
require_once __DIR__ . '/../services/User/Admin/AdminPegawaiService.php';
require_once __DIR__ . '/../services/User/Admin/ImportPegawaiService.php';

// Pegawai services
require_once __DIR__ . '/../services/User/Pegawai/PegawaiService.php';

// Laporan services
require_once __DIR__ . '/../services/Laporan/LaporanService.php';
require_once __DIR__ . '/../services/Laporan/LaporanExportService.php';
