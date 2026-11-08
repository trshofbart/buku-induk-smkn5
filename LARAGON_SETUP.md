# Buku Induk SMKN 5 — Cara Jalan di Laragon (Windows)

Paket ini sudah siap untuk Laragon: kode lengkap + `vendor/` sudah termasuk,
jadi **tidak perlu `composer install`**. Yang perlu disiapkan hanya database MySQL-nya.

## Syarat

- Laragon sudah terpasang (https://laragon.org), dengan **PHP 8.2 atau lebih baru**.
  Cek di Laragon: Menu > PHP > Version — pilih 8.2 / 8.3.
- MySQL di Laragon sudah jalan (biasanya otomatis jalan saat Laragon Start).

## Langkah 1 — Extract ke Laragon

1. Extract file `buku-induk-smkn5-laragon.zip` ke:
   `C:\laragon\www\buku-induk-smkn5`
2. Di Laragon, klik **Stop** lalu **Start All** (atau klik kanan > Reload Apache).
   Laragon otomatis membuat alamat: **http://buku-induk-smkn5.test**
   (tanpa `/public` — Laragon mengarahkan docroot langsung ke `public/`).

> Catatan: kalau proyek dibuka lewat `http://localhost/buku-induk-smkn5`
> (bukan lewat `.test`), URL tanpa `/public` juga tetap jalan — ada file
> `.htaccess` di folder root proyek yang meneruskan semua request ke
> `public/`. File yang sama membuat proyek bisa diletakkan langsung di
> `public_html` shared hosting tanpa `/public` di URL (lihat README,
> bagian "Instalasi di shared hosting").

## Langkah 2 — Buat database

1. Di Laragon, klik **Database** (membuka HeidiSQL) — login sebagai `root` tanpa password.
2. Buat database baru bernama: `buku_induk_smkn5`
   (atau lewat Terminal Laragon: `mysql -u root -e "CREATE DATABASE buku_induk_smkn5 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"`)

## Langkah 3 — Import data (pilih salah satu)

**A. Dengan data asli hasil import (6.548 siswa):**
Import file `buku_induk_smkn5_data.sql` (sudah dikirim terpisah ke Telegram)
lewat HeidiSQL / phpMyAdmin ke database `buku_induk_smkn5`.

**B. Dengan data contoh saja (24 siswa fiktif, untuk coba-coba):**
Di Terminal Laragon, masuk ke folder proyek lalu jalankan:
```
cd C:\laragon\www\buku-induk-smkn5
php artisan migrate --seed
```
File `schema.sql` (struktur saja, tanpa data) juga ada di dalam paket ini.

## Langkah 4 — Buka di browser

Buka **http://buku-induk-smkn5.test**

- Halaman depan: Profil + Berita (publik).
- Login pegawai: klik **Login Pegawai** di kanan atas.
  Akun contoh — passwordnya dari `.env` (`SEED_*_PASSWORD`) atau acak yang
  ditampilkan sekali saat `db:seed`; **segera ganti setelah masuk**:
  - Admin TU: `admin@smkn5.sch.id`
  - Kepala Sekolah: `kepsek@smkn5.sch.id`
  - Guru: `guru@smkn5.sch.id`

## Yang sudah diatur di `.env` paket ini

- `APP_URL=http://buku-induk-smkn5.test`
- `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`
- `DB_DATABASE=buku_induk_smkn5`, `DB_USERNAME=root`, `DB_PASSWORD=` (kosong — default Laragon)
- `APP_KEY` sudah terisi.

Kalau MySQL Laragon kamu pakai password root yang berbeda, ubah `DB_PASSWORD` di file `.env`.

## Kalau ada masalah

| Gejala | Periksa |
|---|---|
| `buku-induk-smkn5.test` tidak bisa dibuka | Laragon sudah Start All? Coba `Menu > Apache > Reload`. Periksa juga file `C:\Windows\System32\drivers\etc\hosts` sudah ada baris `127.0.0.1 buku-induk-smkn5.test` (Laragon menambahkannya otomatis). |
| Error koneksi database | MySQL Laragon jalan? Database `buku_induk_smkn5` sudah dibuat? `DB_PASSWORD` di `.env` sesuai password root MySQL kamu? |
| Error `500` / halaman kosong | Jalankan di Terminal Laragon: `php artisan config:clear` lalu `php artisan cache:clear`. Pastikan PHP 8.2+. |
| Perlu data contoh ulang | `php artisan migrate:fresh --seed` (PERHATIAN: menghapus semua data di database itu). |

## Isi paket ini

- Kode Laravel 12 lengkap + `vendor/` (tanpa `composer install`).
- `.env` sudah diatur untuk Laragon/MySQL.
- `schema.sql` — struktur MySQL saja.
- `BLUEPRINT.md`, `README.md`, `VERIFIKASI.md`, `INVENTARIS_DRIVE.md`, `LAPORAN_IMPORT.md`.
- Importer: `storage/imports/import_buku_induk.py` + artisan command `buku-induk:import`.
- **Tidak termasuk:** file Excel sumber dari Drive dan database SQLite berisi data asli
  (file data SQL dikirim terpisah karena berisi data asli siswa).
