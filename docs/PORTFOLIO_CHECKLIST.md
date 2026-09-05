# Portfolio Checklist

Checklist ini digunakan sebelum repository dipublikasikan sebagai portofolio.

## Aman Dipublikasikan

- Source code aplikasi web.
- Source Android WebView.
- `.env.example`.
- File schema dan migration database tanpa data pribadi.
- Screenshot fitur yang sudah disensor atau tidak memuat data sensitif.
- Dokumentasi setup, fitur, dan keamanan.

## Jangan Dipublikasikan

- `.env` production atau lokal.
- Dump database production berisi data pegawai.
- File upload asli pegawai.
- File APK/AAB release.
- Android keystore.
- `release-signing.properties`.
- Backup zip hosting.
- Log error yang memuat path, token, session, atau credential.

## Pemeriksaan Sebelum Commit

Jalankan:

```powershell
git status --short
git check-ignore -v mobile/android-webview/keystore/ampuh-upload-key.jks mobile/android-webview/release-signing.properties
git check-ignore -v mobile/android-webview/playstore/AMPUH-release-v1.0.3-code13.aab mobile/android-webview/build/AMPUH-debug.apk
```

Pastikan file sensitif tetap terdeteksi oleh `.gitignore`.

## Catatan Data Demo

Repository ini belum menyertakan seed data demo. Jika ingin membuat demo publik, gunakan data dummy penuh dan jangan mengambil nama, NIP, jabatan, foto, bukti, atau laporan dari production.

## Catatan Dependency

Sebagian asset vendor frontend masih berada di repository karena mengikuti pola deploy aplikasi saat ini. Jika repository ingin dibuat lebih ramping, dependency frontend dapat dipindahkan ke package manager atau CDN pada fase cleanup terpisah.
