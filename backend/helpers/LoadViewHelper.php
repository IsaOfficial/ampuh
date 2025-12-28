<?php

function view($path, $data = [])
{
    $file = __DIR__ . '/../../public/views/' . $path . '.php';

    if (!file_exists($file)) {
        throw new Exception("View '{$path}' tidak ditemukan!");
    }

    extract($data);
    require $file;
}
