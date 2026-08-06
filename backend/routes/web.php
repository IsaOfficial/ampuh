<?php

// =======================================
// AUTH
// =======================================
$router->get('/', function () {
    header('Location: /login');
    exit;
});

$router->get('/login', function () {
    view('auth/login');
});

$router->post('/login', [AuthController::class, 'processLogin'])
    ->middleware('csrf');

$router->get('/logout', [AuthController::class, 'logout'])
    ->middleware('auth');

$router->get('/verifikasi-laporan', [VerificationController::class, 'verify']);

// =======================================
// PEGAWAI
// =======================================
$router->group('/pegawai', function ($r) {

    // Dashboard
    $r->get('/dashboard', [PegawaiController::class, 'dashboard']);

    // Laporan
    $r->get('/laporan', [PegawaiLaporanController::class, 'riwayatLaporan']);
    $r->get('/laporan/data', [PegawaiLaporanController::class, 'data']);
    $r->get('/laporan/sampah', [PegawaiLaporanController::class, 'sampahLaporan']);
    $r->get('/laporan/sampah/data', [PegawaiLaporanController::class, 'trashData']);
    $r->post('/laporan/store', [PegawaiLaporanController::class, 'create']);
    $r->post('/laporan/update', [PegawaiLaporanController::class, 'update']);
    $r->post('/laporan/delete', [PegawaiLaporanController::class, 'delete']);
    $r->post('/laporan/restore', [PegawaiLaporanController::class, 'restore']);
    $r->post('/laporan/bulk-process', [PegawaiLaporanController::class, 'bulkProcess']);

    // Export
    $r->get('/laporan/export/pdf', [PegawaiLaporanExportController::class, 'exportPdf']);
    $r->get('/laporan/export/excel', [PegawaiLaporanExportController::class, 'exportExcel']);

    // Profil
    $r->get('/profil', [PegawaiProfilController::class, 'profil']);
    $r->post('/profil/update', [PegawaiProfilController::class, 'updateProfil']);
    $r->post('/profil/update-foto', [PegawaiProfilController::class, 'updateFoto']);
}, ['auth', 'role:pegawai', 'csrf']);


// =======================================
// ADMIN
// =======================================
$router->group('/admin', function ($r) {

    // Dashboard
    $r->get('/dashboard', [AdminDashboardController::class, 'dashboard']);

    // Kelola Laporan
    $r->get('/kelola/laporan', [AdminLaporanController::class, 'kelolaLaporan']);
    $r->get('/kelola/laporan/data', [AdminLaporanController::class, 'data']);
    $r->get('/kelola/laporan/sampah', [AdminLaporanController::class, 'sampahLaporan']);
    $r->get('/kelola/laporan/sampah/data', [AdminLaporanController::class, 'trashData']);
    $r->post('/kelola/laporan/create', [AdminLaporanController::class, 'create']);
    $r->post('/kelola/laporan/update', [AdminLaporanController::class, 'update']);
    $r->post('/kelola/laporan/delete', [AdminLaporanController::class, 'delete']);
    $r->post('/kelola/laporan/restore', [AdminLaporanController::class, 'restore']);
    $r->post('/kelola/laporan/force-delete', [AdminLaporanController::class, 'forceDelete']);
    $r->post('/kelola/laporan/bulk-process', [AdminLaporanController::class, 'bulkProcess']);

    // Export Laporan
    $r->get('/kelola/laporan/export/pdf', [AdminLaporanExportController::class, 'exportPdf']);
    $r->get('/kelola/laporan/export/excel', [AdminLaporanExportController::class, 'exportExcel']);

    // Kelola Pegawai
    $r->get('/kelola/pegawai', [AdminPegawaiController::class, 'kelolaPegawai']);
    $r->get('/kelola/pegawai/data', [AdminPegawaiController::class, 'data']);
    $r->get('/kelola/pegawai/sampah/data', [AdminPegawaiController::class, 'trashData']);
    $r->post('/kelola/pegawai/create', [AdminPegawaiController::class, 'create']);
    $r->post('/kelola/pegawai/update', [AdminPegawaiController::class, 'update']);
    $r->post('/kelola/pegawai/delete', [AdminPegawaiController::class, 'delete']);
    $r->post('/kelola/pegawai/restore', [AdminPegawaiController::class, 'restore']);
    $r->post('/kelola/pegawai/force-delete', [AdminPegawaiController::class, 'forceDelete']);
    $r->post('/kelola/pegawai/bulk-process', [AdminPegawaiController::class, 'bulkProcess']);

    // Export Pegawai
    $r->get('/kelola/pegawai/export/pdf', [AdminPegawaiExportController::class, 'exportPdf']);
    $r->get('/kelola/pegawai/export/excel', [AdminPegawaiExportController::class, 'exportExcel']);

    // Import Pegawai
    $r->post('/kelola/pegawai/import', [AdminPegawaiImportController::class, 'importPegawai']);
}, ['auth', 'role:admin', 'csrf']);
