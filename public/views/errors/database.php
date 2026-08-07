<?php
http_response_code(503);
view('errors/page', [
    'statusCode' => 503,
    'title' => 'Database Tidak Terhubung',
    'message' => 'Sistem tidak dapat terhubung ke database untuk sementara waktu.',
    'reason' => 'Kemungkinan penyebab: server database mati, kredensial .env salah, nama database tidak tersedia, atau koneksi jaringan database bermasalah.',
    'icon' => 'fa-database',
    'variant' => 'danger',
]);
?>
