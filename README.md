# Buku Induk SMKN 5 Surakarta — Starter Laravel 12

Sistem pencatatan Buku Induk & Data Siswa untuk SMKN 5 Surakarta.
Stack: **Laravel 12 + MySQL** (produksi/shared hosting) atau **SQLite** (coba cepat di laptop).
Frontend: Blade + Tailwind CSS via CDN (tanpa build Node). Responsif + PWA.

> Semua data contoh di paket ini **fiktif** dan ditandai contoh — bukan data siswa asli.
> Jangan pakai kredensial contoh di produksi; segera ganti setelah install.

## Fitur starter (Fase 1)

- **Halaman depan publik:** Beranda (hero, statistik, berita terbaru, pengumuman), Profil sekolah (Sejarah, Visi & Misi, Sambutan Kepsek, Struktur, Guru & Staff, Fasilitas, Kontak) — semua dari database, bisa diedit admin. Berita (daftar, detail slug, kategori, pencarian).
- **Admin khusus pegawai:** login saja, tanpa registrasi publik. Role: Admin TU (full CRUD), Kepala Sekolah (lihat + cetak), Guru (lihat terbatas).
- **Buku Induk:** pilih Tahun Ajaran → Jurusan/Kelas → Master Siswa → Data Nominatif (X/XI/XII/XIII) → Ledger Semester 1–8 → Transkrip → Data Sekolah → Cetak (print-to-PDF) & Export CSV.
- **Data Siswa:** CRUD biodata + orang tua/wali, riwayat kelas, dokumen, status Aktif/Lulus/Pindah/Keluar, pencarian NIS/NISN/nama, filter tahun/jurusan/kelas/status, pagination, import CSV, export CSV.
- **Berita & Pengumuman:** CRUD admin, draft/publish, foto, kategori.
- **Log aktivitas:** siapa mengubah data apa dan kapan.
- **PWA:** `manifest.json` + service worker sederhana — bisa di-install ke home screen HP.

## Akun contoh (SEEDER — ganti setelah install!)

Password akun contoh **tidak lagi hardcode** — isi lewat `.env`
(`SEED_ADMIN_PASSWORD` / `SEED_KEPSEK_PASSWORD` / `SEED_GURU_PASSWORD`).
Kalau kosong, seeder membuat password acak 12 karakter dan menampilkannya
sekali saat `db:seed` berjalan — catat saat itu juga.

| Role | Email |
|---|---|
| Admin TU | `admin@smkn5.sch.id` |
| Kepala Sekolah | `kepsek@smkn5.sch.id` |
| Guru | `guru@smkn5.sch.id` |

## Instalasi 5 menit — Windows (Laragon / XAMPP)

Persyaratan: PHP 8.2+, Composer, MySQL (Laragon/XAMPP sudah menyediakan keduanya).

```powershell
# 1. Masuk ke folder proyek
cd buku-induk-smkn5

# 2. Install dependensi PHP
composer install

# 3. Salin konfigurasi (pilih salah satu database di bawah)
copy .env.example .env

# 4. Generate kunci aplikasi
php artisan key:generate

# 5. Buat tabel + isi data contoh
php artisan migrate --seed

# 6. Buat link storage untuk foto/dokumen (opsional, untuk upload)
php artisan storage:link

# 7. Jalankan
php artisan serve
```

Buka `http://127.0.0.1:8000` — halaman depan langsung tampil dengan profil & berita contoh.
Login lewat tombol **Login Pegawai** di pojok kanan atas, atau langsung `http://127.0.0.1:8000/login`.

### Pilihan database di `.env`

**A. Coba cepat — SQLite (tanpa MySQL):**
```env
DB_CONNECTION=sqlite
DB_DATABASE=C:\laragon\www\buku-induk-smkn5\database\database.sqlite
```
Buat file kosong `database\database.sqlite` dulu (atau jalankan `php artisan migrate --seed` — Laravel 12 akan menanyakan dan membuatnya).

**B. Produksi / shared hosting — MySQL (disarankan):**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=buku_induk_smkn5
DB_USERNAME=root
DB_PASSWORD=
```
Buat database kosong `buku_induk_smkn5` dulu di phpMyAdmin, lalu `php artisan migrate --seed`.
Alternatif tanpa artisan: import file `schema.sql` langsung lewat phpMyAdmin (struktur MySQL yang sama dengan migrasi), lalu isi data contoh dengan `php artisan db:seed`.

## Instalasi di shared hosting (cPanel)

**Cara 1 (paling mudah) — seluruh proyek di `public_html`, tanpa `/public` di URL:**
Proyek ini sudah punya file `.htaccess` di folder root yang meneruskan semua
request ke `public/`, jadi tidak perlu memisah folder atau mengubah path.
1. Upload seluruh isi proyek ke `public_html/` (atau ke subfolder, mis. `public_html/buku-induk/`).
2. Buat database MySQL + user di cPanel → isi `DB_*` di `.env` (di hosting, edit file `.env` lewat File Manager).
3. Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://domain-sekolah.sch.id`.
4. Jalankan migrasi lewat Terminal cPanel / SSH: `php artisan migrate --seed --force`.
   Kalau tidak ada SSH: import `schema.sql` lewat phpMyAdmin, lalu jalankan seeder dari Terminal.
5. Pastikan folder `storage/` dan `bootstrap/cache/` bisa ditulis (permission 775).
6. Buka `https://domain-sekolah.sch.id` — langsung tampil tanpa `/public` di URL.
   (Syarat: Apache dengan `mod_rewrite` aktif — standar di cPanel, Laragon, dan XAMPP.)

**Cara 2 (alternatif lama) — pisah folder `public/`:**
1. Upload isi folder proyek ke hosting (di luar `public_html` bila memungkinkan), kecuali folder `public/` → isi `public/` di-upload ke `public_html/`.
2. Di `public/index.php` sesuaikan path ke `vendor/autoload.php` dan `bootstrap/app.php` bila lokasi folder berubah.
3. Lanjutkan langkah 2–5 di atas.

## Instalasi di Linux (Ubuntu)

```bash
sudo apt install php8.3-cli php8.3-mysql php8.3-sqlite3 php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath unzip git
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
cd buku-induk-smkn5 && composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed && php artisan serve
```

## Struktur folder penting

```
app/
  Http/Controllers/        PublicController, AuthController, Admin/*
  Http/Middleware/         CheckRole.php (pembatas role)
  Models/                  User, Siswa, TahunAjaran, Jurusan, Kelas, ...
bootstrap/app.php          Pendaftaran middleware + route
config/                    app, auth, database, session, cache, ...
database/
  migrations/              Semua tabel (lihat BLUEPRINT.md §5)
  factories/               SiswaFactory
  seeders/                 DatabaseSeeder + seeder per modul
public/
  index.php, manifest.json, sw.js, icons/, contoh-import-siswa.csv
resources/views/
  layouts/                 app.blade.php (publik), admin.blade.php
  public/                  home, profil, berita, pengumuman
  auth/login.blade.php
  admin/                   dashboard, siswa, buku-induk, berita, ...
routes/web.php             Semua route publik + admin
schema.sql                 Struktur MySQL (fallback phpMyAdmin)
BLUEPRINT.md               Arsitektur lengkap + ERD + roadmap
VERIFIKASI.md              Hasil verifikasi di mesin pembuat
```

## Perintah yang sering dipakai

```bash
php artisan migrate --seed        # install / reset data contoh
php artisan migrate:fresh --seed  # hapus semua tabel lalu isi ulang
php artisan route:list            # lihat semua route
php artisan test                  # jalankan test (halaman publik + login)
php artisan serve                 # server dev di http://127.0.0.1:8000
```

## Import data lama dari Google Drive (sudah dijalankan 2026-11-06)

Data lama Buku Induk (2013-2014 s.d. 2025-2026) sudah diimport dari folder Drive
`BUKU INDUK SMKN5` ke **`database/database-imported.sqlite`** — **6.548 siswa**,
12.624 record ledger, 740 transkrip. Rincian lengkap (agregat saja, tanpa data pribadi):
`LAPORAN_IMPORT.md`. Inventaris Drive: `INVENTARIS_DRIVE.md`.

```bash
php artisan buku-induk:import                  # import semua tahun
php artisan buku-induk:import --dry-run        # simulasi, DB tidak berubah
php artisan buku-induk:import --tahun=2019-2020
php artisan buku-induk:import --fresh          # buat ulang database-imported.sqlite
```

Logika import ada di `storage/imports/import_buku_induk.py` (Python + openpyxl), dibungkus
artisan command `app/Console/Commands/ImportBukuInduk.php`. Aturan utama: dedup berdasarkan
NIS; file MASTER duplikat hanya dipakai satu per jurusan per tahun; baris gagal dilaporkan
(dengan alasan), tidak pernah diam-diam; data yang sudah ada tidak dihapus tanpa `--fresh`.
Setiap file yang diimport dicatat ke tabel `activity_logs`.

> **Privasi:** `storage/imports/source/` dan `database-imported.sqlite` berisi data asli siswa.
> Jangan diunggah ke hosting publik / dibagikan tanpa izin sekolah. `database.sqlite` (data contoh
> fiktif) tidak berubah selama import; backup-nya di `database/database.sqlite.contoh.bak`.

## Format import CSV siswa

Contoh: `public/contoh-import-siswa.csv`. Header wajib:

```
nis,nisn,nama,jenis_kelamin,tempat_lahir,tanggal_lahir,agama,alamat,no_hp,tahun_ajaran,jurusan,kelas,status,nama_ayah,nama_ibu,no_hp_ortu
```

Aturan: `nis` wajib & unik; `nisn` unik bila diisi; `tanggal_lahir` format `YYYY-MM-DD`; `jenis_kelamin` = `L`/`P`; `status` = `aktif`/`lulus`/`pindah`/`keluar`; jurusan & kelas harus sudah ada di data master (menu Tahun Ajaran / Jurusan / Kelas). Baris gagal dilaporkan, tidak menggagalkan baris lain.

## Keamanan — baca sebelum dipakai di produksi

- Ganti semua password akun contoh, dan hapus akun yang tidak dipakai.
  Aplikasi belum punya menu ganti password untuk akun yang sudah ada, jadi
  ganti lewat Terminal Laragon (masuk folder proyek dulu), satu baris per akun
  — ganti `PASSWORD-BARU` dengan password masing-masing, minimal 8 karakter:
  `php artisan tinker --execute="App\Models\User::where('email','admin@smkn5.sch.id')->update(['password'=>bcrypt('PASSWORD-BARU')]);"`
  Ulangi untuk `kepsek@smkn5.sch.id` dan `guru@smkn5.sch.id`.
  Seeder baru di repo tidak mengubah password akun yang sudah terpasang.
- Set `APP_DEBUG=false` dan `APP_ENV=production` di `.env` produksi.
- Jangan commit `.env` ke Git. File ini sudah ada di `.gitignore`.
- Backup database berkala (mysqldump / fitur backup hosting).

## Pengembangan selanjutnya (Fase 2–3)

Lihat `BLUEPRINT.md` §9: import data MASTER/Dapodik nyata, wali kelas per Guru, upload dokumen, cetak PDF resmi (Dompdf), export Excel (PhpSpreadsheet), API + Flutter.
