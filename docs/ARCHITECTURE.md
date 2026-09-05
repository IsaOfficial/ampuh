# Architecture Overview

AMPUH menggunakan struktur PHP native dengan pola MVC sederhana. Fokus arsitekturnya adalah memisahkan routing, controller, model, service upload, dan template halaman agar fitur administrasi tetap mudah dipelihara.

## Alur Request

```text
Browser / Android WebView
        |
        v
index.php
        |
        v
backend/core/router.php
        |
        v
Controller
        |
        +--> Model
        |       |
        |       v
        |     MySQL
        |
        +--> Service
        |
        v
public/views
```

## Modul Utama

- `backend/routes/web.php`: definisi route aplikasi.
- `backend/controllers`: logika request untuk auth, admin, pegawai, API, dan verifikasi.
- `backend/models`: query database dan akses data.
- `backend/services`: logika pendukung seperti upload dan kompresi file.
- `backend/middleware`: validasi CSRF, role, dan akses.
- `public/views`: template halaman web.
- `public/assets`: CSS, JavaScript, gambar, dan vendor frontend.
- `database`: schema dan migration SQL.
- `mobile/android-webview`: aplikasi Android WebView native.

## Konsep Data

Entitas utama sistem:

- User atau pegawai.
- Jabatan.
- Laporan harian.
- Kegiatan laporan.
- Bukti kegiatan.
- Status approval.
- Token login aplikasi Android.
- Token reminder laporan.

Approval dilakukan pada level kegiatan agar satu laporan harian dapat berisi beberapa kegiatan dengan status yang berbeda.

## Verifikasi Dokumen

Setiap kegiatan yang disahkan memiliki kode verifikasi. Kode tersebut dapat dicek melalui halaman verifikasi publik atau QR Code pada hasil cetak PDF/Excel.

## Upload Bukti

Upload mendukung:

- Gambar.
- PDF.
- Video.

File tertentu dikompresi otomatis untuk menghemat penyimpanan. File upload production tidak disimpan di repository.

## Android WebView

Aplikasi Android WebView diarahkan ke URL production dan menambahkan fitur native:

- Splash screen.
- Progress bar halaman.
- Pull-to-refresh.
- Upload bukti dari kamera, video, atau file manager.
- Remember login.
- Reminder laporan harian.

Build artifact dan signing key Android dikecualikan dari Git.
