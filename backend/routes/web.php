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

// =======================================
// PEGAWAI
// =======================================
$router->group('/pegawai', function ($r) {

    // Dashboard
    $r->get('/dashboard', [PegawaiController::class, 'dashboard']);

    // Laporan
    $r->get('/laporan', [PegawaiLaporanController::class, 'riwayatLaporan']);
    $r->post('/laporan/store', [PegawaiLaporanController::class, 'create']);
    $r->post('/laporan/update', [PegawaiLaporanController::class, 'update']);
    $r->post('/laporan/delete', [PegawaiLaporanController::class, 'delete']);

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
    $r->post('/kelola/laporan/create', [AdminLaporanController::class, 'create']);
    $r->post('/kelola/laporan/update', [AdminLaporanController::class, 'update']);
    $r->post('/kelola/laporan/delete', [AdminLaporanController::class, 'delete']);

    // Export Laporan
    $r->get('/kelola/laporan/export/pdf', [AdminLaporanExportController::class, 'exportPdf']);
    $r->get('/kelola/laporan/export/excel', [AdminLaporanExportController::class, 'exportExcel']);

    // Kelola Pegawai
    $r->get('/kelola/pegawai', [AdminPegawaiController::class, 'kelolaPegawai']);
    $r->post('/kelola/pegawai/create', [AdminPegawaiController::class, 'create']);
    $r->post('/kelola/pegawai/update', [AdminPegawaiController::class, 'update']);
    $r->post('/kelola/pegawai/delete', [AdminPegawaiController::class, 'delete']);

    // Export Pegawai
    $r->get('/kelola/pegawai/export/pdf', [AdminPegawaiExportController::class, 'exportPdf']);
    $r->get('/kelola/pegawai/export/excel', [AdminPegawaiExportController::class, 'exportExcel']);

    // Import Pegawai
    $r->post('/kelola/pegawai/import', [AdminPegawaiImportController::class, 'importPegawai']);
}, ['auth', 'role:admin', 'csrf']);
