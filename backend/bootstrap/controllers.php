<?php
require_once __DIR__ . '/../controllers/VerificationController.php';
require_once __DIR__ . '/../controllers/ApiControllers/ReminderStatusController.php';

// Auth
require_once __DIR__ . '/../controllers/AuthControllers/AuthController.php';
require_once __DIR__ . '/../controllers/AuthControllers/AppLoginController.php';

// Pegawai
require_once __DIR__ . '/../controllers/PegawaiControllers/PegawaiDashboardController.php';
require_once __DIR__ . '/../controllers/PegawaiControllers/PegawaiLaporanController.php';
require_once __DIR__ . '/../controllers/PegawaiControllers/PegawaiProfilController.php';
require_once __DIR__ . '/../controllers/PegawaiControllers/PegawaiLaporanExportController.php';
require_once __DIR__ . '/../controllers/PegawaiControllers/PegawaiReminderController.php';

// Admin
require_once __DIR__ . '/../controllers/AdminControllers/AdminDashboardController.php';
require_once __DIR__ . '/../controllers/AdminControllers/AdminLaporanController.php';
require_once __DIR__ . '/../controllers/AdminControllers/AdminLaporanExportController.php';
require_once __DIR__ . '/../controllers/AdminControllers/AdminPegawaiController.php';
require_once __DIR__ . '/../controllers/AdminControllers/AdminPegawaiExportController.php';
require_once __DIR__ . '/../controllers/AdminControllers/AdminPegawaiImportController.php';
require_once __DIR__ . '/../controllers/AdminControllers/AdminJabatanController.php';
