#!/usr/bin/env python3
"""Import nilai per mapel dari file Transkrip/Merger (.xls -> .xlsx) ke database.

Sumber: file "Data Transkrip Nilai" (Pengetahuan) + "Data Merger" dari Drive,
satu sheet per jurusan, satu kolom per (mapel, semester). Kohort yang dicakup:
2013-2014 s.d. 2019-2020 (yang 2020-2021 & 2021-2022 sudah diimport via Leger).

Dua format header:
  - Lama (BELMOT 2013-2015, KTSP): kode P_AGAMA_1..6, B_IND, MATEMATIKA,
    IPA, FISIKA, KIMIA, IPS, KKPI, KWU, ... + nama deskriptif kompetensi
    ("Menerapkan Dasar-dasar Gambar Teknik Sem1"). Semua nilai skala 0-100.
  - Baru (2016+, K2013): kode P_AG_1..6 / PAPB_1..6, BI, MAT, SEJ, BING,
    SB, KU, ORKES, JAWA, FIS, KIM, SimDig + kode kompetensi (Gb Tek, MekTek,
    ...). Nilai bisa skala 1-4 (predikat K2013) ATAU 0-100, bahkan tercampur
    dalam satu kolom/sheet — file "(MAX 100)" adalah versi yang sudah
    dikonversi sekolah ke 0-100 (x25). Di sini konversi dilakukan per SEL:
    nilai (0, 4] dianggap skala 1-4 -> x25 (terbukti sama dengan versi
    "(MAX 100)" sekolah: 3.86 -> 96.5, 3.81 -> 95.25).

Aturan:
  - Cocokkan siswa by NIS (file lama tidak punya NISN; file baru punya
    NISN tapi NIS cukup). NIS tidak ditemukan -> catat, lewati.
  - Tahun ajaran per siswa dari tahun_ajaran siswa itu sendiri (Y = lulus):
    smt 1-2 -> Y-2, smt 3-4 -> Y-1, smt 5-6 -> Y, smt 7-8 (TOI) -> Y+1.
    (TOI 4 tahun: tahun_ajaran di DB adalah tahun XII, jadi XIII = Y+1;
    sesuai nilai TOI 2019-2020 yang sudah ada dari import Leger 2020-2021.)
  - Nilai 0 dilewati (placeholder, bukan nilai asli).
  - Mapel yang belum ada dibuat sebagai khusus jurusan (C2 bila pertama
    muncul di X, C3 bila di XI/XII); mapel umum dipakai apa adanya.
    IPA/IPS (KTSP) dibuat sebagai mapel UMUM kelompok A (mata pelajaran
    nasional, berlaku semua jurusan) karena tidak ada padanannya di DB.
  - Idempotent: INSERT OR IGNORE. Bila nilai sudah ada dan BERBEDA, nilai
    lama DIPERTAHANKAN (yang sudah ada berasal dari Leger per kelas /
    import sebelumnya yang lebih tepercaya daripada transkrip merger);
    perbedaan dicatat sebagai konflik untuk ditinjau.
  - Ledger: INSERT OR IGNORE saja (tidak pernah menimpa). Rata-rata =
    rata-rata nilai Pengetahuan semester itu (file transkrip tidak punya
    kolom Keterampilan/R; untuk kohort 2013-2019 belum ada ledger sama
    sekali, jadi ini pengisi pertama; untuk 2019-2020 yang sudah ada
    dipertahankan).
  - Koreksi jurusan (flag --koreksi, default menyala): siswa yang di DB
    tertulis TS (Teknik Sipil) tetapi di file kelasnya jelas TGB/TKB/TKK
    (2015-2019) atau BKP/DPIB (2019-2020) dikoreksi jurusan_id-nya,
    mengikuti pola koreksi 2020-2021 (TS->BKP/DPIB). Hanya siswa TS yang
    dikoreksi; siswa yang jurusannya sudah spesifik tidak disentuh
    (kontaminan sheet dicatat sebagai peringatan).

Manifest (file, sheet, kohort) dipilih dari hasil sensus: file "Merger"
dan "Transkrip" yang isinya sama hanya dipakai satu (yang utama); sheet
"(MAX 100)" dipakai untuk 2016-2017 (versi 0-100); sheet khusus
("TGB (Khusus NIS 16105", "TO (Ismail Topo)") berisi nilai satu siswa
yang kosong di sheet utama -> dipakai sebagai pelengkap.
"""
import argparse
import re
import sqlite3
import sys
from collections import Counter, defaultdict
from pathlib import Path

import openpyxl

BASE = Path(__file__).resolve().parent.parent.parent  # ~/workspace/buku-induk-smkn5
SRC = BASE / "storage" / "imports" / "source" / "transkrip"
DB = BASE / "database" / "database-imported.sqlite"

SIKAP = {"A": "Amat Baik", "B": "Baik", "C": "Cukup", "D": "Kurang"}

# Kode mapel bersama -> nama di DB (format lama KTSP + format baru K2013).
KODE_MAPEL = {
    # Lama (BELMOT)
    "P_AGAMA": "Pendidikan Agama Islam dan Budi Pekerti",
    "B_IND": "Bahasa Indonesia",
    "MATEMATIKA": "Matematika (Umum)",
    "B_INGGRIS": "Bahasa Inggris",
    "IPA": "Ilmu Pengetahuan Alam",
    "FISIKA": "Fisika",
    "KIMIA": "Kimia",
    "IPS": "Ilmu Pengetahuan Sosial",
    "KKPI": "Simulasi dan Komunikasi Digital",
    "KWU": "Produk Kreatif dan Kewirausahaan",
    "B_JAWA": "Bahasa Jawa",
    "PENJAS": "Pendidikan Jasmani, Olahraga dan Kesehatan",
    "SEN_BUD": "Seni Budaya",
    "PKn": "Pendidikan Pancasila dan Kewarganegaraan",
    "Pkn": "Pendidikan Pancasila dan Kewarganegaraan",
    # Baru (K2013)
    "P_AG": "Pendidikan Agama Islam dan Budi Pekerti",
    "PAPB": "Pendidikan Agama Islam dan Budi Pekerti",
    "BI": "Bahasa Indonesia",
    "MAT": "Matematika (Umum)",
    "SEJ": "Sejarah Indonesia",
    "BING": "Bahasa Inggris",
    "SB": "Seni Budaya",
    "KU": "Produk Kreatif dan Kewirausahaan",
    "ORKES": "Pendidikan Jasmani, Olahraga dan Kesehatan",
    "JAWA": "Bahasa Jawa",
    "BJAWA": "Bahasa Jawa",
    "FIS": "Fisika",
    "KIM": "Kimia",
    "SimDig": "Simulasi dan Komunikasi Digital",
}

# Mapel umum baru yang perlu dibuat (nama -> kelompok, urutan).
MAPEL_UMUM_BARU = {
    "Ilmu Pengetahuan Alam": ("A", 7),
    "Ilmu Pengetahuan Sosial": ("A", 8),
}

# Nama sheet -> kode jurusan di DB.
SHEET_JURUSAN = {
    "TGB": "TGB", "TKB": "TKB", "TKK": "TKK", "TITL": "TITL", "TL": "TITL",
    "TEI": "TEI", "TE": "TEI", "TM": "TPM", "TO": "TO", "TKR": "TO",
    "TSM": "TO", "TIK": "RPL", "RPL": "RPL", "TOI": "TOI", "TKRO": "TKRO",
    "TBSM": "TBSM", "BKP": "BKP", "DPIB": "DPIB",
}

# Koreksi jurusan: sheet (program) -> jurusan DB, hanya untuk siswa TS.
KOREKSI_TS = {"TGB": "TGB", "TKB": "TKB", "TKK": "TKK", "BKP": "BKP", "DPIB": "DPIB"}

# Manifest: (file, [sheet], kohort lulus). File .xlsx hasil konversi ada di
# SRC/xlsx, kecuali DataMerger2020 & NilaiIjazah2020 (sudah .xlsx di SRC).
MANIFEST = [
    ("xlsx/TP1314_BELMOT.xlsx",
     ["TGB", "TKB", "TKK", "TITL", "TEI", "TM", "TO", "RPL"], "2013-2014"),
    ("xlsx/Merger2014_BELMOT.xlsx",
     ["TGB", "TKB", "TKK", "TITL", "TEI", "TM", "TO", "RPL"], "2013-2014"),
    ("xlsx/TP1516_BELMOT.xlsx",
     ["TGB", "TKB", "TKK", "TITL", "TEI", "TM", "TO", "RPL",
      "TGB (Khusus NIS 16105", "TO (Ismail Topo)"], "2014-2015"),
    ("xlsx/Merger2015_BELMOT.xlsx",
     ["TGB", "TKB", "TKK", "TITL", "TEI", "TM", "TO", "RPL"], "2014-2015"),
    ("xlsx/Transkrip2016_Pengetahuan.xlsx",
     ["TIK", "TO", "TM", "TE", "TL", "TKK", "TKB", "TGB"], "2015-2016"),
    ("xlsx/Merger2016.xlsx",
     ["TGB", "TKB", "TKK", "TITL", "TEI", "TM", "TO", "RPL"], "2015-2016"),
    ("xlsx/Transkrip2017_Pengetahuan.xlsx",
     ["TIK (MAX 100)", "TO (MAX 100)", "TM (MAX 100)", "TE (MAX 100)",
      "TL (MAX 100)", "TKK (MAX 100)", "TKB (MAX 100)", "TGB (MAX 100)"],
     "2016-2017"),
    ("xlsx/Merger2018_BELMOT.xlsx",
     ["TGB", "TKB", "TKK", "TITL", "TEI", "TM", "TO", "RPL"], "2017-2018"),
    ("xlsx/Transkrip2018_Pengetahuan.xlsx",
     ["TL", "TKK", "TKB", "TGB", "TIK", "TM", "TKR", "TE"], "2017-2018"),
    ("xlsx/Transkrip2019_Pengetahuan.xlsx",
     ["TOI", "TL", "TKK", "TKB", "TGB", "RPL", "TM", "TSM", "TKR", "TE"],
     "2018-2019"),
    ("DataMerger2020.xlsx",
     ["TEI", "RPL", "TL", "TKRO", "TBSM", "TM", "BKP", "TOI", "DPIB"],
     "2019-2020"),
    ("NilaiIjazah2020_R1.xlsx",
     ["Rapor Sem 1 sd 6 P TOI (AB)"], "2019-2020"),
]

# Kolom identitas (bukan mapel) yang dilewati bila muncul sebagai header.
BUKAN_MAPEL = {
    "halaman", "hlm", "nis", "nisn", "nama siswa", "nama_siswa", "sekolah",
    "asal sekolah", "pro_keahlian", "pro_studi", "kom_keahlian", "komp_keahlian",
    "rombel", "tempat_lhr", "tgl_lhr", "bl_lhr", "th_lhr", "no_pst",
    "no", "no pst", "c1 das bidang keahlian", "c2 dasar program keahlian",
    "c3 kompetensi keahlian sem 1 sd 5", "rata2 rapor produktip",
    "c3 kompetensi keahlian rerata sem 1 sd sem 5",
    "c1 das bidang keahlian", "c2 dasar program keahlian",
    "c3 paket keahlian", "simdig sem 1 5", "fisika sem 1 5",
    "kimia sem 1 5", "dasar program keahlian sem 1 5",
}

SUFFIX_N = re.compile(r"_(\d)$")
# Sufiks semester di ujung nama mapel: "Sem3", "Sm3", "Ssem6", "Semester 3",
# "(Mulok Sem3)". Pola "s?sem|sm" menangani "Sem", "Ssem", dan "Sm".
SEM_TXT = re.compile(r"(?:\(mulok\s+)?(?:s?sem(?:ester)?|sm)\s*(\d)\s*\)?\s*$", re.I)
TYPO = {"diinding": "dinding", "penegatahuan": "pengetahuan",
        "penegetahuan": "pengetahuan", "dasa": "dasar", "dasar": "Dasar",
        "ssem": "sem", "ttingkat": "tingkat",
        "elektonika": "elektronika", "oprasi": "operasi",
        "statistika": "statika", "iilmu": "ilmu"}


def normal(s):
    """Kunci pencocokan nama mapel (dipakai juga oleh import Leger)."""
    if s is None:
        return ""
    s = str(s).strip().lower().replace("&", " dan ")
    s = re.sub(r"[.,'\"`]", " ", s)
    return re.sub(r"\s+", " ", s).strip()


def kunci(s):
    """Kunci agresif: hanya alfanumerik, kecil — menggabungkan varian
    spasi/huruf besar ("Gb Tek" = "GbTek" = "GBTek")."""
    return re.sub(r"[^a-z0-9]", "", normal(s))


def kanonik(nama):
    """Nama mapel yang disimpan di DB."""
    s = re.sub(r"\s+", " ", str(nama).strip().replace("&", "dan"))
    for salah, benar in TYPO.items():
        s = re.sub(rf"\b{salah}\b", benar, s, flags=re.I)
    # Perbaikan typo bisa membuat huruf pertama kecil ("penegetahuan" ->
    # "pengetahuan"); nama mapel selalu diawali huruf kapital.
    if s:
        s = s[0].upper() + s[1:]
    return s


def pisah_semester(nama):
    """(nama_mapel_tanpa_semester, semester|None). Semester dari sufiks _N
    atau 'Sem N' / '(Mulok SemN)' di ujung nama."""
    nm = str(nama).strip()
    m = SUFFIX_N.search(nm)
    if m:
        return nm[:m.start()].strip(), int(m.group(1))
    m = SEM_TXT.search(nm)
    if m:
        dasar = SEM_TXT.sub("", nm).strip().rstrip("(").strip()
        return dasar, int(m.group(1))
    return nm, None


def angka_100(v):
    """Nilai ke skala 0-100. Nilai (0,4] = skala 1-4 -> x25. 0 dilewati
    (placeholder). String angka (file 'Format Text') ikut diparse."""
    if isinstance(v, bool):
        return None
    if isinstance(v, (int, float)):
        f = float(v)
    elif isinstance(v, str):
        v = v.strip().lstrip("'").replace(",", ".")
        if v in ("", "-"):
            return None
        try:
            f = float(v)
        except ValueError:
            return None
    else:
        return None
    if f == 0:
        return None
    if 0 < f <= 4.0:
        return round(f * 25, 2)
    if 4.0 < f <= 100:
        return f
    return None


def nis_str(v):
    if isinstance(v, bool):
        return None
    if isinstance(v, (int, float)):
        return str(int(v))
    if isinstance(v, str):
        v = v.strip().lstrip("'")
        return v or None
    return None


class Importer:
    def __init__(self, db_path):
        self.con = sqlite3.connect(db_path)
        self.con.row_factory = sqlite3.Row
        self.tahun_id = {r["nama"]: r["id"]
                         for r in self.con.execute("SELECT id, nama FROM tahun_ajaran")}
        self.jurusan_id = {r["kode"]: r["id"]
                           for r in self.con.execute("SELECT id, kode FROM jurusan")}
        self.siswa = {}  # nis -> {id, tahun_ajaran_id, jurusan_id}
        for r in self.con.execute("SELECT id, nis, tahun_ajaran_id, jurusan_id FROM siswa"):
            if r["nis"]:
                self.siswa[str(r["nis"]).strip()] = dict(r)
        self.tahun_nama = {i: n for n, i in self.tahun_id.items()}
        self.mapel = {}       # kunci(nama) + jurusan_id -> row
        self.mapel_umum = {}  # kunci(nama) -> row (jurusan NULL)
        for r in self.con.execute("SELECT id, nama, kelompok, jurusan_id, tingkat, urutan FROM mapel"):
            self.mapel[(kunci(r["nama"]), r["jurusan_id"])] = dict(r)
            if r["jurusan_id"] is None:
                self.mapel_umum.setdefault(kunci(r["nama"]), dict(r))
        self.stat = Counter()
        self.nis_hilang = []
        self.konflik = []
        self.koreksi = []
        self.peringatan = []

    def tingkat(self, smt):
        return "X" if smt <= 2 else ("XI" if smt <= 4 else ("XII" if smt <= 6 else "XIII"))

    def tahun_untuk(self, siswa_tahun_nama, smt):
        """Tahun ajaran nilai dari tahun lulus siswa (Y): smt 1-2 Y-2,
        3-4 Y-1, 5-6 Y, 7-8 (TOI) Y+1."""
        m = re.match(r"(\d{4})-(\d{4})", siswa_tahun_nama or "")
        if not m:
            return None
        y1, y2 = int(m.group(1)), int(m.group(2))
        if smt <= 2:
            return f"{y1-2}-{y2-2}"
        if smt <= 4:
            return f"{y1-1}-{y2-1}"
        if smt <= 6:
            return f"{y1}-{y2}"
        return f"{y1+1}-{y2+1}"

    def cari_mapel(self, nama, jurusan_id):
        # Kunci dihitung dari nama yang SUDAH dikanonik (perbaikan typo
        # mengubah kunci; tanpa ini mapel yang sama dibuat berulang kali).
        key = kunci(kanonik(nama))
        if (key, jurusan_id) in self.mapel:
            return self.mapel[(key, jurusan_id)]
        return self.mapel_umum.get(key)

    def buat_mapel(self, nama, jurusan_id, tingkat):
        nama = kanonik(nama)
        key = kunci(nama)
        if nama in MAPEL_UMUM_BARU:
            kel, urutan = MAPEL_UMUM_BARU[nama]
            cur = self.con.execute(
                "INSERT INTO mapel (nama, kelompok, jurusan_id, tingkat, urutan, kkm_default) "
                "VALUES (?, ?, NULL, NULL, ?, 76)", (nama, kel, urutan))
            row = {"id": cur.lastrowid, "nama": nama, "kelompok": kel,
                   "jurusan_id": None, "tingkat": None, "urutan": urutan}
            self.mapel[(key, None)] = row
            self.mapel_umum[key] = row
            self.stat["mapel_baru_umum"] += 1
            return row
        kelompok = "C2" if tingkat == "X" else "C3"
        ada = [m["urutan"] for m in self.mapel.values()
               if m["jurusan_id"] == jurusan_id and m["kelompok"] == kelompok]
        urutan = (max(ada) + 1) if ada else (13 if kelompok == "C2" else 16)
        cur = self.con.execute(
            "INSERT INTO mapel (nama, kelompok, jurusan_id, tingkat, urutan, kkm_default) "
            "VALUES (?,?,?,NULL,?,76)", (nama, kelompok, jurusan_id, urutan))
        row = {"id": cur.lastrowid, "nama": nama, "kelompok": kelompok,
               "jurusan_id": jurusan_id, "tingkat": None, "urutan": urutan}
        self.mapel[(key, jurusan_id)] = row
        self.stat["mapel_baru"] += 1
        return row

    def koreksi_jurusan(self, file_path, sheet, kohort):
        """Koreksi siswa TS -> program sebenarnya menurut sheet kelasnya.
        Mengembalikan (dikoreksi, kontaminan)."""
        jurusan_sheet = SHEET_JURUSAN.get(sheet.split(" ")[0].split("(")[0])
        if not jurusan_sheet or jurusan_sheet not in KOREKSI_TS:
            return 0, 0
        target = KOREKSI_TS[jurusan_sheet]
        wb = openpyxl.load_workbook(file_path, data_only=True, read_only=True)
        ws = wb[sheet]
        rows = list(ws.iter_rows(values_only=True))
        if not rows:
            return 0, 0
        hdr = rows[0]
        col_nis = next((j for j, v in enumerate(hdr)
                        if isinstance(v, str) and v.strip().upper() == "NIS"), 1)
        dikoreksi = kontaminan = 0
        id_ts = self.jurusan_id.get("TS")
        id_target = self.jurusan_id.get(target)
        for r in rows[1:]:
            if len(r) <= col_nis or r[col_nis] is None:
                continue
            nis = nis_str(r[col_nis])
            if not nis or nis not in self.siswa:
                continue
            s = self.siswa[nis]
            if s["jurusan_id"] == id_ts:
                self.con.execute("UPDATE siswa SET jurusan_id=? WHERE id=?",
                                 (id_target, s["id"]))
                s["jurusan_id"] = id_target
                dikoreksi += 1
                if len(self.koreksi) < 5000:
                    self.koreksi.append((kohort, nis, "TS", target))
            elif s["jurusan_id"] != id_target:
                kontaminan += 1
        return dikoreksi, kontaminan

    def import_sheet(self, file_path, sheet, kohort):
        wb = openpyxl.load_workbook(file_path, data_only=True, read_only=True)
        if sheet not in wb.sheetnames:
            print(f"  LEWATI {file_path.name} [{sheet}]: sheet tidak ada")
            return
        ws = wb[sheet]
        rows = list(ws.iter_rows(values_only=True))
        if not rows or ws.max_row < 2:
            print(f"  LEWATI {file_path.name} [{sheet}]: kosong")
            return
        hdr = rows[0]
        col_nis = next((j for j, v in enumerate(hdr)
                        if isinstance(v, str) and v.strip().upper() == "NIS"), 1)
        # Petakan kolom mapel: (kolom, nama_mapel, semester)
        mapel_cols = []
        for j, v in enumerate(hdr):
            if j < 11 or not isinstance(v, str) or not v.strip():
                continue
            dasar, smt = pisah_semester(v)
            if smt is None:
                continue
            if normal(dasar) in BUKAN_MAPEL:
                continue
            nama = KODE_MAPEL.get(dasar, dasar)
            mapel_cols.append((j, nama, smt))
        if not mapel_cols:
            print(f"  LEWATI {file_path.name} [{sheet}]: tidak ada kolom mapel")
            return
        for r in rows[1:]:
            if len(r) <= col_nis or r[col_nis] is None:
                continue
            nis = nis_str(r[col_nis])
            if not nis or not re.fullmatch(r"\d{4,6}", nis):
                continue
            s = self.siswa.get(nis)
            if not s:
                self.stat["nis_hilang"] += 1
                if len(self.nis_hilang) < 5000:
                    self.nis_hilang.append((f"{file_path.name}[{sheet}]", nis))
                continue
            sid = s["id"]
            jurusan_id = s["jurusan_id"]
            tahun_nama = self.tahun_nama.get(s["tahun_ajaran_id"])
            # Nilai per semester (untuk ledger): {smt: [nilai]}
            per_smt = defaultdict(list)
            # Kolom ganda untuk (mapel, smt) yang sama: pakai nilai pertama
            # yang terisi; bila keduanya terisi dan berbeda -> konflik.
            sudah = {}
            for j, nama, smt in mapel_cols:
                if j >= len(r):
                    continue
                val = angka_100(r[j])
                if val is None:
                    continue
                key = (nama, smt)
                if key in sudah:
                    if abs(sudah[key] - val) >= 0.01:
                        self.stat["konflik_kolom_ganda"] += 1
                    continue
                sudah[key] = val
                per_smt[smt].append(val)
                m = self.cari_mapel(nama, jurusan_id)
                if not m:
                    m = self.buat_mapel(nama, jurusan_id, self.tingkat(smt))
                tahun = self.tahun_untuk(tahun_nama, smt)
                tid = self.tahun_id.get(tahun)
                if tid is None:
                    self.peringatan.append(f"tahun {tahun} tidak ada di DB ({nis} smt{smt})")
                    continue
                cur = self.con.execute(
                    "INSERT OR IGNORE INTO nilai (siswa_id, mapel_id, tahun_ajaran_id, semester, kkm, nilai_angka) "
                    "VALUES (?,?,?,?,76,?)", (sid, m["id"], tid, smt, val))
                if cur.rowcount == 1:
                    self.stat["nilai"] += 1
                else:
                    lama = self.con.execute(
                        "SELECT nilai_angka FROM nilai WHERE siswa_id=? AND mapel_id=? "
                        "AND tahun_ajaran_id=? AND semester=?",
                        (sid, m["id"], tid, smt)).fetchone()
                    if lama and abs(float(lama[0]) - val) >= 0.01:
                        self.stat["konflik"] += 1
                        if len(self.konflik) < 5000:
                            self.konflik.append(
                                (f"{file_path.name}[{sheet}]", nis, nama, smt,
                                 float(lama[0]), val))
                    else:
                        self.stat["nilai_dup"] += 1
            # Ledger: rata-rata Pengetahuan semester itu (hanya bila belum ada).
            for smt, vals in per_smt.items():
                if not vals:
                    continue
                tahun = self.tahun_untuk(tahun_nama, smt)
                tid = self.tahun_id.get(tahun)
                if tid is None:
                    continue
                rata = round(sum(vals) / len(vals), 2)
                cur = self.con.execute(
                    "INSERT OR IGNORE INTO ledger (siswa_id, tahun_ajaran_id, semester, rata_rata) "
                    "VALUES (?,?,?,?)", (sid, tid, smt, rata))
                if cur.rowcount == 1:
                    self.stat["ledger"] += 1
                else:
                    self.stat["ledger_dup"] += 1

    def pastikan_tahun(self, kohort_list):
        """Tahun ajaran yang dibutuhkan nilai kohort lama belum ada di DB:
        seeder hanya membuat 2013-2014 ke atas, padahal kohort 2013-2014
        butuh 2011-2012 (X) & 2012-2013 (XI), kohort 2014-2015 butuh
        2012-2013. Baris dibuat di sini (is_active=0) supaya nilai smt 1-4
        bisa tersimpan; seeder aplikasi sebaiknya ikut ditambah nanti."""
        butuh = set()
        for kohort in kohort_list:
            m = re.match(r"(\d{4})-(\d{4})", kohort)
            if not m:
                continue
            y1, y2 = int(m.group(1)), int(m.group(2))
            for d in (-2, -1, 0, 1):
                butuh.add(f"{y1+d}-{y2+d}")
        baru = []
        for nama in sorted(butuh):
            if nama not in self.tahun_id:
                cur = self.con.execute(
                    "INSERT INTO tahun_ajaran (nama, is_active, created_at, updated_at) "
                    "VALUES (?, 0, datetime('now'), datetime('now'))", (nama,))
                self.tahun_id[nama] = cur.lastrowid
                baru.append(nama)
        self.tahun_nama = {i: n for n, i in self.tahun_id.items()}
        if baru:
            print(f"  tahun_ajaran baru dibuat: {', '.join(baru)}")
        return baru

    def ringkas(self):
        print("nilai disisip:", self.stat["nilai"], "| duplikat:", self.stat["nilai_dup"],
              "| konflik (nilai lama dipertahankan):", self.stat["konflik"],
              "| konflik kolom ganda:", self.stat["konflik_kolom_ganda"])
        print("ledger disisip:", self.stat["ledger"], "| ledger sudah ada:", self.stat["ledger_dup"])
        print("mapel baru (khusus jurusan):", self.stat["mapel_baru"],
              "| mapel baru umum:", self.stat["mapel_baru_umum"])
        print("NIS tidak ditemukan:", self.stat["nis_hilang"])
        print("nilai di DB:", self.con.execute("SELECT COUNT(*) FROM nilai").fetchone()[0],
              "| siswa bernilai:", self.con.execute("SELECT COUNT(DISTINCT siswa_id) FROM nilai").fetchone()[0],
              "| ledger:", self.con.execute("SELECT COUNT(*) FROM ledger").fetchone()[0],
              "| mapel:", self.con.execute("SELECT COUNT(*) FROM mapel").fetchone()[0])


def main():
    ap = argparse.ArgumentParser(description="Import nilai Transkrip/Merger 2013-2020")
    ap.add_argument("--db", default=None)
    ap.add_argument("--kohort", default=None, help="hanya kohort ini, mis. 2016-2017")
    ap.add_argument("--dry-run", action="store_true")
    ap.add_argument("--tanpa-koreksi", action="store_true",
                    help="jangan koreksi jurusan TS")
    args = ap.parse_args()

    db_path = args.db or str(DB)
    imp = Importer(db_path)
    kohort_list = [k for _, _, k in MANIFEST if not args.kohort or k == args.kohort]
    imp.pastikan_tahun(kohort_list)
    for rel, sheets, kohort in MANIFEST:
        if args.kohort and kohort != args.kohort:
            continue
        path = SRC / rel
        if not path.exists():
            print(f"File tidak ada: {path}")
            continue
        print(f"== {rel} (kohort {kohort}) ==")
        if not args.tanpa_koreksi:
            for sh in sheets:
                d, k = imp.koreksi_jurusan(path, sh, kohort)
                if d or k:
                    print(f"  koreksi jurusan [{sh}]: {d} siswa TS dikoreksi, {k} kontaminan")
        for sh in sheets:
            print(f"  [{sh}]")
            imp.import_sheet(path, sh, kohort)
    print()
    print("=== RINGKASAN ===")
    imp.ringkas()
    print("koreksi jurusan total:", len(imp.koreksi))
    by_k = Counter((k[0], k[2], k[3]) for k in imp.koreksi)
    for (koh, asal, tujuan), n in sorted(by_k.items()):
        print(f"  {koh}: {asal} -> {tujuan}: {n} siswa")
    if imp.nis_hilang:
        by_f = Counter(n[0] for n in imp.nis_hilang)
        print("NIS hilang per file/sheet:")
        for f, n in sorted(by_f.items()):
            print(f"  {f}: {n}")
    if imp.konflik:
        by_f = Counter(k[0] for k in imp.konflik)
        print("konflik per file/sheet (nilai lama dipertahankan):")
        for f, n in sorted(by_f.items(), key=lambda x: -x[1])[:20]:
            print(f"  {f}: {n}")
        print("  contoh konflik:", imp.konflik[:5])
    if args.dry_run:
        imp.con.rollback()
        print("(dry-run: perubahan dibuang)")
    else:
        imp.con.commit()
        print("(disimpan ke DB)")


if __name__ == "__main__":
    main()
