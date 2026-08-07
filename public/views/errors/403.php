<?php
http_response_code(403);
view('errors/page', [
    'statusCode' => 403,
    'title' => 'Akses Ditolak',
    'message' => 'Anda tidak memiliki izin untuk mengakses halaman ini.',
    'reason' => 'Role akun saat ini tidak sesuai dengan hak akses halaman yang diminta.',
    'icon' => 'fa-ban',
    'variant' => 'danger',
]);
?>
