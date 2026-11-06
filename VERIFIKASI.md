# Verifikasi — Buku Induk SMKN 5 Surakarta (Starter)

Dokumen ini mencatat hasil verifikasi **jujur** di mesin pembuat (VM Ubuntu 24.04, 6 Nov 2026).
Yang belum terverifikasi di sini harus diverifikasi di mesin Bart sebelum dipakai produksi.

## Ringkasan

| Pemeriksaan | Hasil |
|---|---|
| Struktur file lengkap | ✅ 127 file proyek + folder `vendor/` (hasil `composer install`) |
| Konsistensi statis (route ↔ view ↔ model) | ✅ Dicek dengan skrip Python: tidak ada view/model yang hilang; semua nama route yang dipakai ada (termasuk yang dihasilkan `Route::resource`) |
| Keseimbangan kurung di file PHP | ✅ Tidak ada yang tidak seimbang |
| `php -l` (lint sintaks PHP) | ✅ **75 file, 0 gagal** |
| `composer install` | ✅ **Berhasil (exit 0)** — Laravel 12 + dependensi, `vendor/` 65 MB |
| `php artisan key:generate` | ✅ Berhasil |
| `php artisan migrate --seed` (SQLite) | ✅ **Berhasil (exit 0)** setelah 2 bug diperbaiki (lihat bawah) |
| `php artisan route:list` | ✅ **63 route** terdaftar |
| `php artisan test` | ✅ **8 test lolos (23 assertions)** — 6 Feature + 2 Unit |
| Uji HTTP langsung (`php artisan serve` + curl) | ✅ **Semua lolos** — lihat bawah |

## Catatan pergantian VM saat verifikasi

Di tengah verifikasi, VM diganti oleh platform (uptime kembali ke 1 menit; `/tmp` dan `/etc` hilang, termasuk PHP yang baru di-install). Folder `~/workspace/buku-induk-smkn5/` **tidak hilang**: seluruh kode, `vendor/`, `.env`, dan `database/database.sqlite` (24 siswa, 3 user, 5 berita publish) utuh.

PHP lalu di-install ulang dengan cara yang sama (REINSTALL_EXIT: 0, PHP 8.3.6) dan **seluruh verifikasi diulang pada kondisi file terakhir**: `php artisan test` tetap **8 passed (23 assertions)**, `route:list` tetap **63 route**, `GET /` tetap **200**, `GET /admin` tanpa login tetap **302**. Kode tidak berubah sejak test pertama yang lolos — yang berubah hanya file `VERIFIKASI.md` ini.

## Catatan instalasi PHP di VM ini

- PHP belum terpasang di VM ini. Percobaan pertama `apt-get update` dengan dua mirror + file terjemahan berjalan sangat lambat (±433 MB daftar paket, >13 menit) sehingga dihentikan.
- Percobaan kedua: mirror dipangkas ke `azure.archive.ubuntu.com` saja dan `Acquire::Languages "none"` — update selesai dalam ±12 detik (26,7 MB, UPDATE_EXIT: 0). Instalasi paket PHP 8.3 + ekstensi berhasil (INSTALL_EXIT: 0).
- Hasil akhir: **PHP 8.3.6** + ekstensi `mbstring, xml, curl, zip, sqlite3, pdo_sqlite, pdo_mysql, gd, intl, bcmath, openssl`. Composer diunduh dari getcomposer.org (composer.phar, /tmp — tidak ikut dalam paket).
- Catatan: konfigurasi apt yang diubah (`/etc/apt/sources.list.d/ubuntu.sources` dan `/etc/apt/apt.conf.d/99-nolang`) adalah perubahan di VM ini saja, bukan bagian dari paket.

## Bug yang ditemukan saat verifikasi — dan sudah diperbaiki

1. **Nama tabel model vs migrasi tidak cocok.** Model `Jurusan`, `Kelas`, `Siswa`, `Ledger`, `Transkrip` tidak mendeklarasikan `$table`, sehingga Laravel mencari tabel Inggris (`jurusans`, `siswas`, …) padahal migrasi membuat tabel Indonesia (`jurusan`, `siswa`, …). Seeder gagal di `JurusanSeeder` (`no such table: jurusans`). **Perbaikan:** menambahkan `protected $table = '...'` ke 5 model tersebut. Setelah itu `migrate:fresh --seed` berhasil.
2. **Base Controller tidak ada.** `route:list` gagal dengan `Class "App\Http\Controllers\Controller" not found` — skeleton Laravel 12 tidak menyertakan `app/Http/Controllers/Controller.php`, padahal semua controller mewarisinya. **Perbaikan:** menambahkan file `app/Http/Controllers/Controller.php` (kelas abstrak kosong, seperti bawaan skeleton Laravel 12).

## Hasil `migrate:fresh --seed` (SQLite)

Semua 10 seeder jalan (exit 0). Isi database hasil seed (dihitung langsung dari `database/database.sqlite`):

| Tabel | Jumlah |
|---|---|
| users | 3 (admin, kepsek, guru) |
| tahun_ajaran | 13 (2013-2014 s.d. 2025-2026) |
| jurusan | 4 (RPL, TEI + TKJ, TSM contoh) |
| kelas | 20 |
| siswa | 24 (20 aktif, 4 lulus) |
| orang_tua | 24 |
| riwayat_kelas | 24 |
| ledger | 72 (semester 1–2 siswa aktif, 1–8 lulusan) |
| transkrip | 4 |
| berita | 6 (5 publish, 1 draft) |
| kategori_berita | 4 |
| profil_sekolah | 7 |
| pengumuman | 3 |
| data_sekolah | 1 |
| activity_logs | 0 (baru terisi saat ada aktivitas login/ubah data) |
| Total tabel | 25 |

## Hasil `php artisan test` — 8 passed, 23 assertions

- Halaman depan tampil dengan berita contoh ✅
- Halaman berita dan profil tampil ✅
- Halaman admin wajib login (redirect ke /login) ✅
- Login admin contoh berhasil, dashboard tampil ✅
- Guru tidak bisa membuat siswa (403) ✅
- Pencarian siswa berdasarkan NIS ✅
- Slug berita dibuat dari judul dan unik ✅
- Satu NIS hanya boleh satu record (unique constraint) ✅

## Hasil uji HTTP langsung (`php artisan serve` port 8123 + curl)

| Uji | Hasil |
|---|---|
| `GET /` | **200**, 11.201 byte — tampil "SMK Negeri 5 Surakarta" dan judul berita contoh |
| `GET /berita` | **200** |
| `GET /profil/sejarah` | **200** |
| `GET /admin` tanpa login | **302 → /login** ✅ |
| `GET /login` | **200** |
| `POST /login` (admin@smkn5.sch.id / password) | **302 → /admin** ✅ |
| `GET /admin` setelah login | **200**, tampil "Dashboard" ✅ |
| `GET /admin/siswa?q=20250001` | **200** ✅ |

## Catatan tentang isi paket yang diserahkan

- Folder `vendor/` (65 MB) **ikut disertakan** supaya Bart bisa langsung `php artisan serve` tanpa `composer install` dulu. Bila mau bersih, hapus `vendor/` lalu jalankan `composer install` — `composer.json` + `composer.lock` sudah ada.
- File `.env` sudah terisi konfigurasi SQLite + `APP_KEY` hasil generate, dan `database/database.sqlite` sudah berisi data contoh hasil seed — jadi aplikasi langsung jalan. Untuk MySQL, ubah blok `DB_*` di `.env` sesuai README, lalu `php artisan migrate:fresh --seed`.
- File `/tmp/apt-install.log` dan `/tmp/composer-install.log` adalah log mesin verifikasi, **tidak** ikut dalam paket.

## Yang sudah diperiksa tanpa PHP

1. **File lengkap:** `find . -type f` = 126 file (lihat README.md untuk struktur).
2. **Route:** 42 nama route eksplisit + route hasil `Route::resource` (siswa, berita, pengumuman). Semua `route('...')` yang dipakai di Blade/controller ada pasangannya.
3. **View:** 30 `view('...')` dari controller — semua file Blade-nya ada.
4. **Model:** semua `use App\Models\...` di controller ada filenya.
5. **Migrasi:** 18 file migrasi, urutan foreign key benar (tahun_ajaran → jurusan → kelas → siswa → orang_tua/riwayat/ledger/transkrip/dokumen; berita butuh users + kategori_berita yang dibuat lebih dulu).
6. **Seeder:** data contoh fiktif — 13 tahun ajaran, 4 jurusan (RPL & TEI nyata dari Drive; TKJ & TSM ditandai contoh), 20 kelas, 24 siswa, 6 berita (5 publish + 1 draft), 3 pengumuman, 7 halaman profil, 1 baris data sekolah, ledger semester 1–2 (siswa aktif) / 1–8 (lulusan), 4 transkrip.

## Cara memverifikasi di mesin Bart (5 menit)

```bash
cd buku-induk-smkn5
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan route:list
php artisan test
php artisan serve
# Buka http://127.0.0.1:8000 — login admin@smkn5.sch.id / password
```

Yang diharapkan bila berhasil:
- `migrate --seed` membuat semua tabel dan mengisi: 3 user, 13 tahun ajaran, 4 jurusan, 20 kelas, 24 siswa, 6 berita.
- `route:list` menampilkan ±60 route (publik + admin).
- `test` menampilkan 8 test lolos (6 Feature + 2 Unit).

## Yang belum terverifikasi

- Uji klik manual di browser (tampilan Blade, form CRUD, import CSV, cetak) — baru diuji lewat HTTP/curl dan test otomatis, belum diklik satu per satu di browser.
- Import `schema.sql` via phpMyAdmin (ditulis dari migrasi yang sama; belum diuji import langsung — migrasi artisan-nya sendiri sudah teruji).
- PWA install di HP (manifest + service worker sudah ada, belum diuji di perangkat nyata).
- Koneksi MySQL (yang teruji di sini adalah SQLite; konfigurasi MySQL ada di `.env.example` dan `schema.sql`, tetapi belum dijalankan terhadap server MySQL sungguhan).
