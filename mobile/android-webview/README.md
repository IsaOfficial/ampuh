# AMPUH Android WebView

APK WebView untuk aplikasi AMPUH production.

- URL: `https://ampuh.mtsn1jepara.sch.id`
- Nama aplikasi: `AMPUH`
- Package: `matsantura.ampuh`
- Version: `1.0.3` (`versionCode` 13)

## Fitur WebView

- Splash/loading screen awal dengan logo AMPUH.
- Progress bar halaman warna kuning di bagian atas.
- Pull-to-refresh dengan indikator tarik/lepas.
- Halaman error/offline native dengan tombol Coba Lagi dan Buka di Browser.
- Tombol Back kembali ke halaman sebelumnya; di halaman awal perlu tekan dua kali untuk keluar.
- Pengingat laporan harian setiap 2 jam pada pukul 10.00-22.00 jika pegawai belum mengirim laporan hari ini.
- Opsi ingat akun dan token login perangkat agar pegawai tidak perlu login ulang selama token masih aktif.
- Link luar domain AMPUH dibuka di browser eksternal.
- Download file diarahkan ke folder Download perangkat.
- Upload bukti mendukung kamera, gambar, PDF, dan video.

Catatan: pada Android 13 atau lebih baru, pengguna perlu mengizinkan notifikasi agar pengingat laporan dapat muncul. Akun dan token aplikasi disimpan di penyimpanan privat perangkat dan dienkripsi memakai Android Keystore.

## Build APK Debug

Jalankan dari folder project utama:

```powershell
.\mobile\android-webview\build-apk.ps1
```

Output APK:

```text
mobile/android-webview/build/AMPUH-debug.apk
```

APK debug ini bisa dipakai untuk uji instalasi langsung di perangkat Android. Untuk distribusi resmi/Play Store, buat signing key release sendiri dan sign ulang APK dengan key production.

## Build AAB Release untuk Google Play

Jalankan dari folder project utama:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\mobile\android-webview\build-playstore.ps1
```

Output yang diunggah ke Play Console:

```text
mobile/android-webview/playstore/AMPUH-release-v1.0.3-code13.aab
```

File penting yang harus disimpan aman:

```text
mobile/android-webview/keystore/ampuh-upload-key.jks
mobile/android-webview/release-signing.properties
```

Jangan commit atau menghapus dua file tersebut. Keduanya diperlukan untuk menandatangani update aplikasi berikutnya dengan upload key yang sama.
