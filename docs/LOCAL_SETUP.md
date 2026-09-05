# Local Setup

Panduan ini dipakai untuk menjalankan AMPUH di lingkungan lokal.

## Prasyarat

- PHP 8.x
- Composer
- MySQL atau MariaDB
- Web server lokal seperti XAMPP, Laragon, atau PHP built-in server

## Langkah Instalasi

1. Clone repository.

```powershell
git clone https://github.com/IsaOfficial/ampuh.git
cd ampuh
```

2. Install dependency PHP.

```powershell
composer install
```

3. Buat file `.env`.

```powershell
Copy-Item .env.example .env
```

4. Sesuaikan konfigurasi database di `.env`.

```env
APP_URL=http://localhost:8000
APP_DEBUG=true

DB_HOST=localhost
DB_DATABASE=ampuh
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4

SESSION_LIFETIME_DAYS=30
```

5. Import struktur database.

Import schema utama dan migration terbaru dari folder `database/` sesuai urutan tanggal file.

6. Jalankan server lokal.

```powershell
php -S localhost:8000 index.php
```

7. Buka aplikasi.

```text
http://localhost:8000
```

## Catatan Upload

Folder upload asli production tidak disertakan di repository. Repository hanya menyimpan `.gitkeep` dan konfigurasi keamanan folder upload.

Pastikan folder berikut tetap dapat ditulis oleh web server:

- `public/uploads/bukti`
- `public/uploads/foto`

## Android WebView

Source Android WebView tersedia di:

```text
mobile/android-webview
```

Build debug:

```powershell
powershell -ExecutionPolicy Bypass -File .\mobile\android-webview\build-apk.ps1
```

Build release AAB:

```powershell
powershell -ExecutionPolicy Bypass -File .\mobile\android-webview\build-playstore.ps1
```

Keystore, signing properties, APK, dan AAB tidak boleh dimasukkan ke repository publik.
