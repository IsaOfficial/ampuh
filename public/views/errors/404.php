<?php
http_response_code(404);
view('errors/page', [
    'statusCode' => 404,
    'title' => 'Halaman Tidak Ditemukan',
    'message' => 'Alamat yang Anda buka tidak tersedia atau sudah dipindahkan.',
    'reason' => 'Route atau URL tidak terdaftar pada sistem AMPUH.',
    'icon' => 'fa-search',
    'variant' => 'warning',
]);
?>
