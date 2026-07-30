# AMPUH Android WebView

APK WebView untuk aplikasi AMPUH production.

- URL: `https://ampuh.mtsn1jepara.sch.id`
- Nama aplikasi: `AMPUH`
- Package: `ampuh.mtsn1jepara`

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
