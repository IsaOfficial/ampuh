<?php
http_response_code(401);
view('errors/page', [
    'statusCode' => 401,
    'title' => 'Sesi Login Diperlukan',
    'message' => 'Anda perlu login terlebih dahulu untuk membuka halaman ini.',
    'reason' => 'Sesi pengguna tidak ditemukan, sudah berakhir, atau belum valid.',
    'icon' => 'fa-user-lock',
    'variant' => 'warning',
]);
?>
