# Blueprint — Sistem Buku Induk SMKN 5 Surakarta

> Dokumen perencanaan + arsitektur. Bahasa Indonesia.
> Status: Starter Fase 1 (siap dikembangkan). Semua data contoh di proyek ini **fiktif** dan ditandai contoh — bukan data siswa asli dari Drive.

## 1. Ringkasan proyek

| Item | Keputusan |
|---|---|
| Nama | Buku Induk SMKN 5 Surakarta |
| Stack | Laravel 12 + MySQL (produksi / shared hosting), SQLite untuk dev lokal |
| Frontend | Blade + Tailwind CSS via CDN (tanpa build Node), mobile-first |
| Akses | Web responsif + PWA (install ke home screen HP) |
| Area publik | Profil sekolah + Berita dinamis (input admin) |
| Area privat | Buku Induk & Data Siswa — hanya pegawai setelah login |
| Prinsip data | **Single source of truth**: satu siswa = satu record. Tidak ada lagi file MASTER duplikat. |

### Masalah yang diselesaikan

Referensi Drive `BUKU INDUK SMKN5` menunjukkan pola kerja lama:

- Satu folder per Tahun Ajaran: 2013-2014 s.d. 2021-2022, lalu 2025-2026. **Tidak ada folder 2022-2023, 2023-2024, 2024-2025.**
- Di dalam tiap tahun: file `MASTER*.xlsm` per jurusan (contoh nyata: `MASTER [RPL A].xlsm`, `MASTER-TEI.xlsm`), folder `DATA NOMINATIF SISWA` (file per Kelas XII / XIII), folder `LEDGER KELAS XII DAN XIII` (sub-folder Semester 1–8), `DATA-SEKOLAH.xlsx`, dan `DATABASE TRANSKRIP 2025-2026.xlsx`.
- Masalah nyata: **banyak file MASTER duplikat** — `MASTER.xlsm`, `MASTER-edit.xlsm`, `MASTER [RPL A].xlsm`, `MASTER (RPL A) edit.xlsm`, `MASTERrR.xlsm`. Tidak jelas mana yang paling baru, dan satu siswa bisa berbeda datanya di file yang berbeda.

Sistem ini menggantikan pola itu dengan satu database: jurusan, kelas, dan tahun ajaran jadi data master; siswa diinput sekali; nominatif, ledger, dan transkrip **dihitung/ditarik dari data yang sama**, bukan dari file terpisah.

## 2. Diagram alur

```
                          ┌─────────────────────────┐
                          │   PENGUNJUNG (publik)    │
                          │  Beranda / Profil /      │
                          │  Berita / Pengumuman     │
                          └────────────┬────────────┘
                                       │ tanpa login
                                       ▼
┌──────────────┐   ┌──────────────────────────────────────────────┐
│  ADMIN (TU)  │   │            LARAVEL 12 (Blade)                │
│  KEPSEK      │──▶│  Route publik  │  Route /admin (auth + role) │
│  GURU        │   │  Profil, Berita│  Siswa, Buku Induk, Ledger, │
│  (login)     │   │  Pengumuman    │  Transkrip, Berita, Profil  │
└──────────────┘   └──────────────┬───────────────────────────────┘
                                  │ Eloquent ORM
                                  ▼
                    ┌─────────────────────────────┐
                    │  MySQL (produksi) /          │
                    │  SQLite (dev lokal)          │
                    │  tabel siswa, ledger, dst.   │
                    └─────────────────────────────┘

Alur pegawai (harian):
  Login → Dashboard → pilih Tahun Ajaran → pilih Jurusan/Kelas
        → Master Siswa → Data Nominatif → Ledger per Semester
        → Transkrip → Cetak PDF / Export CSV

Alur admin berita:
  Login (Admin TU) → Berita → Tambah → isi judul/isi/foto
  → Draft / Publish → langsung tampil di halaman depan

Alur migrasi data lama (sekali, Fase 2):
  File MASTER .xlsm/.xlsx di Drive → export ke CSV per jurusan
  → Import CSV di menu Siswa → validasi NIS/NISN duplikat
  → data masuk sebagai satu record per siswa → file lama jadi arsip
```

## 3. Daftar menu

### 3.1 Menu publik (tanpa login)

| Menu | URL | Isi |
|---|---|---|
| Beranda | `/` | Hero, statistik (jumlah siswa aktif, guru, alumni), berita terbaru, pengumuman |
| Profil | `/profil` | Daftar halaman profil |
| Profil: Sejarah | `/profil/sejarah` | Halaman profil (slug) |
| Profil: Visi & Misi | `/profil/visi-misi` | Halaman profil (slug) |
| Profil: Sambutan Kepsek | `/profil/sambutan-kepala-sekolah` | Halaman profil (slug) |
| Profil: Struktur Organisasi | `/profil/struktur-organisasi` | Halaman profil (slug) |
| Profil: Guru & Staff | `/profil/guru-staff` | Halaman profil (slug) |
| Profil: Fasilitas | `/profil/fasilitas` | Halaman profil (slug) |
| Profil: Kontak | `/profil/kontak` | Halaman profil (slug) |
| Berita | `/berita` | Daftar berita (publish saja), pencarian, filter kategori |
| Detail Berita | `/berita/{slug}` | Isi berita lengkap |
| Pengumuman | `/pengumuman` | Daftar pengumuman aktif |

### 3.2 Menu admin — khusus pegawai (login, prefix `/admin`)

Navigasi desktop: sidebar kiri. Navigasi HP: menu bawah (Dashboard, Siswa, Buku Induk, Berita, Akun).

| Menu | URL | Keterangan |
|---|---|---|
| Dashboard | `/admin` | Ringkasan: total siswa aktif, siswa baru tahun berjalan, berita terbaru, log aktivitas terakhir |
| Tahun Ajaran | `/admin/tahun-ajaran` | CRUD tahun ajaran (2013-2014 s.d. 2025-2026 + tambah baru), set tahun aktif |
| Jurusan | `/admin/jurusan` | CRUD jurusan (RPL, TEI, dst.) |
| Kelas | `/admin/kelas` | CRUD kelas per jurusan & tingkat (X / XI / XII / XIII) |
| **Data Siswa** | `/admin/siswa` | Daftar + pencarian NIS/NISN/nama, filter tahun/jurusan/kelas/status, pagination |
| Tambah Siswa | `/admin/siswa/create` | Form biodata + orang tua/wali |
| Detail Siswa | `/admin/siswa/{id}` | Biodata, orang tua, riwayat kelas, ledger, dokumen, transkrip |
| Import Siswa | `/admin/siswa/import` | Upload CSV (contoh: `public/contoh-import-siswa.csv`) |
| Export Siswa | `/admin/siswa/export` | Download CSV (filter yang sedang aktif ikut terbawa) |
| **Buku Induk** | `/admin/buku-induk` | Pilih Tahun Ajaran → pilih Jurusan/Kelas → daftar induk |
| Data Nominatif | `/admin/buku-induk/nominatif?tahun_ajaran_id=&kelas_id=` | Nominatif per kelas (XII / XIII / X / XI), cetak & export |
| Ledger | `/admin/buku-induk/ledger` | Ledger per siswa per Semester 1–8 |
| Transkrip | `/admin/buku-induk/transkrip` | Database transkrip, cetak per siswa |
| Data Sekolah | `/admin/data-sekolah` | Satu baris identitas sekolah (dipakai di kop cetakan) |
| Berita | `/admin/berita` | CRUD berita + kategori, draft/publish, foto |
| Profil Sekolah | `/admin/profil` | CRUD halaman profil (sejarah, visi-misi, dst.) |
| Pengumuman | `/admin/pengumuman` | CRUD pengumuman |
| Pengguna | `/admin/pengguna` | CRUD akun pegawai + role (hanya Admin TU) |
| Log Aktivitas | `/admin/log` | Siapa mengubah data apa dan kapan (read-only) |

## 4. Role & hak akses

| Fitur | Admin TU | Kepala Sekolah | Guru |
|---|---|---|---|
| Lihat dashboard & data siswa | ✅ | ✅ | ✅ (terbatas*) |
| Tambah / edit / hapus siswa | ✅ | ❌ | ❌ |
| Import / export data | ✅ | ✅ export saja | ❌ |
| Cetak buku induk / nominatif / ledger / transkrip | ✅ | ✅ | ✅ (kelas sendiri) |
| Kelola berita / pengumuman / profil | ✅ | ❌ | ❌ |
| Kelola tahun ajaran / jurusan / kelas | ✅ | ❌ | ❌ |
| Kelola pengguna & role | ✅ | ❌ | ❌ |
| Lihat log aktivitas | ✅ | ✅ | ❌ |

\* Guru dibatasi di starter ini lewat policy sederhana: guru hanya melihat data (tidak bisa ubah). Pembatasan per kelas wali (guru hanya melihat kelas yang dia ampu) direncanakan di Fase 2 — perlu tabel penugasan wali kelas.

**Implementasi:** kolom `role` di tabel `users` (`admin_tu`, `kepala_sekolah`, `guru`), middleware `role:admin_tu` / `role:admin_tu,kepala_sekolah`, dan Gate/policy untuk aksi ubah. Tidak ada registrasi publik: akun hanya dibuat Admin TU dari menu Pengguna. Login di `/login`.

## 5. Struktur database (ERD)

```
tahun_ajaran 1───* kelas ───* siswa
jurusan      1───* kelas
jurusan      1───* siswa (denormalisasi ringan via kelas)
siswa        1───1 orang_tua
siswa        1───* riwayat_kelas
siswa        1───* ledger (unique: siswa + semester)
siswa        1───* transkrip (unique: siswa + tahun_ajaran)
siswa        1───* dokumen_siswa
users        1───* activity_logs
berita       *───1 kategori_berita
berita       *───1 users (penulis)
profil_sekolah (pages, keyed by slug, tanpa relasi)
data_sekolah (satu baris, id = 1)
pengumuman (tanpa relasi wajib)
```

### 5.1 Daftar tabel + kolom + index

**users** — akun pegawai
| Kolom | Tipe | Ket. |
|---|---|---|
| id | bigint PK | |
| name | varchar(255) | Nama pegawai |
| email | varchar(255) unique | Login |
| password | varchar(255) | bcrypt |
| role | enum(admin_tu, kepala_sekolah, guru) | default `guru` |
| nip | varchar(30) nullable | NIP pegawai |
| is_active | boolean default true | Akun nonaktif tidak bisa login |
| email_verified_at, remember_token, timestamps | standar Laravel | |
| Index: `email` (unique), `role` | | |

**tahun_ajaran**
| Kolom | Tipe | Ket. |
|---|---|---|
| id | bigint PK | |
| nama | varchar(20) unique | contoh: `2025-2026` |
| is_active | boolean default false | Hanya satu yang aktif |
| tanggal_mulai / tanggal_selesai | date nullable | |
| timestamps | | |
| Index: `nama` (unique), `is_active` | | |

**jurusan**
| Kolom | Tipe | Ket. |
|---|---|---|
| id | bigint PK | |
| kode | varchar(10) unique | `RPL`, `TEI` |
| nama | varchar(100) | `Rekayasa Perangkat Lunak` |
| deskripsi | text nullable | |
| timestamps | | |

**kelas**
| Kolom | Tipe | Ket. |
|---|---|---|
| id | bigint PK | |
| tahun_ajaran_id | FK → tahun_ajaran | |
| jurusan_id | FK → jurusan | |
| tingkat | enum(X, XI, XII, XIII) | |
| nama | varchar(50) | `XII RPL A` |
| wali_kelas | varchar(100) nullable | Nama wali kelas |
| timestamps | | |
| Index: `(tahun_ajaran_id, jurusan_id, tingkat)`, unique `(tahun_ajaran_id, jurusan_id, nama)` | | |

**siswa** — tabel inti (single source of truth)
| Kolom | Tipe | Ket. |
|---|---|---|
| id | bigint PK | |
| nis | varchar(20) unique | **Nomor Induk Siswa — wajib, unik** |
| nisn | varchar(20) unique nullable | Nomor Induk Siswa Nasional — unik bila diisi |
| nama | varchar(150) | |
| jenis_kelamin | enum(L, P) | |
| tempat_lahir | varchar(100) nullable | |
| tanggal_lahir | date nullable | |
| agama | varchar(30) nullable | |
| alamat | text nullable | |
| no_hp | varchar(20) nullable | |
| tahun_ajaran_id | FK → tahun_ajaran | Tahun masuk |
| jurusan_id | FK → jurusan | |
| kelas_id | FK → kelas nullable | Kelas saat ini |
| status | enum(aktif, lulus, pindah, keluar) default `aktif` | |
| tanggal_masuk | date nullable | |
| tanggal_keluar | date nullable | Diisi bila pindah/keluar/lulus |
| keterangan | text nullable | Alasan pindah/keluar |
| foto | varchar(255) nullable | Path file foto |
| timestamps | | |
| **Index: `nis` (unique), `nisn` (unique), `nama`, `(tahun_ajaran_id, jurusan_id, status)`, `(kelas_id, status)`** | | |

**orang_tua** (1 siswa : 1 baris; dipisah supaya biodata siswa tetap ramping)
| Kolom | Tipe | Ket. |
|---|---|---|
| id | bigint PK | |
| siswa_id | FK → siswa, unique, cascade delete | |
| nama_ayah | varchar(150) nullable | |
| pekerjaan_ayah | varchar(100) nullable | |
| nama_ibu | varchar(150) nullable | |
| pekerjaan_ibu | varchar(100) nullable | |
| nama_wali | varchar(150) nullable | |
| pekerjaan_wali | varchar(100) nullable | |
| no_hp_ortu | varchar(20) nullable | |
| alamat_ortu | text nullable | |
| timestamps | | |

**riwayat_kelas**
| Kolom | Tipe | Ket. |
|---|---|---|
| id | bigint PK | |
| siswa_id | FK → siswa, cascade delete | |
| kelas_id | FK → kelas | |
| tahun_ajaran_id | FK → tahun_ajaran | |
| status_naik | enum(naik, tinggal, pindah_masuk, lulus) nullable | |
| catatan | varchar(255) nullable | |
| timestamps | | |
| Index: `(siswa_id, tahun_ajaran_id)` | | |

**ledger** — nilai/rekap per semester (pengganti folder `LEDGER ... SEMESTER 1–8`)
| Kolom | Tipe | Ket. |
|---|---|---|
| id | bigint PK | |
| siswa_id | FK → siswa, cascade delete | |
| tahun_ajaran_id | FK → tahun_ajaran | |
| semester | tinyint (1–8) | |
| rata_rata | decimal(5,2) nullable | |
| peringkat | integer nullable | |
| catatan | text nullable | |
| timestamps | | |
| Index: unique `(siswa_id, semester, tahun_ajaran_id)` | | |

**transkrip** — pengganti `DATABASE TRANSKRIP`
| Kolom | Tipe | Ket. |
|---|---|---|
| id | bigint PK | |
| siswa_id | FK → siswa, cascade delete | |
| tahun_ajaran_id | FK → tahun_ajaran | |
| nomor_transkrip | varchar(50) unique nullable | |
| tanggal_terbit | date nullable | |
| keterangan | text nullable | |
| timestamps | | |
| Index: unique `(siswa_id, tahun_ajaran_id)` | | |

**dokumen_siswa**
| Kolom | Tipe | Ket. |
|---|---|---|
| id | bigint PK | |
| siswa_id | FK → siswa, cascade delete | |
| jenis | enum(akta, kk, ijazah, foto, lain) | |
| nama_file | varchar(255) | |
| path | varchar(255) | Path di `storage/app/public/dokumen` |
| timestamps | | |
| Index: `(siswa_id, jenis)` | | |

**data_sekolah** — satu baris (id = 1)
| Kolom | Tipe | Ket. |
|---|---|---|
| id | bigint PK | |
| nama_sekolah | varchar(150) | `SMK Negeri 5 Surakarta` |
| npsn | varchar(20) nullable | |
| alamat | text nullable | |
| telepon / email / website | varchar nullable | |
| kepala_sekolah | varchar(150) nullable | Nama Kepsek aktif |
| logo | varchar(255) nullable | |
| timestamps | | |

**profil_sekolah** — halaman profil dinamis (satu baris per slug)
| Kolom | Tipe | Ket. |
|---|---|---|
| id | bigint PK | |
| slug | varchar(100) unique | `sejarah`, `visi-misi`, `sambutan-kepala-sekolah`, `struktur-organisasi`, `guru-staff`, `fasilitas`, `kontak` |
| judul | varchar(200) | |
| isi | longtext | HTML sederhana dari textarea admin |
| gambar | varchar(255) nullable | |
| urutan | integer default 0 | Urutan di menu profil |
| is_active | boolean default true | |
| timestamps | | |
| Index: `slug` (unique) | | |

**kategori_berita**
| Kolom | Tipe | Ket. |
|---|---|---|
| id | bigint PK | |
| nama | varchar(100) unique | `Pengumuman`, `Kegiatan`, `Prestasi`, `PPDB` |
| slug | varchar(100) unique | |
| timestamps | | |

**berita**
| Kolom | Tipe | Ket. |
|---|---|---|
| id | bigint PK | |
| kategori_berita_id | FK → kategori_berita nullable | |
| user_id | FK → users (penulis) | |
| judul | varchar(255) | |
| slug | varchar(255) unique | Dibuat otomatis dari judul |
| ringkasan | text nullable | |
| isi | longtext | |
| foto | varchar(255) nullable | |
| status | enum(draft, publish) default `draft` | Hanya `publish` tampil di publik |
| published_at | datetime nullable | |
| views | integer default 0 | |
| timestamps | | |
| Index: `slug` (unique), `(status, published_at)`, `kategori_berita_id` | | |

**pengumuman**
| Kolom | Tipe | Ket. |
|---|---|---|
| id | bigint PK | |
| judul | varchar(255) | |
| isi | text | |
| tanggal_mulai / tanggal_selesai | date nullable | |
| is_active | boolean default true | |
| timestamps | | |
| Index: `(is_active, tanggal_mulai)` | | |

**activity_logs**
| Kolom | Tipe | Ket. |
|---|---|---|
| id | bigint PK | |
| user_id | FK → users nullable | |
| aksi | varchar(100) | `siswa.create`, `siswa.update`, `berita.publish`, … |
| model_type / model_id | varchar / bigint nullable | Objek yang diubah |
| keterangan | text nullable | |
| ip_address | varchar(45) nullable | |
| timestamps (created_at saja) | | |
| Index: `(user_id, created_at)`, `(model_type, model_id)` | | |

## 6. Alur import dari file MASTER lama

1. Di Excel, buka file `MASTER [RPL A].xlsm` → **Save As → CSV** (satu file per jurusan/kelas). Jangan mengubah file aslinya.
2. Samakan header CSV dengan contoh `public/contoh-import-siswa.csv`: `nis,nisn,nama,jenis_kelamin,tempat_lahir,tanggal_lahir,agama,alamat,no_hp,tahun_ajaran,jurusan,kelas,status,nama_ayah,nama_ibu,no_hp_ortu`.
3. Di aplikasi: **Data Siswa → Import** → pilih Tahun Ajaran & Jurusan → upload CSV.
4. Sistem validasi per baris: NIS wajib & unik, NISN unik bila diisi, tanggal lahir format `YYYY-MM-DD`, jurusan/kelas harus sudah ada di data master.
5. Baris gagal **tidak menghentikan** baris lain — hasilnya dilaporkan: "24 berhasil, 2 gagal" + daftar baris gagal dan alasannya, supaya bisa diperbaiki dan di-upload ulang.
6. Duplikat NIS dari file MASTER yang berbeda **ditolak** — inilah yang menghilangkan masalah duplikat di Drive. Satu NIS hanya boleh satu record.
7. Setelah import bersih, file Excel lama dipindah ke folder arsip dan tidak lagi dipakai sebagai sumber data.

## 7. Rencana migrasi data Drive → MySQL

| Tahap | Pekerjaan |
|---|---|
| 1. Inventaris | Daftarkan semua file MASTER per tahun & jurusan; tentukan file mana yang paling baru bila ada duplikat (cek `modifiedTime` di Drive). |
| 2. Bersihkan | Gabungkan duplikat per jurusan; satu NIS satu baris; lengkapi NISN yang kosong dari Dapodik bila ada. |
| 3. Lengkapi tahun kosong | Buat folder/tahun 2022-2023, 2023-2024, 2024-2025 di aplikasi (di Drive saat ini belum ada) dan isi dari Dapodik / arsip TU. |
| 4. Import bertahap | Import per tahun ajaran, mulai dari 2025-2026 (paling baru), lalu mundur. Verifikasi jumlah siswa per kelas dengan Data Nominatif. |
| 5. Ledger & transkrip | Import ledger per semester (1–8) dan transkrip setelah data siswa bersih — keduanya memakai `siswa_id`, jadi tidak bisa jalan sebelum siswa ada. |
| 6. Arsip | File Excel lama tetap disimpan (read-only) sebagai arsip, bukan sumber data. |

## 8. Keamanan

- Login wajib untuk semua `/admin/*`; tidak ada registrasi publik.
- Password di-hash (bcrypt); akun contoh **wajib diganti** setelah install.
- Semua query lewat Eloquent (prepared statement) — aman dari SQL injection.
- Upload hanya gambar/dokumen dengan validasi mime & ukuran; file disimpan di `storage/app/public`, bukan di folder web yang bisa dieksekusi.
- `APP_DEBUG=false` di produksi; `.env` tidak boleh di-commit.
- Backup: dump MySQL berkala + backup folder `storage`. Di shared hosting, pakai fitur backup terjadwal dari panel hosting bila ada.

## 9. Roadmap

**Fase 1 — Starter (paket ini)**
Laravel 12 + migrasi + model + seeder contoh + route publik & admin + Blade responsif + PWA + login + CRUD siswa/berita/profil + pencarian/filter/pagination + export CSV + import CSV + cetak via print.

**Fase 2 — Migrasi data nyata**
Import data MASTER/Dapodik per tahun ajaran, verifikasi jumlah per kelas, lengkapi tahun 2022–2025, penugasan wali kelas (Guru hanya melihat kelasnya), upload dokumen siswa (akta/KK/ijazah), cetak PDF resmi buku induk (Dompdf), export Excel asli (PhpSpreadsheet).

**Fase 3 — Pengembangan**
Aplikasi mobile native (Flutter) memakai API Laravel yang sama, notifikasi pengumuman, sinkronisasi Dapodik, dashboard Kepsek (grafik per angkatan), dan audit log lengkap.

## 10. Cara menjalankan (ringkas)

Lihat `README.md`. Intinya (5 menit, Windows + Laragon/XAMPP):
1. `composer install`
2. Salin `.env.example` → `.env`, atur database (SQLite untuk coba cepat, MySQL untuk produksi)
3. `php artisan key:generate && php artisan migrate --seed`
4. `php artisan serve` → buka `http://127.0.0.1:8000`
5. Login `admin@smkn5.sch.id` / `password` (contoh — segera ganti)

## 11. File di paket ini

- `BLUEPRINT.md` — dokumen ini
- `README.md` — instalasi langkah-demi-langkah + kredensial contoh
- `VERIFIKASI.md` — hasil verifikasi di mesin pembuat (jujur: apa yang teruji, apa yang belum)
- `schema.sql` — struktur MySQL untuk import langsung via phpMyAdmin
- `composer.json`, `.env.example`, `artisan`, `bootstrap/`, `config/`, `public/`, `routes/`, `app/`, `database/`, `resources/` — starter Laravel 12
- `public/contoh-import-siswa.csv` — contoh format import
- `public/manifest.json`, `public/sw.js`, `public/icons/` — PWA
