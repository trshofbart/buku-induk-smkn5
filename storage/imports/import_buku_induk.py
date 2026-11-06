#!/usr/bin/env python3
"""
Import data lama Buku Induk SMKN 5 Surakarta dari file Excel (Google Drive)
ke database SQLite Laravel (database-imported.sqlite).

Sumber: ~/workspace/buku-induk-smkn5/storage/imports/source/<tahun>/
Target: ~/workspace/buku-induk-smkn5/database/database-imported.sqlite
        (salinan database.sqlite; data contoh fiktif dihapus dari salinan ini,
         file database.sqlite asli TIDAK disentuh; backup-nya di
         database.sqlite.contoh.bak).

Aturan utama:
- Kunci dedup: NIS (5-8 digit). NIS kosong/tidak valid -> baris gagal (dilaporkan).
- File duplikat (MASTER vs MASTER-edit dsb.) -> hanya 1 file per jurusan per
  tahun yang diproses; sisanya dicatat sebagai di-skip.
- Status: semua kohort lama 'lulus' (kecuali ada keterangan pindah/keluar).
- Tahun ajaran siswa = tahun folder file sumber saat pertama kali ditemukan.
  Pengecualian: file dokumen kelulusan (NOMINATIF XII/XIII, TRANSKRIP)
  memindahkan siswa yang sudah ada ke tahun kelulusannya (dicatat sbg
  "pindah_tahun").
- TIDAK PERNAH menghapus data yang sudah ada tanpa --fresh.
- Dry-run: import dijalankan penuh terhadap salinan sementara lalu dibuang.

Pemakaian:
  python3 import_buku_induk.py [--dry-run] [--fresh] [--tahun 2019-2020]
  python3 import_buku_induk.py --file 2021-2022/DATA...xlsx --tahun 2021-2022

PERINGATAN PRIVASI: file sumber dan DB hasil import berisi data asli siswa
(nama, NIS/NISN, tanggal lahir, alamat, orang tua). JANGAN diunggah ke hosting
publik / dikirim ke pihak luar tanpa izin sekolah. Laporan agregat saja yang
boleh dibagikan (lihat LAPORAN_IMPORT.md).
"""
import argparse
import csv
import json
import os
import re
import shutil
import sqlite3
import sys
import tempfile
from collections import Counter, defaultdict
from datetime import date, datetime

import openpyxl

BASE = os.path.dirname(os.path.abspath(__file__))          # storage/imports/
PROJECT = os.path.dirname(os.path.dirname(BASE))           # buku-induk-smkn5/
SRC_DIR = os.path.join(BASE, "source")
WORK_DIR = os.path.join(BASE, "work")
DB_EXAMPLE = os.path.join(PROJECT, "database", "database.sqlite")
DB_BACKUP = os.path.join(PROJECT, "database", "database.sqlite.contoh.bak")
DB_IMPORT = os.path.join(PROJECT, "database", "database-imported.sqlite")

os.makedirs(WORK_DIR, exist_ok=True)

# ---------------------------------------------------------------------------
# Pemetaan jurusan: kode kanonik -> (nama, alias kode lama / varian penulisan)
# Nama lengkap yang belum dikonfirmasi sekolah ditandai di deskripsi.
# ---------------------------------------------------------------------------
JURUSAN_MAP = {
    "RPL":  ("Rekayasa Perangkat Lunak",
             ["TIK"], "TIK = nama lama RPL (terlihat di file 2013-2015)."),
    "TEI":  ("Teknik Elektronika Industri",
             ["TE"], "TE = singkatan lama TEI."),
    "TITL": ("Teknik Instalasi Tenaga Listrik",
             ["TL", "T LISTRIK", "T. LISTRIK"], "TL = singkatan lama TITL."),
    "TOI":  ("Teknik Otomasi Industri",
             ["T OTOMASI INDUSTRI", "T. OTOMASI INDUSTRI"], ""),
    "TKRO": ("Teknik Kendaraan Ringan Otomotif",
             ["TKR"], "TKR = singkatan baru TKRO."),
    "TBSM": ("Teknik dan Bisnis Sepeda Motor",
             ["TSM"], "TSM = singkatan lama TBSM."),
    "TPM":  ("Teknik Pemesinan",
             ["TM", "TP", "T MESIN"], "TM/TP = nama lama/varian TPM."),
    "TO":   ("Teknik Otomotif (program lama)",
             [], "Program lama sebelum pemekaran menjadi TKRO dan TBSM."),
    "TS":   ("Teknik Sipil",
             ["T SIPIL"], ""),
    "DPIB": ("Desain Pemodelan dan Informasi Bangunan",
             [], ""),
    "BKP":  ("Bisnis Konstruksi dan Properti",
             [], ""),
    "TGB":  ("Teknik Gambar Bangunan (program lama)",
             [], "Program lama; kemungkinan pendahulu DPIB (belum dikonfirmasi)."),
    "TKB":  ("Teknik Konstruksi Bangunan (program lama)",
             [], "Program lama."),
    "TKK":  ("Teknik Konstruksi Kayu (program lama)",
             [], "Program lama."),
    "TKP":  ("Teknik Konstruksi dan Perumahan",
             [], "Konsentrasi Keahlian di DATABASE TRANSKRIP 2025-2026; KELAS 'XII TKP'."),
}
# Catatan: 'TK' BUKAN jurusan — itu nama program 'Teknik Ketenagalistrikan' di
# DATABASE TRANSKRIP 2025-2026; siswanya tercatat sebagai TITL sesuai kolom KELAS
# ('XII TITL A/B') dan Konsentrasi Keahlian ('Teknik Instalasi Tenaga Listrik').
ALIAS = {}
for _kode, (_nama, _aliases, _desc) in JURUSAN_MAP.items():
    for _a in _aliases:
        ALIAS[_a] = _kode

# Nama sheet KELAS X -> kode jurusan
KELASX_SHEET = {
    "X T SIPIL": "TS", "X T LISTRIK": "TITL", "X T OTOMASI INDUSTRI": "TOI",
    "X T ELEKTRONIKA": "TEI", "X T MESIN": "TPM", "X T OTOMOTIF": "TO",
    "X T RPL": "RPL",
}

BULAN_ID = {"JANUARI": 1, "JAN": 1, "FEBRUARI": 2, "FEB": 2, "MARET": 3,
            "MAR": 3, "APRIL": 4, "APR": 4, "MEI": 5, "JUNI": 6, "JUN": 6,
            "JULI": 7, "JUL": 7, "AGUSTUS": 8, "AGU": 8, "AGS": 8,
            "SEPTEMBER": 9, "SEP": 9, "SEPT": 9, "OKTOBER": 10, "OKT": 10,
            "NOVEMBER": 11, "NOV": 11, "DESEMBER": 12, "DES": 12}
BULAN_EN = {"JANUARY": 1, "FEBRUARY": 2, "MARCH": 3, "APRIL": 4, "MAY": 5,
            "JUNE": 6, "JULY": 7, "AUGUST": 8, "SEPTEMBER": 9, "OCTOBER": 10,
            "NOVEMBER": 11, "DECEMBER": 12, "JAN": 1, "FEB": 2, "MAR": 3,
            "APR": 4, "JUN": 6, "JUL": 7, "AUG": 8, "SEP": 9, "SEPT": 9,
            "OCT": 10, "NOV": 11, "DEC": 12}

# ---------------------------------------------------------------------------
# Util normalisasi
# ---------------------------------------------------------------------------
def norm_header(h):
    """Normalisasi nama kolom: huruf besar, tanpa spasi/titik/garis bawah/slash/koma."""
    if h is None:
        return ""
    return (str(h).strip().upper().replace(".", "").replace(" ", "").replace("_", "")
            .replace("/", "").replace("-", "").replace(",", "").replace(";", "")
            .replace("(", "").replace(")", ""))

def clean_str(v):
    if v is None:
        return None
    s = str(v).strip()
    s = re.sub(r"\s+", " ", s)
    return s or None

def clean_nis(v):
    """NIS valid: hanya digit, panjang 5-8. Kembalikan str atau None."""
    if v is None:
        return None
    s = str(v).strip()
    if s.endswith(".0"):
        s = s[:-2]
    s = s.replace(" ", "")
    if re.fullmatch(r"\d{5,8}", s):
        return s
    return None

def clean_nisn(v):
    if v is None:
        return None
    s = str(v).strip()
    if s.endswith(".0"):
        s = s[:-2]
    s = s.replace(" ", "").replace("-", "")
    if re.fullmatch(r"\d{8,10}", s):
        return s
    return None

def clean_nama(v):
    s = clean_str(v)
    if not s:
        return None
    if s.isdigit():
        return None
    if s.upper().startswith(("JUMLAH", "TOTAL", "REKAP", "SUB TOTAL")):
        return None
    # Huruf kapital di awal tiap kata; jaga singkatan bertitik (M., S.H. dsb.)
    parts = s.split(" ")
    out = []
    for p in parts:
        if re.fullmatch(r"[A-Z]{2,}\.?", p) and len(p) <= 4:
            out.append(p if p.endswith(".") else p)
        else:
            out.append(p.capitalize() if p.isupper() or p.islower() else p)
    return " ".join(out)

def clean_jk(v):
    if v is None:
        return None
    s = str(v).strip().upper().replace(" ", "").replace("-", "")
    if s in ("L", "LAKILAKI", "LAKI"):
        return "L"
    if s in ("P", "PEREMPUAN", "WANITA"):
        return "P"
    return None

def clean_agama(v):
    s = clean_str(v)
    if not s:
        return None
    m = {"ISLAM": "Islam", "KRISTEN": "Kristen", "KATOLIK": "Katolik",
         "HINDU": "Hindu", "BUDHA": "Budha", "BUDDHA": "Budha",
         "KONGHUCU": "Konghucu", "KRISTEN PROTESTAN": "Kristen",
         "KRISTEN KATOLIK": "Katolik"}
    return m.get(s.upper(), s.title() if len(s) <= 20 else None)

def clean_hp(v):
    if v is None:
        return None
    s = re.sub(r"[^\d]", "", str(v))
    if not s:
        return None
    if s.startswith("62"):
        s = "0" + s[2:]
    return s[:20] if len(s) >= 9 else None

def parse_date(v):
    """Parse berbagai format tanggal Indonesia -> datetime.date atau None."""
    if v is None or v == "":
        return None
    if isinstance(v, datetime):
        return v.date()
    if isinstance(v, date):
        return v
    if isinstance(v, (int, float)):
        return None
    s = str(v).strip()
    if not s:
        return None
    # "Tempat, 16-7-1996" -> ambil bagian setelah koma terakhir
    if "," in s:
        s = s.split(",")[-1].strip()
    s = s.replace(".", " ").strip()
    m = re.match(r"^(\d{1,2})[-/ ](\d{1,2})[-/ ](\d{2,4})$", s)
    if m:
        d, mo, y = int(m.group(1)), int(m.group(2)), int(m.group(3))
        if y < 100:
            y += 2000
        try:
            return date(y, mo, d)
        except ValueError:
            return None
    m = re.match(r"^(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})$", s)
    if m:
        d = int(m.group(1))
        mo = BULAN_ID.get(m.group(2).upper()) or BULAN_EN.get(m.group(2).upper())
        y = int(m.group(3))
        if mo:
            try:
                return date(y, mo, d)
            except ValueError:
                return None
    m = re.match(r"^(\d{4})[-/](\d{1,2})[-/](\d{1,2})$", s)
    if m:
        try:
            return date(int(m.group(1)), int(m.group(2)), int(m.group(3)))
        except ValueError:
            return None
    return None

def parse_tgl_triple(tgl, bln, thn):
    """Tanggal dari 3 kolom terpisah (tgl/bln/thn); bln boleh nama atau angka."""
    if tgl is None or thn is None:
        return None
    try:
        d = int(float(str(tgl).strip()))
        y = int(float(str(thn).strip()))
    except (ValueError, TypeError):
        return None
    if y < 100:
        y += 2000
    if bln is None or str(bln).strip() == "":
        return None
    bs = str(bln).strip().upper()
    if bs.isdigit():
        mo = int(bs)
    else:
        mo = BULAN_ID.get(bs) or BULAN_EN.get(bs)
    if not mo:
        return None
    try:
        return date(y, mo, d)
    except ValueError:
        return None

def canon_jurusan(code_raw):
    """Kode jurusan mentah (nama sheet / kolom) -> kode kanonik atau None."""
    if code_raw is None:
        return None
    c = str(code_raw).strip().upper()
    c = re.sub(r"\s+", " ", c)
    if c in JURUSAN_MAP:
        return c
    if c in ALIAS:
        return ALIAS[c]
    # "T SIPIL" / "T LISTRIK" (nama sheet KELAS X) -> kode kanonik
    t_map = {"T SIPIL": "TS", "T LISTRIK": "TITL", "T OTOMASI INDUSTRI": "TOI",
             "T ELEKTRONIKA": "TEI", "T MESIN": "TPM", "T OTOMOTIF": "TO",
             "T RPL": "RPL"}
    if c in t_map:
        return t_map[c]
    # "TITL A" / "TP B" (kode + huruf rombel) -> kode tanpa huruf rombel
    m2 = re.match(r"^([A-Z]+)\s+([A-D])$", c)
    if m2:
        t = m2.group(1)
        if t in JURUSAN_MAP:
            return t
        if t in ALIAS:
            return ALIAS[t]
        if t in t_map:
            return t_map[t]
    # "XII RPL A" / "X T RPL" / "XIITL A" -> ambil token jurusan
    m = re.match(r"^(X{1,3}|XII|XIII)\s*(.*)$", c)
    if m:
        rest = m.group(2).strip()
        for tok in (rest.split(" ") + [rest.replace(" ", "")]):
            if tok in JURUSAN_MAP:
                return tok
            if tok in ALIAS:
                return ALIAS[tok]
            if tok in t_map:
                return t_map[tok]
    return None

def parse_kelas_label(label):
    """'XII RPL A' / 'X T SIPIL' / 'XIITL A' -> (tingkat, kode_jurusan, nama_kelas)."""
    if label is None:
        return None, None, None
    s = re.sub(r"\s+", " ", str(label).strip().upper())
    m = re.match(r"^(XIII|XII|XI|X)\s*(.*)$", s)
    if not m:
        return None, None, None
    tingkat, rest = m.group(1), m.group(2).strip()
    kode = canon_jurusan(rest) if rest else None
    if kode is None and rest:
        kode = canon_jurusan("T " + rest)  # "T SIPIL" -> TS
    if kode:
        # huruf rombel: token terakhir berupa satu huruf A-D, atau nama lengkap
        huruf = None
        toks = rest.replace("T ", "").split(" ")
        if toks and re.fullmatch(r"[A-D]", toks[-1]):
            huruf = toks[-1]
        nama = f"{tingkat} {kode}" + (f" {huruf}" if huruf else "")
        return tingkat, kode, nama
    return tingkat, None, s

def mean_or_none(vals):
    nums = [float(v) for v in vals if isinstance(v, (int, float)) and not isinstance(v, bool) and 0 <= v <= 100]
    if not nums:
        return None
    return round(sum(nums) / len(nums), 2)

# ---------------------------------------------------------------------------
# Pemetaan kolom Excel -> field (berdasarkan header yang dinormalisasi)
# ---------------------------------------------------------------------------
COLMAP = {
    # NIS
    "NIS": "nis", "NOINDUK": "nis", "NOMORINDUK": "nis",
    # NISN
    "NISN": "nisn",
    # Nama
    "NAMASISWA": "nama", "NAMA": "nama", "NAMALENGKAP": "nama",
    # Jenis kelamin
    "JENISKELAMIN": "jk", "LP": "jk", "JK": "jk",
    # Tempat lahir
    "TEMPATLAHIR": "tempat_lahir", "TEMPAT": "tempat_lahir", "TEMPATLHR": "tempat_lahir",
    # Tanggal lahir: satu kolom
    "TGLLAHIR": "tgl_lahir", "TANGGALLAHIR": "tgl_lahir", "TTL": "tgl_lahir",
    "TEMPATTANGGALLAHIR": "ttl_gabung",
    # Tanggal lahir: tiga kolom
    "TGL": "tgl", "TGLLHR": "tgl", "TANGGAL": "tgl",
    "BLN": "bln", "BLNLHR": "bln", "BULAN": "bln", "BLLHR": "bln", "BULANLAHIR": "bln",
    "THN": "thn", "THNLHR": "thn", "TAHUN": "thn", "THLHR": "thn", "TAHUNLAHIR": "thn",
    # Agama / alamat / HP
    "AGAMA": "agama",
    "ALAMATSISWA": "alamat", "ALAMAT": "alamat",
    "HPSISWA": "no_hp", "NOTELEPON": "no_hp", "NOTELEPONHP": "no_hp",
    "NOHP": "no_hp", "TELEPON": "no_hp", "HP": "no_hp",
    # Orang tua / wali
    "NAMABAPAK": "nama_ayah", "NAMAAYAH": "nama_ayah", "BAPAK": "nama_ayah",
    "AYAH": "nama_ayah", "NAMAORTU": "nama_ayah",
    "NAMAIBU": "nama_ibu", "IBU": "nama_ibu",
    "NAMAWALI": "nama_wali", "WALI": "nama_wali",
    "PEKERJAANORANGTUA": "pek_ortu", "PEKERJAANORANGTUAWALI": "pek_ortu",
    "PEKERJAAN": "pek_ortu",
    "PEKERJAANAYAH": "pek_ayah", "PEKERJAANIBU": "pek_ibu",
    "PEKERJAANWALI": "pek_wali",
    "HPORANGTUA": "no_hp_ortu", "HPORTU": "no_hp_ortu", "HPWALI": "no_hp_ortu",
    "ALAMATORTU": "alamat_ortu", "ALAMATORANGTUAWALI": "alamat_ortu",
    # Alamat terurai
    "DESA": "desa", "DUSUN": "desa", "RT": "rt", "RW": "rw",
    "KELURAHAN": "kelurahan", "KECAMATAN": "kecamatan",
    "KOTA": "kota", "KABUPATENKOTA": "kota",
    "KODEPOS": "kode_pos",
    # Sekolah asal
    "SEKOLAHASAL": "asal_sekolah", "ASALSEKOLAH": "asal_sekolah",
    # Rombel / kelas / diterima
    "ROMBEL": "rombel", "ROMBELSAATINI": "rombel", "KELAS": "rombel",
    "DITERIMADIKELAS": "diterima_kelas",
    "DITERIMADISEKOLAHINIPADATGL": "tgl_masuk",
    "DITERIMADISEKOLAHINIPADATANGGAL": "tgl_masuk",
    # Meninggalkan sekolah (Format B)
    "TGLMENINGGALKANSEKOLAH": "tgl_keluar", "ALASAN": "alasan_keluar",
    "TAMATBELAJAR": "tamat",
    # Transkrip
    "NOMORIJAZAH": "nomor_ijazah", "NOSERIIJAZAH": "nomor_ijazah",
    "NOMORSURAT": "nomor_ijazah",
    "PROGRAMKEAHLIAN": "program_keahlian",
    "KONSENTRASIKEAHLIAN": "konsentrasi",
    # Lain-lain (dicatat, tidak diimport ke skema saat ini)
    "NIK": "nik", "NOKK": "no_kk", "NO PESERTA UN": "no_peserta_un",
    "EMAIL": "email", "JURUSAN": "jurusan_kolom", "TAHUNMASUK": "tahun_masuk_kolom",
    "KOMPKEAHLIAN": "konsentrasi", "PROSTUDI": "program_keahlian",
}

def find_header_row(rows, max_scan=12):
    """Cari baris header: mengandung NIS/NO INDUK dan NAMA. Kembalikan indeks."""
    for i, row in enumerate(rows[:max_scan]):
        if not row:
            continue
        joined = " ".join(norm_header(c) for c in row if c is not None)
        if ("NIS" in joined or "NOINDUK" in joined) and "NAMA" in joined:
            return i
    return None

def map_columns(header_row):
    """Header (list nilai) -> dict field -> indeks kolom (ambil kemunculan pertama)."""
    out = {}
    for idx, h in enumerate(header_row):
        n = norm_header(h)
        if not n:
            continue
        field = COLMAP.get(n)
        if field and field not in out:
            out[field] = idx
    return out

def row_to_dict(row, colmap):
    d = {}
    for field, idx in colmap.items():
        if idx < len(row):
            d[field] = row[idx]
    return d

def extract_biodata(d):
    """Dict kolom mentah -> dict siswa ternormalisasi (atau None bila tak layak)."""
    nis = clean_nis(d.get("nis"))
    nama = clean_nama(d.get("nama"))
    if not nis or not nama:
        return None, ("NIS kosong/tidak valid" if not nis else "Nama kosong/tidak valid")
    jk = clean_jk(d.get("jk"))
    if not jk:
        return None, "Jenis kelamin kosong/tidak dikenali"
    # tanggal lahir
    tgl_lahir = None
    tempat = clean_str(d.get("tempat_lahir"))
    if d.get("tgl_lahir") is not None:
        tgl_lahir = parse_date(d.get("tgl_lahir"))
        if tgl_lahir is None:
            # 'Tgl. Lahir' berisi angka hari saja (Format B) -> gabung bln/thn
            tgl_lahir = parse_tgl_triple(d.get("tgl_lahir"), d.get("bln"), d.get("thn"))
    if tgl_lahir is None and d.get("ttl_gabung") is not None:
        gab = str(d.get("ttl_gabung"))
        if "," in gab:
            tempat, tgl_txt = gab.split(",", 1)
            tempat = clean_str(tempat)
            tgl_lahir = parse_date(tgl_txt)
        else:
            tgl_lahir = parse_date(gab)
    if tgl_lahir is None and (d.get("tgl") is not None or d.get("thn") is not None):
        tgl_lahir = parse_tgl_triple(d.get("tgl"), d.get("bln"), d.get("thn"))
        if tgl_lahir is None and d.get("tgl") is not None:
            # kolom TGL berisi tanggal lengkap ('27 Oct 2002'), BL/TH kosong
            tgl_lahir = parse_date(d.get("tgl"))
    # alamat: gabung alamat + bagian terurai
    alamat_parts = []
    if clean_str(d.get("alamat")):
        alamat_parts.append(clean_str(d.get("alamat")))
    for k in ("desa", "rt", "rw", "kelurahan", "kecamatan", "kota", "kode_pos"):
        v = d.get(k)
        if v is not None and str(v).strip() not in ("", "-"):
            label = {"rt": "RT", "rw": "RW"}.get(k, "")
            alamat_parts.append(f"{label} {str(v).strip()}".strip() if label else str(v).strip())
    alamat = ", ".join(alamat_parts) if alamat_parts else None
    # tanggal masuk
    tgl_masuk = parse_date(d.get("tgl_masuk")) if d.get("tgl_masuk") is not None else None
    # status keluar/pindah (Format B)
    status = "lulus"
    tgl_keluar = None
    if d.get("tgl_keluar") is not None and str(d.get("tgl_keluar")).strip() not in ("", "-"):
        tgl_keluar = parse_date(d.get("tgl_keluar"))
        if not d.get("tamat") or str(d.get("tamat")).strip() in ("", "-"):
            alasan = (str(d.get("alasan_keluar") or "")).lower()
            status = "pindah" if "pindah" in alasan else "keluar"
    siswa = {
        "nis": nis,
        "nisn": clean_nisn(d.get("nisn")),
        "nama": nama,
        "jenis_kelamin": jk,
        "tempat_lahir": tempat,
        "tanggal_lahir": tgl_lahir.isoformat() if tgl_lahir else None,
        "agama": clean_agama(d.get("agama")),
        "alamat": alamat,
        "no_hp": clean_hp(d.get("no_hp")),
        "tanggal_masuk": tgl_masuk.isoformat() if tgl_masuk else None,
        "tanggal_keluar": tgl_keluar.isoformat() if tgl_keluar else None,
        "status": status,
        "asal_sekolah": clean_str(d.get("asal_sekolah")),
        "nomor_ijazah": clean_str(d.get("nomor_ijazah")),
        "rombel_raw": clean_str(d.get("rombel")),
        "jurusan_kolom": clean_str(d.get("jurusan_kolom")),
        "tahun_masuk_kolom": d.get("tahun_masuk_kolom"),
    }
    ortu = {
        "nama_ayah": clean_nama(d.get("nama_ayah")),
        "nama_ibu": clean_nama(d.get("nama_ibu")),
        "nama_wali": clean_nama(d.get("nama_wali")),
        "pekerjaan_ayah": clean_str(d.get("pek_ayah")) or clean_str(d.get("pek_ortu")),
        "pekerjaan_ibu": clean_str(d.get("pek_ibu")),
        "pekerjaan_wali": clean_str(d.get("pek_wali")),
        "no_hp_ortu": clean_hp(d.get("no_hp_ortu")),
        "alamat_ortu": clean_str(d.get("alamat_ortu")),
    }
    # "Nama Ortu" (transkrip) sudah dipetakan ke nama_ayah; pekerjaan ortu gabungan
    # dipakai sebagai pekerjaan_ayah hanya bila nama_ayah terisi dari kolom itu.
    if ortu["nama_ayah"] and not ortu["pekerjaan_ayah"]:
        ortu["pekerjaan_ayah"] = clean_str(d.get("pek_ortu"))
    siswa["ortu"] = ortu
    return siswa, None

def grade_averages(header_row, data_row, max_semester=6):
    """Rata-rata nilai per semester dari kolom SUBJECT_N (N=1..max_semester).
    Nilai boleh berupa angka atau string angka ('88')."""
    by_sem = defaultdict(list)
    pat = re.compile(r"^(.+)_(\d+)$")
    for idx, h in enumerate(header_row):
        if h is None:
            continue
        m = pat.match(str(h).strip().upper())
        if not m:
            continue
        sem = int(m.group(2))
        if 1 <= sem <= max_semester and idx < len(data_row):
            v = data_row[idx]
            if isinstance(v, str):
                v = v.strip()
                if re.fullmatch(r"\d{1,3}(\.\d+)?", v):
                    v = float(v)
                else:
                    continue
            if isinstance(v, (int, float)) and not isinstance(v, bool):
                by_sem[sem].append(v)
    return {s: mean_or_none(v) for s, v in by_sem.items() if mean_or_none(v) is not None}

def smstr_r_averages(header_row6, data_row):
    """SMSTR .xlsm: baris 6 berisi N/P/R per mapel; kolom 'R' = rata-rata mapel.
    Kembalikan rata-rata keseluruhan semester itu."""
    r_vals = []
    for idx, h in enumerate(header_row6):
        if h is not None and str(h).strip().upper() == "R" and idx >= 4 and idx < len(data_row):
            v = data_row[idx]
            if isinstance(v, (int, float)) and not isinstance(v, bool) and 0 <= v <= 100:
                r_vals.append(v)
    return mean_or_none(r_vals) if r_vals else None

# ---------------------------------------------------------------------------
# Importer
# ---------------------------------------------------------------------------
class Importer:
    def __init__(self, db_path, dry_run=False):
        self.db_path = db_path
        self.dry_run = dry_run
        self.conn = sqlite3.connect(db_path)
        self.conn.row_factory = sqlite3.Row
        self.conn.execute("PRAGMA foreign_keys = ON")
        self.cur = self.conn.cursor()
        self.now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        # cache
        self.tahun_id = {r["nama"]: r["id"] for r in self.cur.execute("SELECT id, nama FROM tahun_ajaran")}
        self.jurusan_id = {r["kode"]: r["id"] for r in self.cur.execute("SELECT id, kode FROM jurusan")}
        self.kelas_id = {}
        for r in self.cur.execute("SELECT id, tahun_ajaran_id, jurusan_id, nama FROM kelas"):
            self.kelas_id[(r["tahun_ajaran_id"], r["jurusan_id"], r["nama"])] = r["id"]
        self.siswa_by_nis = {}
        for r in self.cur.execute(
            "SELECT id, nis, nisn, nama, jenis_kelamin, tempat_lahir, tanggal_lahir, "
            "agama, alamat, no_hp, tahun_ajaran_id, jurusan_id, kelas_id, status, "
            "tanggal_masuk, tanggal_keluar FROM siswa"):
            self.siswa_by_nis[r["nis"]] = dict(r)
        self.nisn_owner = {}
        for nis, s in self.siswa_by_nis.items():
            if s.get("nisn"):
                self.nisn_owner[s["nisn"]] = nis
        self.ortu_by_siswa = {r["siswa_id"]: dict(r) for r in self.cur.execute("SELECT * FROM orang_tua")}
        self.stats = defaultdict(Counter)          # per sumber (file): Counter
        self.per_tahun_jurusan = defaultdict(Counter)  # (tahun, jurusan) -> dibuat
        self.log_rows = []                          # baris log detail (NIS, sumber, aksi, alasan)
        self.skipped_files = []                     # (file, alasan)
        self.manual_files = []                      # (file, alasan)

    # -- lookup / create ----------------------------------------------------
    def ensure_jurusan(self, kode):
        if kode in self.jurusan_id:
            return self.jurusan_id[kode]
        nama, _aliases, desc = JURUSAN_MAP.get(kode, (kode, [], "Kode dari file sumber; nama lengkap perlu dikonfirmasi."))
        self.cur.execute(
            "INSERT INTO jurusan (kode, nama, deskripsi, created_at, updated_at) VALUES (?,?,?,?,?)",
            (kode, nama, desc or None, self.now, self.now))
        jid = self.cur.lastrowid
        self.jurusan_id[kode] = jid
        return jid

    def ensure_kelas(self, tahun, jurusan_kode, tingkat, nama_kelas):
        tid = self.tahun_id[tahun]
        jid = self.ensure_jurusan(jurusan_kode)
        key = (tid, jid, nama_kelas)
        if key in self.kelas_id:
            return self.kelas_id[key]
        self.cur.execute(
            "INSERT INTO kelas (tahun_ajaran_id, jurusan_id, tingkat, nama, created_at, updated_at) "
            "VALUES (?,?,?,?,?,?)", (tid, jid, tingkat, nama_kelas, self.now, self.now))
        kid = self.cur.lastrowid
        self.kelas_id[key] = kid
        return kid

    def default_tanggal_masuk(self, tahun, siswa):
        """Perkiraan tanggal masuk bila kolom 'Diterima' kosong:
        1 Juli, dua tahun sebelum tahun ajaran file (XII = masuk X dua tahun lalu)."""
        start = int(tahun.split("-")[0])
        return f"{start - 2}-07-01"

    # -- siswa upsert --------------------------------------------------------
    def upsert_siswa(self, s, tahun, jurusan_kode, tingkat, nama_kelas,
                     sumber, graduating_doc=False):
        """Kembalikan aksi: 'dibuat' | 'diperkaya' | 'duplikat' | 'pindah_tahun'."""
        nis = s["nis"]
        tid = self.tahun_id[tahun]
        jid = self.ensure_jurusan(jurusan_kode)
        kid = self.ensure_kelas(tahun, jurusan_kode, tingkat, nama_kelas) if nama_kelas else None
        existing = self.siswa_by_nis.get(nis)
        if existing is None:
            if not s.get("tanggal_masuk"):
                s["tanggal_masuk"] = self.default_tanggal_masuk(tahun, s)
            self.cur.execute(
                "INSERT INTO siswa (nis, nisn, nama, jenis_kelamin, tempat_lahir, tanggal_lahir, "
                "agama, alamat, no_hp, tahun_ajaran_id, jurusan_id, kelas_id, status, "
                "tanggal_masuk, tanggal_keluar, keterangan, created_at, updated_at) "
                "VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                (nis, self._safe_nisn(s.get("nisn"), nis), s["nama"], s["jenis_kelamin"],
                 s.get("tempat_lahir"), s.get("tanggal_lahir"), s.get("agama"),
                 s.get("alamat"), s.get("no_hp"), tid, jid, kid, s.get("status", "lulus"),
                 s.get("tanggal_masuk"), s.get("tanggal_keluar"),
                 ("Sekolah asal: " + s["asal_sekolah"]) if s.get("asal_sekolah") else None,
                 self.now, self.now))
            sid = self.cur.lastrowid
            row = dict(s)
            row.update({"id": sid, "tahun_ajaran_id": tid, "jurusan_id": jid, "kelas_id": kid})
            self.siswa_by_nis[nis] = row
            if s.get("nisn"):
                self.nisn_owner[s["nisn"]] = nis
            self._upsert_ortu(sid, s.get("ortu") or {})
            self._add_riwayat(sid, kid, tid, "lulus")
            self.per_tahun_jurusan[(tahun, jurusan_kode)]["dibuat"] += 1
            return "dibuat"
        # sudah ada: perkaya field kosong + aturan pindah tahun kelulusan
        sid = existing["id"]
        changed = self._enrich_fields(sid, existing, s)
        self._upsert_ortu(sid, s.get("ortu") or {})
        aksi = "diperkaya" if changed else "duplikat"
        if graduating_doc and existing["tahun_ajaran_id"] < tid:
            # dokumen kelulusan (nominatif/transkrip) lebih baru dari tahun tercatat
            self.cur.execute(
                "UPDATE siswa SET tahun_ajaran_id=?, jurusan_id=?, kelas_id=?, status='lulus', updated_at=? WHERE id=?",
                (tid, jid, kid, self.now, sid))
            existing.update({"tahun_ajaran_id": tid, "jurusan_id": jid, "kelas_id": kid, "status": "lulus"})
            self._add_riwayat(sid, kid, tid, "lulus")
            self.per_tahun_jurusan[(tahun, jurusan_kode)]["pindah_tahun"] += 1
            aksi = "pindah_tahun"
        return aksi

    def _enrich_fields(self, sid, existing, s):
        """Isi field siswa yang masih kosong dari data baru. Kembalikan daftar field yang berubah."""
        nis = existing["nis"]
        changed = []
        for f in ("nisn", "tempat_lahir", "tanggal_lahir", "agama", "alamat", "no_hp",
                  "tanggal_masuk", "tanggal_keluar"):
            new_v = s.get(f)
            if new_v and not existing.get(f):
                if f == "nisn":
                    new_v = self._safe_nisn(new_v, nis)
                    if not new_v:
                        continue
                self.cur.execute(f"UPDATE siswa SET {f}=?, updated_at=? WHERE id=?", (new_v, self.now, sid))
                existing[f] = new_v
                changed.append(f)
                if f == "nisn":
                    self.nisn_owner[new_v] = nis
        return changed

    def _safe_nisn(self, nisn, nis):
        """NISN harus unik; bila sudah dipakai siswa lain -> kembalikan None."""
        if not nisn:
            return None
        owner = self.nisn_owner.get(nisn)
        if owner and owner != nis:
            return None
        return nisn

    def _upsert_ortu(self, sid, ortu):
        if not any(v for v in ortu.values()):
            return
        ex = self.ortu_by_siswa.get(sid)
        if ex is None:
            self.cur.execute(
                "INSERT INTO orang_tua (siswa_id, nama_ayah, pekerjaan_ayah, nama_ibu, pekerjaan_ibu, "
                "nama_wali, pekerjaan_wali, no_hp_ortu, alamat_ortu, created_at, updated_at) "
                "VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                (sid, ortu.get("nama_ayah"), ortu.get("pekerjaan_ayah"), ortu.get("nama_ibu"),
                 ortu.get("pekerjaan_ibu"), ortu.get("nama_wali"), ortu.get("pekerjaan_wali"),
                 ortu.get("no_hp_ortu"), ortu.get("alamat_ortu"), self.now, self.now))
            self.ortu_by_siswa[sid] = {"siswa_id": sid, **ortu}
        else:
            for f in ("nama_ayah", "pekerjaan_ayah", "nama_ibu", "pekerjaan_ibu",
                      "nama_wali", "pekerjaan_wali", "no_hp_ortu", "alamat_ortu"):
                if ortu.get(f) and not ex.get(f):
                    self.cur.execute(f"UPDATE orang_tua SET {f}=?, updated_at=? WHERE siswa_id=?",
                                     (ortu[f], self.now, sid))
                    ex[f] = ortu[f]

    def _add_riwayat(self, sid, kid, tid, status_naik):
        if not kid:
            return
        ex = self.cur.execute(
            "SELECT id FROM riwayat_kelas WHERE siswa_id=? AND kelas_id=? AND tahun_ajaran_id=?",
            (sid, kid, tid)).fetchone()
        if not ex:
            self.cur.execute(
                "INSERT INTO riwayat_kelas (siswa_id, kelas_id, tahun_ajaran_id, status_naik, created_at, updated_at) "
                "VALUES (?,?,?,?,?,?)", (sid, kid, tid, status_naik, self.now, self.now))

    def add_ledger(self, sid, tahun, semester, rata):
        if rata is None:
            return False
        tid = self.tahun_id[tahun]
        self.cur.execute(
            "INSERT OR IGNORE INTO ledger (siswa_id, tahun_ajaran_id, semester, rata_rata, created_at, updated_at) "
            "VALUES (?,?,?,?,?,?)", (sid, tid, semester, rata, self.now, self.now))
        return self.cur.rowcount > 0

    def add_transkrip(self, sid, tahun, nomor, keterangan=None):
        tid = self.tahun_id[tahun]
        nomor_ok = None
        if nomor:
            clash = self.cur.execute(
                "SELECT siswa_id FROM transkrip WHERE nomor_transkrip=?", (nomor,)).fetchone()
            if not clash or clash["siswa_id"] == sid:
                nomor_ok = nomor
        self.cur.execute(
            "INSERT OR IGNORE INTO transkrip (siswa_id, tahun_ajaran_id, nomor_transkrip, keterangan, created_at, updated_at) "
            "VALUES (?,?,?,?,?,?)", (sid, tid, nomor_ok, keterangan, self.now, self.now))
        return self.cur.rowcount > 0

    def log_activity(self, sumber, ringkasan):
        self.cur.execute(
            "INSERT INTO activity_logs (user_id, aksi, model_type, model_id, keterangan, created_at) "
            "VALUES (NULL,'import','Siswa',NULL,?,?)", (f"{sumber}: {ringkasan}", self.now))

    # -- pemrosesan file ------------------------------------------------------
    def process_workbook(self, path, tahun, kind, opts=None):
        opts = opts or {}
        rel = os.path.relpath(path, SRC_DIR)
        st = self.stats[rel]
        wb = openpyxl.load_workbook(path, data_only=True, read_only=True)
        try:
            sheets = self._sheet_list(kind, wb, opts)
            for sheet_name in sheets:
                ws = wb[sheet_name]
                rows = list(ws.iter_rows(values_only=True))
                self._process_sheet(rows, sheet_name, tahun, kind, opts, rel, st)
        finally:
            wb.close()
        # ringkasan per file
        ringkas = (f"dibaca {st['dibaca']}, dibuat {st['dibuat']}, diperkaya {st['diperkaya']}, "
                   f"duplikat {st['duplikat']}, pindah_tahun {st['pindah_tahun']}, gagal {st['gagal']}, "
                   f"ledger {st['ledger']}, transkrip {st['transkrip']}")
        self.log_activity(rel, ringkas)
        return st

    def _sheet_list(self, kind, wb, opts):
        if kind == "transkrip":
            return ["all"] if "all" in wb.sheetnames else []
        if kind == "master_xlsx":
            return [s for s in wb.sheetnames if s in ("BIODATA", "LEGGER")]
        if kind == "master_xlsm":
            return [s for s in wb.sheetnames if s == "BIODATA" or s.startswith("SMSTR")]
        if kind == "kelasx":
            # Sheet paling besar (daftar master seluruh kohort, mis. 'XII T SIPIL'
            # di DATA SISWA 1617 non-REVISI) diproses TERAKHIR, supaya sheet
            # per-jurusan yang lebih spesifik menentukan jurusan saat pembuatan.
            sheets = list(wb.sheetnames)
            return sorted(sheets, key=lambda s: (wb[s].max_row or 0))
        return list(wb.sheetnames)

    def _process_sheet(self, rows, sheet_name, tahun, kind, opts, rel, st):
        # lewati sheet non-data
        if sheet_name.strip().upper() in ("HOME", "JURUSAN", "SEMESTER", "MAPEL", "FOTO",
                                          "EKSTRA", "PRIBADI", "HAL_PRINT", "KELAS 11",
                                          "KELAS 12", "REKAPITULASI", "LEMBAR6"):
            return
        if "IJASAH" in sheet_name.upper():
            # 'siswa16-17 yg blm ambil ijasah': tanpa kolom jenis kelamin; semua
            # siswanya sudah tercakup sheet utama file yang sama (hanya NIK
            # yang tidak ada kolomnya di skema). Dilewati, bukan gagal.
            st["sheet_dilewati"] += 1
            self.log_rows.append((rel, sheet_name, "", "SKIP",
                                  "sheet 'blm ambil ijasah' dilewati: tanpa kolom JK, siswa sudah tercakup sheet utama"))
            return
        hi = find_header_row(rows)
        if hi is None:
            # sheet 'siswa ... blm ambil ijasah' punya header di baris 4 tanpa kata NAMA? cek manual
            if kind == "kelasx" and "IJASAH" in sheet_name.upper():
                hi = 3  # baris ke-4 (0-based 3)
            else:
                st["sheet_tanpa_header"] += 1
                self.log_rows.append((rel, sheet_name, "", "SKIP", "sheet tanpa baris header NIS/Nama"))
                return
        header = [("" if c is None else str(c).strip()) for c in rows[hi]]
        colmap = map_columns(header)
        if "nis" not in colmap or "nama" not in colmap:
            st["sheet_tanpa_header"] += 1
            self.log_rows.append((rel, sheet_name, "", "SKIP", "header tidak memuat kolom NIS/Nama"))
            return
        # SMSTR .xlsm: baris 4-6 adalah sub-header (nama mapel, Peng/Ket, N/P/R);
        # data mulai baris 7 (indeks 6). Kolom 'R' di baris 6 = rata-rata mapel.
        data_start = hi + 1
        r_cols = None
        if kind == "master_xlsm" and sheet_name.startswith("SMSTR"):
            data_start = 6
            r_cols = [i for i, c in enumerate(rows[5]) if c is not None
                      and str(c).strip().upper() == "R" and i >= 4]
            self._smstr_row6 = rows[5]
        self._smstr_r_cols = r_cols
        self._current_rombel = None  # rombel blok saat ini (dari baris judul 'Kelas: ...')
        # Blok pertama: baris judul 'Kelas: ...' ada SEBELUM baris header.
        for _r in rows[:hi]:
            if _r and _r[0] is not None and str(_r[0]).strip() == "Kelas" and len(_r) > 3 and _r[3]:
                _val = str(_r[3]).strip().lstrip(":").strip()
                if _val:
                    self._current_rombel = _val
        for row in rows[data_start:]:
            if not row or all(v is None or str(v).strip() == "" for v in row):
                continue
            # Baris judul blok (KELAS X bertumpuk: 'Nama Sekolah' / 'Program
            # Keahlian' / 'Kelas: X TM B' di tengah sheet). Bukan data siswa.
            first_cell = str(row[0]).strip() if row[0] is not None else ""
            if first_cell in ("Nama Sekolah", "Program Keahlian", "Kelas",
                              "Tahun Ajaran", "Semester"):
                if first_cell == "Kelas" and len(row) > 3 and row[3]:
                    val = str(row[3]).strip().lstrip(":").strip()
                    if val:
                        self._current_rombel = val
                continue
            # Lewati diam-diam: baris template berisi nomor kolom (1..37) dan
            # baris pengulangan header di tengah data (bukan data siswa).
            vals = [v for v in row if v is not None and str(v).strip() not in ("", "-")]
            if vals and all(re.fullmatch(r"\d{1,3}", str(v).strip()) for v in vals):
                continue
            # Baris kosong berformat (hanya nomor urut + '-' placeholder):
            # bukan data siswa, lewati tanpa dihitung gagal.
            nis_cell = row[colmap["nis"]] if colmap["nis"] < len(row) else None
            nama_cell = row[colmap["nama"]] if colmap["nama"] < len(row) else None
            if (nis_cell is None or str(nis_cell).strip() in ("", "-")) and \
               (nama_cell is None or str(nama_cell).strip() in ("", "-")):
                continue
            nis_raw = str(nis_cell).strip().upper() if nis_cell is not None else ""
            nama_raw = str(nama_cell).strip().upper() if nama_cell is not None else ""
            if nis_raw in ("NO", "NIS", "NO INDUK") or nama_raw in ("NAMA SISWA", "NAMA"):
                continue
            if nama_raw.startswith(":"):
                # nilai blok judul (': SMK Negeri 5 Surakarta') di kolom nama
                continue
            d = row_to_dict(row, colmap)
            st["dibaca"] += 1
            self._process_row(d, header, row, sheet_name, tahun, kind, opts, rel, st)
        self._smstr_r_cols = None
        self._smstr_row6 = None
        self._current_rombel = None

    def _process_row(self, d, header, row, sheet_name, tahun, kind, opts, rel, st):
        # --- DATA MERGER: tidak ada kolom jenis kelamin -> hanya pengayaan
        # NISN/TTL + ledger untuk siswa yang sudah ada; siswa baru tidak dibuat
        # (kolom jenis_kelamin wajib diisi di skema dan tidak tersedia di file ini).
        if kind == "merger":
            nis = clean_nis(d.get("nis"))
            if not nis:
                st["gagal"] += 1
                self.log_rows.append((rel, sheet_name, "", "GAGAL", "MERGER: NIS kosong/tidak valid"))
                return
            ex = self.siswa_by_nis.get(nis)
            if not ex:
                st["gagal"] += 1
                self.log_rows.append((rel, sheet_name, nis, "GAGAL",
                                      "MERGER: NIS belum ada di DB dan file ini tidak memuat jenis kelamin (siswa baru tidak dibuat)"))
                return
            tgl_lahir = None
            if d.get("tgl_lahir") is not None:
                tgl_lahir = parse_date(d.get("tgl_lahir"))
            if tgl_lahir is None and (d.get("tgl") is not None or d.get("thn") is not None):
                tgl_lahir = parse_tgl_triple(d.get("tgl"), d.get("bln"), d.get("thn"))
            changed = self._enrich_fields(ex["id"], ex, {
                "nisn": clean_nisn(d.get("nisn")),
                "tempat_lahir": clean_str(d.get("tempat_lahir")),
                "tanggal_lahir": tgl_lahir.isoformat() if tgl_lahir else None,
            })
            st["diperkaya" if changed else "duplikat"] += 1
            avgs = grade_averages(header, row, max_semester=6)
            n = 0
            for sem, rata in avgs.items():
                n += 1 if self.add_ledger(ex["id"], tahun, sem, rata) else 0
            st["ledger"] += n
            return
        # --- ledger-only sheets (SMSTR / LEGGER): tanpa biodata baru ---
        if kind == "master_xlsm" and sheet_name.startswith("SMSTR"):
            sem = int(sheet_name.replace("SMSTR", "").strip() or 0)
            nis = clean_nis(d.get("nis"))
            if not nis:
                st["gagal"] += 1
                self.log_rows.append((rel, sheet_name, "", "GAGAL", "SMSTR: NIS kosong/tidak valid"))
                return
            ex = self.siswa_by_nis.get(nis)
            if not ex:
                st["gagal"] += 1
                self.log_rows.append((rel, sheet_name, nis, "GAGAL", "SMSTR: NIS tidak ditemukan di DB (ledger dilewati)"))
                return
            r_cols = getattr(self, "_smstr_r_cols", None) or []
            row6 = getattr(self, "_smstr_row6", None) or []
            def _num(v):
                return isinstance(v, (int, float)) and not isinstance(v, bool) and 0 <= v <= 100
            vals = [row[i] for i in r_cols if i < len(row) and _num(row[i])]
            if not vals:
                # fallback: nilai numerik di bawah sub-header N/P/K (SMSTR 2 tidak
                # punya kolom R; sebagian SMSTR 3-6 RPL A kosong)
                vals = [row[i] for i, c in enumerate(row6)
                        if i >= 4 and i < len(row) and c is not None
                        and str(c).strip().upper() in ("N", "P", "K") and _num(row[i])]
            rata = mean_or_none(vals)
            st["ledger"] += 1 if self.add_ledger(ex["id"], tahun, sem, rata) else 0
            return
        if kind == "master_xlsx" and sheet_name == "LEGGER":
            nis = clean_nis(d.get("nis"))
            if not nis:
                st["gagal"] += 1
                self.log_rows.append((rel, sheet_name, "", "GAGAL", "LEGGER: NIS kosong/tidak valid"))
                return
            ex = self.siswa_by_nis.get(nis)
            if not ex:
                st["gagal"] += 1
                self.log_rows.append((rel, sheet_name, nis, "GAGAL", "LEGGER: NIS tidak ditemukan di DB"))
                return
            avgs = grade_averages(header, row, max_semester=6)
            n = 0
            for sem, rata in avgs.items():
                n += 1 if self.add_ledger(ex["id"], tahun, sem, rata) else 0
            st["ledger"] += n
            return
        # --- baris biodata ---
        siswa, err = extract_biodata(d)
        if siswa is None and err and "Jenis kelamin" in err and kind == "datasiswa":
            # Format B (75 kolom, header 'Nama Panggilan'): sebagian blok memakai
            # susunan kolom Format A (tanpa 'Nama Panggilan'; JK ada di nis+6).
            # Deteksi per baris dan petakan ulang.
            hdr_norm = [norm_header(h) for h in header]
            if "NAMAPANGGILAN" in hdr_norm:
                nis_idx = next((i for i, h in enumerate(hdr_norm) if h in ("NIS", "NOINDUK")), None)
                if nis_idx is not None and nis_idx + 6 < len(row) and clean_jk(row[nis_idx + 6]) is not None:
                    d2 = self._format_a_dict(row, nis_idx)
                    siswa2, err2 = extract_biodata({**d, **d2})
                    if siswa2 is not None:
                        siswa, err, d = siswa2, err2, {**d, **d2}
        if siswa is None:
            st["gagal"] += 1
            self.log_rows.append((rel, sheet_name, clean_str(d.get("nis")) or "", "GAGAL", err or "baris tidak valid"))
            return
        # jurusan + kelas
        jurusan_kode, tingkat, nama_kelas = self._jurusan_kelas(
            sheet_name, tahun, kind, opts, siswa, d)
        if jurusan_kode is None:
            st["gagal"] += 1
            self.log_rows.append((rel, sheet_name, siswa["nis"], "GAGAL",
                                  f"jurusan tidak dapat dipetakan (sheet='{sheet_name}', rombel='{siswa.get('rombel_raw')}')"))
            return
        # tanggal masuk khusus transkrip: prefix NIS ~= tahun masuk
        if kind == "transkrip" and not siswa.get("tanggal_masuk"):
            pref = siswa["nis"][:2]
            if pref.isdigit() and 13 <= int(pref) <= 25:
                siswa["tanggal_masuk"] = f"20{pref}-07-01"
        graduating = kind in ("nominatif", "transkrip")
        aksi = self.upsert_siswa(siswa, tahun, jurusan_kode, tingkat, nama_kelas,
                                 rel, graduating_doc=graduating)
        st[aksi] += 1
        if aksi == "dibuat":
            self.log_rows.append((rel, sheet_name, siswa["nis"], "DIBUAT", ""))
        elif aksi == "pindah_tahun":
            self.log_rows.append((rel, sheet_name, siswa["nis"], "PINDAH_TAHUN",
                                  f"dipindah ke tahun ajaran {tahun} (dokumen kelulusan)"))
        # ledger dari kolom nilai (merger / transkrip)
        sid = self.siswa_by_nis[siswa["nis"]]["id"]
        if kind in ("merger", "transkrip"):
            max_sem = 6 if kind == "merger" else 5
            avgs = grade_averages(header, row, max_semester=max_sem)
            n = 0
            for sem, rata in avgs.items():
                n += 1 if self.add_ledger(sid, tahun, sem, rata) else 0
            st["ledger"] += n
        # transkrip (nomor ijazah)
        if kind == "transkrip" and siswa.get("nomor_ijazah"):
            if self.add_transkrip(sid, tahun, siswa["nomor_ijazah"]):
                st["transkrip"] += 1

    def _format_a_dict(self, row, nis_idx):
        """Petakan satu baris Format A berdasarkan posisi relatif terhadap kolom NIS.
        Dipakai untuk blok Format B yang susunan kolomnya mengikuti Format A."""
        def _c(off):
            i = nis_idx + off
            return row[i] if 0 <= i < len(row) else None
        return {
            "nama": _c(1), "tempat_lahir": _c(2), "tgl": _c(3), "bln": _c(4),
            "thn": _c(5), "jk": _c(6), "nama_ayah": _c(7), "nama_ibu": _c(8),
            "pek_ortu": _c(9), "alamat": _c(11), "agama": _c(15),
            "no_hp": _c(16), "no_hp_ortu": _c(17), "nama_wali": _c(18),
            "asal_sekolah": _c(22), "rombel": _c(30), "tgl_masuk": _c(35),
        }

    def _jurusan_kelas(self, sheet_name, tahun, kind, opts, siswa, d):
        """Tentukan (kode_jurusan, tingkat, nama_kelas) dari konteks sheet/kolom."""
        sn = sheet_name.strip()
        if kind == "datasiswa":
            kode = canon_jurusan(sn)
            if kode is None:
                return None, None, None
            # rombel dari kolom ROMBEL bila format "KODE HURUF" (mis. "TL A")
            tingkat, nama_kelas = "XII", f"XII {kode}"
            rb = siswa.get("rombel_raw")
            if rb:
                m = re.match(r"^([A-Z]+)\s+([A-D])$", rb.strip().upper())
                if m and (canon_jurusan(m.group(1)) == kode):
                    nama_kelas = f"XII {kode} {m.group(2)}"
            return kode, tingkat, nama_kelas
        if kind == "kelasx":
            # Rombel blok saat ini (dari baris judul 'Kelas: X TM B') paling akurat.
            cur = getattr(self, "_current_rombel", None)
            if cur:
                t, k, nk = parse_kelas_label(cur)
                if k is not None:
                    return k, (t or "X"), nk
            # "X T SIPIL" / "XII T SIPIL" -> tingkat + jurusan dari nama sheet
            t, k, nk = parse_kelas_label(sn)
            if k is not None:
                return k, (t or "X"), nk
            kode = KELASX_SHEET.get(sn.upper()) or canon_jurusan(sn)
            if kode is None:
                # sheet 'Siswa mutasi': jurusan dari kolom Kelas per baris
                t2, k2, nk2 = parse_kelas_label(siswa.get("rombel_raw"))
                if k2 is not None:
                    return k2, (t2 or "XII"), nk2
                return None, None, None
            return kode, "X", f"X {kode}"
        if kind == "merger":
            kode = canon_jurusan(sn)
            if kode is None:
                return None, None, None
            tingkat, nama_kelas = "XII", f"XII {kode}"
            rb = siswa.get("rombel_raw")
            if rb:
                t, k, nk = parse_kelas_label(rb)
                if k == kode and nk:
                    nama_kelas = nk
            return kode, tingkat, nama_kelas
        if kind == "master_xlsx":
            kode = canon_jurusan(siswa.get("jurusan_kolom"))
            if kode is None:
                return None, None, None
            return kode, "XII", f"XII {kode}"
        if kind == "master_xlsm":
            kode = canon_jurusan(opts.get("jurusan_hint"))
            if kode is None:
                return None, None, None
            return kode, "XII", f"XII {kode}"
        if kind == "nominatif":
            t, k, nk = parse_kelas_label(sn)
            if k is None:
                return None, None, None
            return k, (t or "XII"), nk
        if kind == "transkrip":
            t, k, nk = parse_kelas_label(siswa.get("rombel_raw"))
            if k is None:
                # fallback: konsentrasi keahlian
                k = canon_jurusan(d.get("konsentrasi")) or canon_jurusan(d.get("program_keahlian"))
                if k is None:
                    return None, None, None
                t, nk = "XII", f"XII {k}"
            return k, (t or "XII"), nk
        return None, None, None

# ---------------------------------------------------------------------------
# Daftar sumber (berurutan: sumber utama dulu, pengayaan sesudahnya)
# File duplikat yang sengaja TIDAK diproses dicatat di SKIPPED_FILES.
# ---------------------------------------------------------------------------
SOURCES = [
    ("2013-2014", "datasiswa", "2013-2014/DB DATA SISWA.xlsx", {}),
    ("2014-2015", "datasiswa", "2014-2015/DB DATA SISWA 1415.xlsx", {}),
    ("2015-2016", "datasiswa", "2015-2016/DATA SISWA 1516.xlsx", {}),
    ("2016-2017", "datasiswa", "2016-2017/DATA SISWA 1617 REVISI.xlsx", {}),
    # Format C (ada NISN): pengayaan NISN/NIK/kelas untuk kohort 2016-2017.
    # Nama sheet-nya menyesatkan (sheet 'XII T SIPIL' memuat seluruh kohort),
    # jadi jurusan TIDAK diambil dari nama sheet file ini.
    ("2016-2017", "kelasx", "2016-2017/DATA_SISWA_1617_NONREVISI.xlsx", {}),
    ("2017-2018", "datasiswa", "2017-2018/DATA SISWA 1718.xlsx", {}),
    ("2018-2019", "datasiswa", "2018-2019/DATA SISWA 1819.xlsx", {}),
    ("2018-2019", "kelasx", "2018-2019/KELAS X.xlsx", {}),
    ("2019-2020", "datasiswa", "2019-2020/DATA SISWA 1920.xlsx", {}),
    ("2019-2020", "kelasx", "2019-2020/KELAS X.xlsx", {}),
    ("2019-2020", "merger", "2019-2020/DATA MERGER 2020.xlsx", {}),
    ("2020-2021", "datasiswa", "2020-2021/DATA SISWA 2021.xlsx", {}),
    ("2020-2021", "merger", "2020-2021/DATA MERGER 2021.xlsx", {}),
    ("2020-2021", "master_xlsx", "2020-2021/MASTER.xlsx", {}),
    ("2021-2022", "nominatif", "2021-2022/DATA NOMINATIF SISWA KELAS XII TP 2021_2022.xlsx", {}),
    ("2021-2022", "nominatif", "2021-2022/DATA NOMINATIF SISWA KELAS XIII TP 2021_2022.xlsx", {}),
    ("2021-2022", "master_xlsm", "2021-2022/MASTER-TEI.xlsm", {"jurusan_hint": "TEI"}),
    ("2021-2022", "master_xlsm", "2021-2022/MASTER [RPL A].xlsm", {"jurusan_hint": "RPL"}),
    ("2025-2026", "transkrip", "2025-2026/DATABASE TRANSKRIP 2025-2026.xlsx", {}),
]

# File yang sengaja tidak diproses (duplikat dari file lain yang sudah dipakai)
SKIPPED_FILES = [
    ("2016-2017/DATA SISWA 1617.xlsx (bukan REVISI) — DIPROSES sebagai pengayaan NISN",
     "dipakai sebagai pengayaan (kind=kelasx), bukan sumber utama"),
    ("2021-2022/MASTER.xlsm",
     "duplikat MASTER-TEI.xlsm: 35 siswa yang sama (overlap 34-35 NIS); semua sudah tercakup DATA NOMINATIF XII"),
    ("2021-2022/MASTER-edit.xlsm",
     "duplikat MASTER-TEI.xlsm: siswa yang sama"),
    ("2021-2022/MASTERrR.xlsm",
     "duplikat MASTER-TEI.xlsm: siswa yang sama"),
]

# File yang TIDAK bisa diimport aman -> butuh keputusan manual
MANUAL_FILES = [
    ("2013-2014/Buku Induk 2013-2014 _  kelas.xlsx",
     "File terenkripsi (password). Password di 'password buku induk 2013-2014.txt' TIDAK cocok (dicoba via msoffcrypto). "
     "Kemungkinan besar hanya formulir cetak yang sama dengan DATA SISWA 2013-2014 (kohort sudah terimport)."),
    ("2014-2015/Buku Induk 2014-2015.xlsx",
     "File terenkripsi (password). Kedua password .txt yang ada di Drive TIDAK cocok. Kohort sudah terimport via DATA SISWA 1415."),
    ("2015-2016/Buku Induk 2015-2016 _19 kelas.xlsx",
     "File terenkripsi (password). Tidak ada file password .txt untuk tahun ini di Drive. Kohort sudah terimport via DATA SISWA 1516."),
    ("2017-2018/Buku Induk 2017-2018 _ 22 kelas.xlsx",
     "File terenkripsi (password). Password di 'password buku induk 2017-2018.txt' TIDAK cocok. Kohort sudah terimport via DATA SISWA 1718."),
    ("2016-2017/Buku Induk 2016-2017 _ 19 kelas.xlsx",
     "Tidak terenkripsi, tapi berupa formulir cetak bertumpuk (22 sheet, ~1500-1800 baris/sheet, satu siswa per blok). "
     "Kohort yang sama sudah terimport via DATA SISWA 1617 REVISI + pengayaan NISN. Parsing formulir cetak tidak sebanding manfaatnya."),
    ("2013-2014/TEI-1314.xlsx, TO-1314.xlsx, dan file rapor per-jurusan sejenis (TEI/TIK-1819, TEI-1920, TEI-2021)",
     "Bukan biodata massal: sheet BIODATA berisi formulir satu siswa ('KETERANGAN TENTANG DIRI PESERTA DIDIK'), "
     "bukan tabel. Kohort yang sama sudah terimport dari DATA SISWA."),
    ("2021-2022/DATA-SEKOLAH.xlsx",
     "Isinya daftar 665 sekolah asal (SMP) — bukan data siswa dan bukan identitas SMKN 5; tidak ada tabel yang cocok di skema. "
     "Disimpan sebagai referensi di storage/imports/source/2021-2022/."),
    ("2020-2021/SUDAH DI RANKING/ (SEMESTER 1-8, ~150 file ledger per kelas)",
     "Ledger per kelas per semester sudah tercakup agregatnya via DATA MERGER (rata-rata semester 1-6) dan "
     "MASTER SMSTR (2021-2022). File-file ini tidak diimport satu per satu; bisa diimport belakangan bila sekolah meminta detail per mapel."),
]

def prepare_db(fresh=False):
    """Siapkan database-imported.sqlite:
    - backup database.sqlite -> database.sqlite.contoh.bak (bila belum ada)
    - salin database.sqlite -> database-imported.sqlite (bila --fresh / belum ada)
    - hapus 24 siswa contoh fiktif (NIS 2025xxxx) dari salinan
    """
    if not os.path.exists(DB_BACKUP):
        shutil.copy2(DB_EXAMPLE, DB_BACKUP)
        print(f"Backup contoh DB: {DB_BACKUP}")
    if fresh or not os.path.exists(DB_IMPORT):
        shutil.copy2(DB_EXAMPLE, DB_IMPORT)
        print(f"Salinan DB dibuat: {DB_IMPORT}")
    conn = sqlite3.connect(DB_IMPORT)
    cur = conn.cursor()
    n = cur.execute("SELECT COUNT(*) FROM siswa WHERE nis LIKE '2025%'").fetchone()[0]
    if n:
        cur.execute("DELETE FROM siswa WHERE nis LIKE '2025%'")
        # bersihkan anak yatim yang mungkin tertinggal
        cur.execute("DELETE FROM orang_tua WHERE siswa_id NOT IN (SELECT id FROM siswa)")
        cur.execute("DELETE FROM riwayat_kelas WHERE siswa_id NOT IN (SELECT id FROM siswa)")
        cur.execute("DELETE FROM ledger WHERE siswa_id NOT IN (SELECT id FROM siswa)")
        cur.execute("DELETE FROM transkrip WHERE siswa_id NOT IN (SELECT id FROM siswa)")
        conn.commit()
        print(f"Dihapus {n} siswa contoh fiktif (NIS 2025xxxx) dari salinan DB.")
    conn.close()
    return DB_IMPORT

def main():
    ap = argparse.ArgumentParser(description="Import Buku Induk SMKN 5 dari Excel ke SQLite Laravel")
    ap.add_argument("--dry-run", action="store_true", help="jalankan penuh lalu buang hasilnya (DB tidak berubah)")
    ap.add_argument("--fresh", action="store_true", help="buat ulang database-imported.sqlite dari database.sqlite")
    ap.add_argument("--tahun", help="hanya tahun ajaran ini, mis. 2019-2020")
    ap.add_argument("--file", help="hanya file sumber ini (path relatif terhadap storage/imports/source)")
    ap.add_argument("--db", default=None, help="path DB SQLite (default: database-imported.sqlite)")
    args = ap.parse_args()

    if args.dry_run:
        # salinan sementara dari DB saat ini; dibuang setelah selesai
        src_db = args.db or (DB_IMPORT if os.path.exists(DB_IMPORT) else DB_EXAMPLE)
        tmp = tempfile.NamedTemporaryFile(suffix=".sqlite", delete=False)
        tmp.close()
        shutil.copy2(src_db, tmp.name)
        db_path = tmp.name
        print(f"[DRY-RUN] memakai salinan sementara {db_path} (DB asli tidak berubah)")
    else:
        db_path = args.db or prepare_db(fresh=args.fresh)

    imp = Importer(db_path, dry_run=args.dry_run)
    todo = [s for s in SOURCES
            if (not args.tahun or s[0] == args.tahun)
            and (not args.file or s[2] == args.file)]
    if args.file and not todo:
        print(f"File tidak dikenal di daftar sumber: {args.file}")
        return 2
    for tahun, kind, rel, opts in todo:
        path = os.path.join(SRC_DIR, rel)
        if not os.path.exists(path):
            print(f"  ! file tidak ditemukan, dilewati: {rel}")
            imp.stats[rel]["file_hilang"] += 1
            continue
        print(f"[{tahun}] {kind:12s} {rel}")
        try:
            imp.process_workbook(path, tahun, kind, opts)
        except Exception as e:
            print(f"  !! GAGAL memproses {rel}: {type(e).__name__}: {e}")
            imp.stats[rel]["error_file"] += 1
        st = imp.stats[rel]
        print(f"      dibaca={st['dibaca']} dibuat={st['dibuat']} diperkaya={st['diperkaya']} "
              f"duplikat={st['duplikat']} pindah_tahun={st['pindah_tahun']} gagal={st['gagal']} "
              f"ledger={st['ledger']} transkrip={st['transkrip']}")
    imp.conn.commit()

    # ---- ringkasan DB akhir ----
    cur = imp.conn.cursor()
    total_siswa = cur.execute("SELECT COUNT(*) FROM siswa").fetchone()[0]
    total_ortu = cur.execute("SELECT COUNT(*) FROM orang_tua").fetchone()[0]
    total_ledger = cur.execute("SELECT COUNT(*) FROM ledger").fetchone()[0]
    total_transkrip = cur.execute("SELECT COUNT(*) FROM transkrip").fetchone()[0]
    total_riwayat = cur.execute("SELECT COUNT(*) FROM riwayat_kelas").fetchone()[0]
    per_tahun = cur.execute(
        "SELECT ta.nama, j.kode, COUNT(*) c FROM siswa s "
        "JOIN tahun_ajaran ta ON ta.id=s.tahun_ajaran_id "
        "JOIN jurusan j ON j.id=s.jurusan_id "
        "GROUP BY ta.nama, j.kode ORDER BY ta.nama, j.kode").fetchall()
    per_status = cur.execute("SELECT status, COUNT(*) FROM siswa GROUP BY status").fetchall()

    summary = {
        "db": db_path,
        "dry_run": args.dry_run,
        "total_siswa": total_siswa,
        "total_orang_tua": total_ortu,
        "total_ledger": total_ledger,
        "total_transkrip": total_transkrip,
        "total_riwayat_kelas": total_riwayat,
        "per_status": {r[0]: r[1] for r in per_status},
        "per_tahun_jurusan": [{"tahun": r[0], "jurusan": r[1], "jumlah": r[2]} for r in per_tahun],
        "per_sumber": {k: dict(v) for k, v in sorted(imp.stats.items())},
        "skipped_files": SKIPPED_FILES,
        "manual_files": MANUAL_FILES,
        "gagal_terbanyak": Counter(a for (_, _, _, _, a) in
                                  [tuple(x) for x in imp.log_rows if x[3] == "GAGAL"]).most_common(15),
    }
    ts = datetime.now().strftime("%Y%m%d-%H%M%S")
    with open(os.path.join(WORK_DIR, f"import_summary_{ts}.json"), "w") as f:
        json.dump(summary, f, indent=2, ensure_ascii=False)
    with open(os.path.join(WORK_DIR, "import_summary_latest.json"), "w") as f:
        json.dump(summary, f, indent=2, ensure_ascii=False)
    with open(os.path.join(WORK_DIR, f"import_log_{ts}.csv"), "w", newline="") as f:
        w = csv.writer(f)
        w.writerow(["sumber", "sheet", "nis", "aksi", "catatan"])
        w.writerows(imp.log_rows)

    print("\n================ RINGKASAN ================")
    print(f"Total siswa di DB: {total_siswa} | orang_tua: {total_ortu} | ledger: {total_ledger} | "
          f"transkrip: {total_transkrip} | riwayat_kelas: {total_riwayat}")
    print("Per status:", dict(summary["per_status"]))
    print("\nPer tahun ajaran x jurusan (DB akhir):")
    for r in per_tahun:
        print(f"  {r[0]}  {r[1]:6s} {r[2]}")
    tot = Counter()
    for v in imp.stats.values():
        tot.update(v)
    print(f"\nTotal per sumber: dibaca={tot['dibaca']} dibuat={tot['dibuat']} diperkaya={tot['diperkaya']} "
          f"duplikat={tot['duplikat']} pindah_tahun={tot['pindah_tahun']} gagal={tot['gagal']} "
          f"ledger={tot['ledger']} transkrip={tot['transkrip']}")
    print(f"Alasan gagal terbanyak: {summary['gagal_terbanyak']}")
    print(f"Log detail: {WORK_DIR}/import_log_{ts}.csv | Ringkasan: {WORK_DIR}/import_summary_latest.json")
    if args.dry_run:
        os.unlink(db_path)
        print("[DRY-RUN] salinan sementara dibuang; DB asli tidak berubah.")
    return 0

if __name__ == "__main__":
    sys.exit(main())
