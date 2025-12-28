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
    $r->get('/laporan', [PegawaiController::class, 'laporan']);
    $r->post('/laporan/store', [PegawaiLaporanController::class, 'create']);
    $r->post('/laporan/update/{id}', [PegawaiLaporanController::class, 'update']);
    $r->post('/laporan/delete/{id}', [PegawaiLaporanController::class, 'delete']);

    // Export
    $r->get('/laporan/export/pdf', [PegawaiLaporanController::class, 'exportPdf']);
    $r->get('/laporan/export/excel', [PegawaiLaporanController::class, 'exportExcel']);

    // Profil
    $r->get('/profil', [PegawaiController::class, 'profil']);
    $r->post('/profil/update', [PegawaiController::class, 'updateProfil']);
    $r->post('/profil/update-foto', [PegawaiController::class, 'updateFoto']);
}, ['auth', 'role:pegawai', 'csrf']);


// =======================================
// ADMIN
// =======================================
$router->group('/admin', function ($r) {

    // Dashboard
    $r->get('/dashboard', [AdminController::class, 'index']);

    // Kelola Laporan
    $r->get('/kelola/laporan', [AdminLaporanController::class, 'index']);
    $r->post('/kelola/laporan/create', [AdminLaporanController::class, 'create']);
    $r->post('/kelola/laporan/update/{id}', [AdminLaporanController::class, 'update']);
    $r->post('/kelola/laporan/delete/{id}', [AdminLaporanController::class, 'delete']);

    // Export Laporan
    $r->get('/kelola/laporan/export/pdf', [AdminLaporanController::class, 'exportPdf']);
    $r->get('/kelola/laporan/export/excel', [AdminLaporanController::class, 'exportExcel']);

    // Kelola Pegawai
    $r->get('/kelola/pegawai', [AdminPegawaiController::class, 'index']);
    $r->post('/kelola/pegawai/create', [AdminPegawaiController::class, 'create']);
    $r->post('/kelola/pegawai/update/{id}', [AdminPegawaiController::class, 'update']);
    $r->post('/kelola/pegawai/delete/{id}', [AdminPegawaiController::class, 'delete']);

    // Export Pegawai
    $r->get('/kelola/pegawai/export/pdf', [AdminPegawaiController::class, 'exportPdf']);
    $r->get('/kelola/pegawai/export/excel', [AdminPegawaiController::class, 'exportExcel']);

    // Import Pegawai
    $r->post('/kelola/pegawai/import', [AdminPegawaiController::class, 'importPegawai']);
}, ['auth', 'role:admin', 'csrf']);
