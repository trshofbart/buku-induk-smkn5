# Laporan Import — Buku Induk SMKN 5 Surakarta

Tanggal import: **2026-11-06**
Sumber: folder Drive **BUKU INDUK SMKN5** (https://drive.google.com/drive/folders/1koLuVX7WAo-e9eJXy2bQ4BdLrK63YPaj)
Inventaris lengkap: `INVENTARIS_DRIVE.md` (598 file, 463 spreadsheet, 155 MB)
Importer: `storage/imports/import_buku_induk.py` (+ artisan command `buku-induk:import`)
Database hasil import: **`database/database-imported.sqlite`** (4,7 MB)
Database contoh (tidak berubah): `database/database.sqlite` (backup: `database/database.sqlite.contoh.bak`)

> **PERINGATAN PRIVASI — BACA DULU**
> File sumber di `storage/imports/source/` dan `database-imported.sqlite` berisi **data asli siswa**
> (nama, NIS/NISN, tanggal lahir, alamat, orang tua).
> **Jangan** diunggah ke hosting publik, jangan dikirim ke pihak luar, jangan disertakan dalam zip/file
> yang diserahkan secara umum tanpa izin sekolah.
> Yang boleh dibagikan: laporan ini (hanya angka agregat), kode importer, dan `INVENTARIS_DRIVE.md`.
> Laporan ini **tidak memuat satu pun nama, NIS/NISN, alamat, atau tanggal lahir siswa asli**.

## Ringkasan hasil

| | Jumlah |
|---|---|
| Siswa berhasil diimport (unik, dedup NIS) | **6.548** |
| Baris sumber dibaca | 11.278 |
| Duplikat NIS di-skip (sudah ada) | 1.597 |
| Baris diperkaya (NISN/TTL/orang tua ditambah ke record yang sudah ada) | 2.588 |
| Siswa dipindah ke tahun kelulusan (dokumen nominatif/transkrip) | 71 |
| Baris gagal (lihat rincian) | **13** |
| Record orang tua (`orang_tua`) | 5.842 |
| Record ledger rata-rata semester (`ledger`) | 12.624 |
| Record transkrip (`transkrip`, nomor ijazah) | 740 |
| Record riwayat kelas (`riwayat_kelas`) | 6.619 |
| Status siswa | semua `lulus` (6.548) |

Verifikasi: `php artisan test` → **8 passed (23 assertions)**. Database contoh (`database.sqlite`)
tidak berubah (md5 sama sebelum/sesudah import). Spot-check 8 record acak (nama + tanggal lahir + JK)
cocok 8/8 dengan file sumber. Cakupan NIS: 6.714 dari 6.715 NIS unik sumber ada di DB
(1 NIS hilang: gagal karena jenis kelamin kosong di sumber — lihat "Baris gagal").

## Jumlah siswa per tahun ajaran × jurusan (isi DB akhir)

Tahun ajaran siswa = tahun folder file sumber saat siswa pertama kali ditemukan,
kecuali 71 siswa yang dipindah ke tahun kelulusannya oleh dokumen NOMINATIF/TRANSKRIP
(lihat "Aturan dedup & tahun ajaran" di bawah).

| Tahun ajaran | RPL | TEI | TITL | TOI | TKRO | TBSM | TPM | TO | TS | DPIB | BKP | TKP | TGB | TKB | TKK | Total |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| 2013-2014 | 105 | 89 | 89 | – | – | – | 133 | 96 | – | – | – | – | 30 | 30 | 28 | **600** |
| 2014-2015 | 70 | 100 | 93 | – | – | – | 132 | 106 | – | – | – | – | 31 | 24 | 24 | **580** |
| 2015-2016 | 92 | 91 | 85 | – | – | – | 123 | 89 | 81 | – | – | – | – | – | – | **561** |
| 2016-2017 | 93 | 88 | 82 | – | – | – | 127 | 88 | 92 | – | – | – | 1 | – | – | **571** |
| 2017-2018 | 96 | 95 | 95 | – | – | – | 128 | 96 | 96 | – | – | – | – | – | – | **606** |
| 2018-2019 | 96 | 64 | 64 | 96 | – | – | 96 | 95 | 96 | – | – | – | – | – | – | **607** |
| 2019-2020 | 103 | 107 | 107 | 68 | 108 | 36 | 144 | – | 105 | – | – | – | – | – | – | **778** |
| 2020-2021 | 95 | 96 | 101 | 4 | 106 | 36 | 140 | – | 106 | – | – | – | – | – | – | **684** |
| 2021-2022 | 102 | 103 | 103 | 134 | 106 | 33 | 138 | – | – | 68 | 34 | – | – | – | – | **821** |
| 2025-2026 | 99 | 102 | 65 | 68 | 105 | 58 | 141 | – | – | 67 | – | 35 | – | – | – | **740** |
| **Total** | **951** | **935** | **884** | **370** | **425** | **163** | **1.302** | **570** | **576** | **135** | **34** | **35** | **62** | **54** | **52** | **6.548** |

Catatan per baris:
- **2013-2014 s.d. 2017-2018:** jurusan memakai kode lama (TIK→RPL, TE→TEI, TL→TITL, TM→TPM;
  lihat "Pemetaan jurusan"). TGB/TKB/TKK hanya ada di 2013-2015 (program lama, lalu hilang dari file).
- **2016-2017 TGB = 1:** satu siswa dari sheet `siswa16-17 yg blm ambil ijasah` (rombel XII TGB);
  5 siswa baru dari file non-REVISI (lainnya sudah ada di REVISI).
- **2019-2020:** TO hilang (diganti TKRO + TBSM); sheet TO di file ini adalah salinan basi dari
  2018-2019 (96 NIS sama persis) → didedup ke 2018-2019.
- **2020-2021 TOI = 4:** 65 siswa TOI file ini adalah kohort XIII yang dipindah ke 2021-2022
  oleh DATA NOMINATIF XIII (aturan pindah tahun kelulusan); 4 siswa tersisa tidak ada di nominatif.
- **2021-2022:** satu-satunya tahun dengan DPIB (68) dan BKP (34) — jurusan baru yang tidak muncul
  di file DATA SISWA tahun-tahun sebelumnya (hanya muncul di DATA MERGER 2020/2021 sebagai sheet).
- **2025-2026:** dari DATABASE TRANSKRIP (kohort lulus 2026). Nama sheet "TK" di file transkrip
  adalah nama **program** (Teknik Ketenagalistrikan); siswanya tercatat sebagai TITL sesuai kolom
  KELAS (`XII TITL A/B`) dan Konsentrasi Keahlian (`Teknik Instalasi Tenaga Listrik`) — jadi tidak
  ada jurusan `TK` tersendiri di DB. Jurusan TKP = `Teknik Konstruksi dan Perumahan` (sesuai kolom
  Konsentrasi Keahlian; KELAS `XII TKP`).

## Per file sumber (dibaca / dibuat / diperkaya / duplikat / pindah / gagal / ledger / transkrip)

| File sumber | Dibaca | Dibuat | Diperkaya | Duplikat | Pindah thn | Gagal | Ledger | Transkrip |
|---|---|---|---|---|---|---|---|---|
| 2013-2014 / DB DATA SISWA.xlsx | 600 | 600 | 0 | 0 | 0 | 0 | 0 | 0 |
| 2014-2015 / DB DATA SISWA 1415.xlsx | 581 | 580 | 0 | 0 | 0 | 1 | 0 | 0 |
| 2015-2016 / DATA SISWA 1516.xlsx | 561 | 561 | 0 | 0 | 0 | 0 | 0 | 0 |
| 2016-2017 / DATA SISWA 1617 REVISI.xlsx | 566 | 566 | 0 | 0 | 0 | 0 | 0 | 0 |
| 2016-2017 / DATA SISWA 1617 (non-REVISI, Format C) | 1.034 | 5 | 553 | 475 | 0 | 1 | 0 | 0 |
| 2017-2018 / DATA SISWA 1718.xlsx | 607 | 606 | 0 | 1 | 0 | 0 | 0 | 0 |
| 2018-2019 / DATA SISWA 1819.xlsx | 607 | 607 | 0 | 0 | 0 | 0 | 0 | 0 |
| 2018-2019 / KELAS X.xlsx | 607 | 0 | 607 | 0 | 0 | 0 | 0 | 0 |
| 2019-2020 / DATA SISWA 1920.xlsx | 878 | 780 | 0 | 97 | 0 | 1 | 0 | 0 |
| 2019-2020 / KELAS X.xlsx | 783 | 1 | 727 | 54 | 0 | 1 | 0 | 0 |
| 2019-2020 / DATA MERGER 2020.xlsx | 732 | 0 | 34 | 697 | 0 | 1 | 4.383 | 0 |
| 2020-2021 / DATA SISWA 2021.xlsx | 756 | 752 | 0 | 4 | 0 | 0 | 0 | 0 |
| 2020-2021 / DATA MERGER 2021.xlsx | 770 | 0 | 745 | 22 | 0 | 3 | 4.251 | 0 |
| 2020-2021 / MASTER.xlsx | 143 | 0 | 96 | 3 | 0 | 3 | 6 | 0 |
| 2021-2022 / DATA NOMINATIF XII | 758 | 751 | 0 | 0 | 5 | 2 | 0 | 0 |
| 2021-2022 / DATA NOMINATIF XIII (TOI) | 65 | 0 | 0 | 0 | 65 | 0 | 0 | 0 |
| 2021-2022 / MASTER-TEI.xlsm | 252 | 0 | 33 | 3 | 0 | 0 | 216 | 0 |
| 2021-2022 / MASTER [RPL A].xlsm | 238 | 0 | 0 | 34 | 0 | 0 | 68 | 0 |
| 2025-2026 / DATABASE TRANSKRIP 2025-2026.xlsx | 740 | 739 | 0 | 0 | 1 | 0 | 3.700 | 740 |
| **TOTAL** | **11.278** | **6.548** | **2.588** | **1.597** | **71** | **13** | **12.624** | **740** |

Keterangan kolom: **Dibaca** = baris data yang diproses (baris template/kosong/judul tidak dihitung).
**Dibuat** = siswa baru. **Diperkaya** = siswa sudah ada, field kosong dilengkapi (NISN, tanggal lahir,
orang tua). **Duplikat** = siswa sudah ada, tidak ada data baru. **Pindah thn** = tahun ajaran siswa
diperbarui ke tahun dokumen kelulusan. **Ledger** = rata-rata nilai per semester yang ditambahkan.
**Transkrip** = record transkrip (nomor ijazah) yang ditambahkan.

## Pemetaan kolom Excel → tabel database

### Sumber biodata utama (per jenis file)

| Jenis file | Baris header | Kolom kunci (contoh) | Dipetakan ke |
|---|---|---|---|
| DATA SISWA (Format A, 37 kol) | baris 1 | NO, NIS, NAMA SISWA, TEMPAT LAHIR, TGL/BL/TH LHR, JENIS KELAMIN, NAMA BAPAK/IBU, PEKERJAAN ORANG TUA, ALAMAT SISWA, AGAMA, HP SISWA, HP ORTU, NAMA WALI, SEKOLAH ASAL, Diterima di kelas, Diterima tgl | `siswa` + `orang_tua` |
| DATA SISWA (Format B, 75–76 kol; sheet TIK & TM 2014-2015) | baris 1 (TIK) / baris 8 (TM) | No Induk, NISN, Nama Siswa, L/P, Tempat, Tgl/Bulan/Tahun Lahir, Agama, Alamat, Nomor Telepon, Nama Ayah/Ibu/Wali (+ tempat/tgl lahir, pendidikan, pekerjaan, penghasilan), Tgl. Meninggalkan Sekolah, Alasan, Tamat Belajar, STTB/STL | `siswa` + `orang_tua`; status `keluar`/`pindah` bila ada Tgl. Meninggalkan Sekolah tanpa Tamat Belajar |
| KELAS X (Format C, 16–17 kol) | baris 8 | No Induk, NISN, Nama Siswa, L/P, Tempat, Tgl. Lahir, Agama, Nama Orang Tua/Wali, Pekerjaan, Alamat, DK/LK, Asal Sekolah, No KK, NIK, No Tlp/HP | pengayaan NISN + TTL + orang tua; `siswa` baru hanya bila NIS belum ada |
| DATA MERGER (Format D, ~152 kol) | baris 1 | NIS, NISN, NAMA SISWA, ROMBEL, TEMPAT_LHR, TGL/BL/TH_LHR, nilai mapel `SUBJEK_1`..`SUBJEK_6` | pengayaan NISN/TTL + `ledger` rata-rata semester 1–6 (tidak membuat siswa baru: file ini tidak memuat jenis kelamin) |
| MASTER.xlsx BIODATA (24 kol) | baris 2 | NIS, NAMA_SISWA, JURUSAN, TAHUN MASUK, JENIS KELAMIN, AGAMA, TEMPAT_LHR, TGL_LHR, BAPAK, IBU, WALI, ALAMAT, NO.HP, ASAL SEKOLAH | pengayaan; LEGGER → `ledger` semester 1–6 |
| MASTER*.xlsm BIODATA (21 kol) | baris 2 | NIS, NISN, NAMA_SISWA, JK, TTL, AGAMA, BAPAK, IBU, PEKERJAAN, DESA/RT/RW/KELURAHAN/KECAMATAN/KOTA, ASAL SEKOLAH, NO HP, ALAMAT | pengayaan; SMSTR 1–6 → `ledger` (rata-rata mapel kolom R; bila kosong dipakai kolom N/P/K) |
| DATA NOMINATIF XII/XIII (50 kol) | baris 6 | NIS, NISN, Nama, Rombel Saat Ini, JK, Tempat/Tanggal Lahir, Agama, NIK, Alamat (+RT/RW/Dusun/Kelurahan/Kecamatan/Kab/Kota/Kode Pos), Telepon, HP, E-Mail, SKHUN, Data Ayah/Ibu/Wali, No Peserta UN, No Seri Ijazah, KIP, No Registrasi Akta Lahir, Bank, No Rekening, Sekolah Asal, Anak ke-berapa, No KK, Jarak Rumah | `siswa` + `orang_tua` + `kelas` (rombel dari nama sheet) |
| DATABASE TRANSKRIP 2025-2026 (84 kol) | baris 1 | KELAS, NAMA LENGKAP, Program/Konsentrasi Keahlian, Tempat Tanggal Lahir, NIS, NISN, Nomor Ijazah, Nama Ortu, JK, Agama, nilai `SUBJEK_1`..`SUBJEK_5`, `SUBJEK_RATA2`, PKL, RATA2 | `siswa` (lulus) + `transkrip` (nomor ijazah) + `ledger` semester 1–5 |

Kolom yang **tidak** diimport karena tidak ada kolomnya di skema saat ini: NIK, No KK, No Peserta UN,
No Seri Ijazah (selain sebagai nomor transkrip), KIP/KKS, No Rekening Bank, koordinat Lintang/Bujur,
Berat/Tinggi/Lingkar Kepala, Jarak Rumah. Bila sekolah membutuhkan, skema perlu ditambah dulu.

### Pemetaan jurusan (kode sheet/kolom → kode kanonik)

| Kode kanonik | Nama | Alias / kode lama yang dipetakan ke sini |
|---|---|---|
| RPL | Rekayasa Perangkat Lunak | TIK |
| TEI | Teknik Elektronika Industri | TE |
| TITL | Teknik Instalasi Tenaga Listrik | TL, T LISTRIK |
| TOI | Teknik Otomasi Industri | T OTOMASI INDUSTRI |
| TKRO | Teknik Kendaraan Ringan Otomotif | TKR |
| TBSM | Teknik dan Bisnis Sepeda Motor | TSM |
| TPM | Teknik Pemesinan | TM, TP, T MESIN |
| TO | Teknik Otomotif (program lama) | – (sebelum pemekaran ke TKRO/TBSM) |
| TS | Teknik Sipil | T SIPIL |
| DPIB | Desain Pemodelan dan Informasi Bangunan | – |
| BKP | Bisnis Konstruksi dan Properti | – |
| TGB | Teknik Gambar Bangunan (program lama) | – |
| TKB | Teknik Konstruksi Bangunan (program lama) | – |
| TKK | Teknik Konstruksi Kayu (program lama) | – |
| TKP | Teknik Konstruksi dan Perumahan | – (Konsentrasi Keahlian di TRANSKRIP 2025-2026; KELAS `XII TKP`) |

Jurusan contoh dari seeder (TKJ, TSM) **tidak** dipakai: TKJ tidak muncul di file Drive mana pun,
TSM dipetakan ke TBSM (nama baru program yang sama). Baris jurusan TKJ/TSM di tabel `jurusan` tetap ada
dengan 0 siswa; boleh dihapus/dirapikan oleh admin bila sudah dikonfirmasi ke sekolah.

### Aturan dedup & tahun ajaran

- **Kunci utama: NIS** (5–8 digit angka). NIS kosong/tidak valid → baris gagal (dilaporkan).
  Bila NIS kosong, NISN dipakai sebagai kunci cadangan (tidak terjadi di import ini — semua baris
  valid memakai NIS).
- **Tahun ajaran siswa** = tahun folder file sumber saat siswa pertama kali ditemukan.
- **Pengecualian (dokumen kelulusan):** bila siswa sudah ada dari tahun lebih lama lalu muncul di
  DATA NOMINATIF XII/XIII atau DATABASE TRANSKRIP (dokumen tahun kelulusan), tahun ajarannya
  diperbarui ke tahun dokumen itu — 71 siswa (65 XIII TOI + 5 XII + 1 transkrip).
- **Status:** semua siswa import = `lulus`, kecuali baris Format B yang punya "Tgl. Meninggalkan
  Sekolah" tanpa "Tamat Belajar" → `keluar` (atau `pindah` bila alasan mengandung kata "pindah").
- **Tanggal masuk:** dari kolom "Diterima di sekolah ini pada tgl" bila ada; bila kosong dipakai
  perkiraan 1 Juli dua tahun sebelum tahun ajaran file (untuk TRANSKRIP: 1 Juli tahun prefix NIS).
  Tanggal masuk ini perkiraan — bukan data resmi.
- **Kelas:** dari nama sheet / kolom rombel bila formatnya `KODE HURUF` (mis. `XII RPL A`);
  bila rombel tidak dapat dipastikan, kelas dibuat generik `XII <KODE>` per jurusan per tahun.
  Kolom ROMBEL di file DATA SISWA 2019-2021 **tidak** dipakai mentah-mentah karena isinya kecamatan
  (kesalahan pengisian di file sumber) — hanya dipakai bila cocok pola rombel.
- **NISN ganda:** bila satu NISN sudah dipakai siswa lain, NISN pada record kedua dikosongkan
  (dicatat di log) agar tidak melanggar constraint unik.
- **Tanggal lahir:** diparse dari (tgl/bln/thn), tanggal tunggal, atau gabungan "Tempat, Tanggal Lahir";
  nama bulan Indonesia + Inggris (termasuk singkatan `Oct`, `Nov`, `Dec`). Cakupan akhir: hanya 35 dari
  6.548 siswa tanpa tanggal lahir (0,5%); 2.595 siswa tanpa NISN (40% — file Format A 2013-2018
  memang tidak punya kolom NISN).

## File yang di-skip sebagai duplikat

| File | Alasan |
|---|---|
| 2021-2022 / MASTER.xlsm | Duplikat MASTER-TEI.xlsm (35 siswa yang sama, overlap 34–35 NIS) |
| 2021-2022 / MASTER-edit.xlsm | Duplikat MASTER-TEI.xlsm (siswa yang sama) |
| 2021-2022 / MASTERrR.xlsm | Duplikat MASTER-TEI.xlsm (siswa yang sama) |
| 2025-2026 / sheet per-jurusan (RPL, TK, TEI, TOI, TP, TKR, TSM, TKP, DPIB) | Jumlahnya sama dengan sheet `all` (740 siswa) — hanya sheet `all` yang diimport |
| 2016-2017 / DATA SISWA 1617.xlsx (non-REVISI) | **Dipakai** sebagai pengayaan NISN (bukan sumber utama); sheet 'XII T SIPIL'-nya memuat seluruh kohort sehingga jurusan tidak diambil dari nama sheet file ini; sheet 'siswa16-17 yg blm ambil ijasah' dilewati (tanpa kolom JK, siswa sudah tercakup sheet utama) |

## File yang butuh keputusan manual (tidak diimport)

| File | Alasan |
|---|---|
| 2013-2014 / Buku Induk 2013-2014 _ kelas.xlsx | Terenkripsi (password). Password di `password buku induk 2013-2014.txt` **tidak cocok** (dicoba via msoffcrypto, kedua password .txt yang ada). Kemungkinan hanya formulir cetak; kohort sudah terimport via DATA SISWA. |
| 2014-2015 / Buku Induk 2014-2015.xlsx | Terenkripsi. Kedua password .txt di Drive tidak cocok. Kohort sudah terimport via DATA SISWA 1415. |
| 2015-2016 / Buku Induk 2015-2016 _19 kelas.xlsx | Terenkripsi. Tidak ada file password .txt untuk tahun ini di Drive. Kohort sudah terimport via DATA SISWA 1516. |
| 2017-2018 / Buku Induk 2017-2018 _ 22 kelas.xlsx | Terenkripsi. Password di `password buku induk 2017-2018.txt` tidak cocok. Kohort sudah terimport via DATA SISWA 1718. |
| 2016-2017 / Buku Induk 2016-2017 _ 19 kelas.xlsx | Tidak terenkripsi, tapi formulir cetak bertumpuk (22 sheet, ±1500–1800 baris/sheet, satu siswa per blok). Kohort sudah terimport via DATA SISWA 1617 REVISI + pengayaan NISN. Parsing formulir cetak tidak sebanding manfaatnya. |
| File rapor per-jurusan (TEI-1314, TO-1314, TEI/TIK-1819, TEI-1920, TEI-2021, dst.) | Bukan biodata massal: sheet BIODATA berisi formulir satu siswa, bukan tabel. Kohort sudah terimport dari DATA SISWA. |
| 2021-2022 / DATA-SEKOLAH.xlsx | Isinya daftar 665 sekolah asal (SMP), bukan data siswa dan bukan identitas SMKN 5; tidak ada tabel yang cocok di skema. Disimpan sebagai referensi di `storage/imports/source/2021-2022/`. |
| 2020-2021 / SUDAH DI RANKING / (SEMESTER 1–8, ±150 file ledger per kelas) | Ledger per kelas per semester sudah tercakup agregatnya via DATA MERGER (rata-rata semester 1–6) dan MASTER SMSTR (2021-2022). File-file ini tidak diimport satu per satu; bisa diimport belakangan bila sekolah meminta detail per mapel. |

## Baris gagal (13 baris — tanpa identitas siswa)

| Alasan | Jumlah | Keterangan |
|---|---|---|
| NIS kosong/tidak valid | 6 | Baris judul/sub-header di tengah sheet (mis. baris "Nama Sekolah" di KELAS X bertumpuk, baris template MASTER.xlsx) dan 2 baris NOMINATIF DPIB tanpa NIS |
| MERGER: NIS kosong/tidak valid | 4 | Baris kaki/footer di DATA MERGER 2020/2021 |
| Jenis kelamin kosong/tidak dikenali | 2 | 1 baris di DATA SISWA 1415 (sheet TKK), 1 baris di DATA SISWA 1920 (sheet TE) — kolom JK kosong di sumber; tidak bisa dibuat karena `jenis_kelamin` wajib di skema |
| Nama kosong/tidak valid | 1 | 1 baris di DATA SISWA 1617 non-REVISI (ada NIS, nama kosong) |

Satu NIS sumber (dari 6.715 NIS unik) tidak masuk DB karena alasan jenis kelamin di atas.
Log detail per baris (berisi NIS, tanpa nama) ada di `storage/imports/work/import_log_20261106-070526.csv`
— file log ini berisi data asli dan **tidak untuk dibagikan**.

## Gap tahun ajaran

**Tidak ada folder Drive untuk 2022-2023, 2023-2024, 2024-2025** (lihat INVENTARIS_DRIVE.md).
Kohort lulus 2026 (NIS prefix 22/23, masuk 2022/2023) tercakup lewat DATABASE TRANSKRIP 2025-2026,
tetapi tidak ada DATA SISWA / NOMINATIF / LEDGER khusus untuk ketiga tahun ajaran tersebut.
Perlu ditanyakan ke sekolah apakah datanya memang tidak ada atau tersimpan di tempat lain.

## Catatan kualitas data (temuan saat import)

- **Prefix NIS tidak konsisten sebagai tahun masuk.** NIS 5 digit dengan 2 digit awal 13–23;
  satu file tahunan berisi dua kelompok prefix (mis. file 2019-2020: prefix 17 dan 18).
  NIS dipakai murni sebagai kunci dedup, bukan sebagai penanda tahun masuk.
- **Sheet TO di DATA SISWA 1920 adalah salinan basi** dari DATA SISWA 1819 (96 NIS sama persis,
  TO sudah dipecah menjadi TKRO/TBSM). Ditangani oleh dedup NIS.
- **Kolom ROMBEL di DATA SISWA 2019-2021 berisi nama kecamatan**, bukan rombel (kesalahan pengisian
  di sumber). Rombel diambil dari nama sheet / kolom Kelas (KELAS X, NOMINATIF, TRANSKRIP) atau
  pola `KODE HURUF`; sisanya memakai kelas generik `XII <KODE>`.
- **File DATA SISWA 1617 non-REVISI**: sheet 'XII T SIPIL' memuat seluruh kohort (558 NIS),
  bukan hanya jurusan Sipil; sheet per-jurusan lain adalah subset darinya. File ini hanya dipakai
  untuk pengayaan NISN.
- **Sheet KELAS X 2019-2020** berisi 4 blok bertumpuk per sheet (rombel A–D) masing-masing dengan
  baris judul + header sendiri; rombel diambil dari baris judul `Kelas: X TM B` dst.
- **Sheet TIK/TM di DB DATA SISWA 1415 (Format B)** berisi dua blok dengan susunan kolom berbeda
  (blok kedua mengikuti susunan Format A, tanpa kolom 'Nama Panggilan'); importer mendeteksi
  susunan per baris.
- **Nilai ledger** adalah rata-rata per semester yang dihitung dari nilai per mapel
  (`SUBJEK_1`..`SUBJEK_6` di DATA MERGER, `SUBJEK_1`..`SUBJEK_5` di TRANSKRIP, kolom R di SMSTR).
  Semester 7–8 tidak ada sumber rata-ratanya (folder `SUDAH DI RANKING` belum diimport per file).
- **NISN kosong pada 2.595 siswa (40%)** — file Format A (2013-2018) tidak punya kolom NISN.
  NISN hanya tersedia dari KELAS X, DATA MERGER, NOMINATIF, TRANSKRIP, dan MASTER.
- **Jurusan TKP** namanya sudah dikonfirmasi dari kolom Konsentrasi Keahlian
  (`Teknik Konstruksi dan Perumahan`). Yang masih perlu konfirmasi sekolah: status jurusan lama
  (TGB/TKB/TKK — kemungkinan pendahulu DPIB/BKP) dan jurusan TKJ (tidak ada di file Drive).
- **Jurusan TGB/TKB/TKK** hanya muncul di file 2013-2015; kemungkinan program lama yang sudah
  tidak ada / berganti nama (DPIB/BKP) — perlu konfirmasi sekolah.

## Cara memakai ulang importer

```bash
# Import semua tahun (ke database-imported.sqlite; database.sqlite contoh tidak disentuh)
php artisan buku-induk:import

# Simulasi dulu (DB tidak berubah)
php artisan buku-induk:import --dry-run

# Hanya satu tahun / satu file
php artisan buku-induk:import --tahun=2019-2020
php artisan buku-induk:import --tahun=2021-2022 --file=2021-2022/DATA NOMINATIF SISWA KELAS XII TP 2021_2022.xlsx

# Buat ulang database-imported.sqlite dari database.sqlite (hapus hasil import lama)
php artisan buku-induk:import --fresh
```

Atau langsung via Python (tanpa artisan):

```bash
python3 storage/imports/import_buku_induk.py [--dry-run] [--fresh] [--tahun 2019-2020] [--file ...] [--db /path/db.sqlite]
```

Importer **tidak pernah menghapus** data yang sudah ada tanpa `--fresh`, melewati duplikat NIS
dengan laporan, melaporkan setiap baris gagal beserta alasannya, dan mencatat setiap file yang
diimport ke tabel `activity_logs` (aksi `import`). Log detail per baris tersimpan di
`storage/imports/work/import_log_<timestamp>.csv`, ringkasan agregat di
`storage/imports/work/import_summary_<timestamp>.json`.

## File-file terkait

| File | Isi |
|---|---|
| `INVENTARIS_DRIVE.md` | Inventaris lengkap folder Drive (598 file) |
| `LAPORAN_IMPORT.md` | Laporan ini (agregat saja) |
| `storage/imports/import_buku_induk.py` | Importer Python (logika utama) |
| `app/Console/Commands/ImportBukuInduk.php` | Artisan command `buku-induk:import` (wrapper) |
| `database/database-imported.sqlite` | **DB hasil import — BERISI DATA ASLI, jangan dibagikan sembarangan** |
| `database/database.sqlite` | DB contoh fiktif (tidak berubah) |
| `database/database.sqlite.contoh.bak` | Backup DB contoh |
| `storage/imports/source/<tahun>/` | File sumber Excel yang didownload — **BERISI DATA ASLI** |
| `storage/imports/work/import_log_*.csv` | Log detail per baris (berisi NIS) — internal saja |
| `storage/imports/work/import_summary_*.json` | Ringkasan agregat per run |

## Yang belum terverifikasi

- Klik manual di browser (aplikasi Laravel) — di luar lingkup import; starter sudah dites
  `php artisan test` (8 passed) sebelum dan sesudah import.
- Import `schema.sql` ke MySQL sungguhan (baru SQLite yang teruji) dan PWA di HP nyata —
  sudah dicatat sejak pembangunan starter.
- Isi file-file "butuh keputusan manual" di atas (terutama 4 Buku Induk terenkripsi) —
  menunggu keputusan/petunjuk sekolah.
- Status jurusan lama (TGB/TKB/TKK, TKJ) — menunggu konfirmasi sekolah.
