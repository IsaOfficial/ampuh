<?php
http_response_code(500);
view('errors/page', [
    'statusCode' => 500,
    'title' => 'Terjadi Kesalahan Sistem',
    'message' => 'Sistem sedang mengalami kendala saat memproses permintaan.',
    'reason' => 'Terjadi error di sisi aplikasi/server. Silakan coba beberapa saat lagi atau hubungi admin.',
    'icon' => 'fa-tools',
    'variant' => 'danger',
]);
?>
