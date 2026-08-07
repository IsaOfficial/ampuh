<?php
http_response_code(505);
view('errors/page', [
    'statusCode' => 505,
    'title' => 'Versi HTTP Tidak Didukung',
    'message' => 'Permintaan tidak dapat diproses karena versi protokol HTTP tidak didukung.',
    'reason' => 'Browser, proxy, atau server mengirim versi HTTP yang tidak kompatibel dengan aplikasi.',
    'icon' => 'fa-network-wired',
    'variant' => 'danger',
]);
?>
