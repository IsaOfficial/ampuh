# Security Policy

AMPUH adalah aplikasi administrasi internal. Repository ini disiapkan untuk portofolio dan pengembangan source code, bukan untuk menyimpan data production.

## Data Sensitif

Jangan membuka issue atau pull request yang menyertakan:

- Credential database.
- File `.env`.
- Token aplikasi.
- Android keystore atau signing properties.
- Dump database production.
- Data pribadi pegawai.
- File bukti laporan asli.

## Pelaporan Kerentanan

Jika menemukan celah keamanan, laporkan secara privat kepada pemilik repository. Jangan membuat laporan publik yang berisi langkah eksploitasi, credential, atau data production.

## Praktik Minimum

- Gunakan `.env.example` sebagai template konfigurasi.
- Simpan `.env` hanya di server atau mesin lokal.
- Rotasi credential jika pernah terunggah ke Git.
- Jangan gunakan data production untuk screenshot portofolio.
- Jangan commit APK, AAB, atau signing key Android.
