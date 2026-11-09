#!/usr/bin/env python3
"""Import nilai per mapel dari file Leger per kelas (Drive) ke database-imported.sqlite.

Sumber: folder LEDGER tahun ajaran (mis. 2021-2022/LEDGER/SMT1..SMT8),
satu file per rombel per semester — inilah data lengkap per kelas yang
dibuat sekolah (file MASTER hanya template). Dua layout dikenali:

  - K2013 (SMT1-2): baris 2 'TAHUN PELAJARAN : 2019 / 2020, SEMESTER : 1',
    baris 4 nama mapel; nilai Pengetahuan = kolom pertama tiap blok
    (Peng angka). Dua varian lebar blok: 5 kolom (SMT1: N,P,N,P,R —
    R = rata-rata mapel) dan 4 kolom (SMT2: N,P,N,P — tanpa kolom R,
    rata-rata dihitung (Peng+Ket)/2). Lebar blok dideteksi per file
    dari jarak dua nama mapel pertama. Kolom NIS (angka) + S/I/A +
    Ekskul di ujung.
  - Baru (SMT3-8): baris 0 'No | NAMA PESERTA DIDIK | NISN | mapel...'
    (blok 3 kolom: Nilai P, Nilai K, R), nilai Pengetahuan = 'Nilai P';
    tanpa kolom NIS (cocokkan lewat NISN, buang apostrof di depan).

Aturan:
  - Tahun ajaran nilai: dari baris TAHUN PELAJARAN bila ada, jika tidak
    dari semester (folder kohort lulus G: smt 1-2 -> G-2, 3-4 -> G-1,
    5-6 -> G, 7-8 -> G). Ledger rata-rata = rata-rata kolom R per mapel
    (aturan yang sama dengan import lama).
  - Mapel yang belum ada di DB dibuat sebagai mapel khusus jurusan
    (C2 bila pertama muncul di tingkat X, C3 bila pertama muncul di
    XI/XII) supaya tampil di cetakan lewat Mapel per Jurusan; mapel
    umum (A/B/C1) dipakai apa adanya. Kelompok C2/C3 khusus jurusan
    MENGGANTIKAN yang umum di cetakan, jadi daftarnya harus lengkap.
  - Idempotent: INSERT OR IGNORE (kunci unik nilai & ledger). Bila file
    Leger memberi nilai BERBEDA untuk siswa+mapel+semester yang sudah
    ada (mis. dari import MASTER), nilai Leger MENIMPA karena file
    Leger per kelas adalah sumber resmi sedangkan MASTER hanya
    template; penimpaan dicatat di laporan.
  - Kelengkapan (S/I/A) + ekstrakurikuler ikut diimport bila ada.
"""
import argparse
import re
import sqlite3
import sys
from pathlib import Path

import openpyxl

BASE = Path(__file__).resolve().parent.parent.parent  # ~/workspace/buku-induk-smkn5
SRC = BASE / "storage" / "imports" / "source"
DB = BASE / "database" / "database-imported.sqlite"

SIKAP_EKSKUL = {"A": "Amat Baik", "B": "Baik", "C": "Cukup", "D": "Kurang"}

# Nama mapel di file Leger tidak selalu sama persis dengan nama di DB
# (singkatan, tanda baca, terpotong). Kunci di sini adalah bentuk
# yang sudah dinormalisasi (lihat normal()): kecil, tanpa tanda baca.
ALIAS_MAPEL = {
    "apl perangkat lunak dan perancangan int gedung": "Aplikasi Perangkat Lunak dan Perancangan Interior Gedung",
    "aplikasi perangkat lunak dan perancangan interior": "Aplikasi Perangkat Lunak dan Perancangan Interior Gedung",
    "pembuatan perbaikan dan pemeliharaanperalatan el": "Pembuatan, Perbaik dan Pemel Peralatan Elektronika",
    "gambar teknik listrik": "Gambar Teknik Listrik",
}

# Mapel agama selain Islam adalah kelompok A (Muatan Nasional) dan
# berlaku untuk semua jurusan — bukan C2/C3 khusus jurusan.
AGAMA_NON_ISLAM = re.compile(r"^pendidikan agama (kristen|katholik|katolik|buddha|hindu|konghucu) dan budi pekerti$")


def normal(s):
    """Kunci pencocokan nama mapel: kecil, '&' -> 'dan', buang tanda baca."""
    if s is None:
        return ""
    s = str(s).strip().lower().replace("&", " dan ")
    s = re.sub(r"[.,'\"`]", " ", s)
    return re.sub(r"\s+", " ", s).strip()


def kanonik(nama):
    """Nama mapel yang disimpan di DB (alias diselesaikan, '&' -> 'dan')."""
    key = normal(nama)
    if key in ALIAS_MAPEL:
        return ALIAS_MAPEL[key]
    return re.sub(r"\s+", " ", str(nama).strip().replace("&", "dan"))


def angka(v):
    """Nilai angka dari sel (int/float/string angka, buang spasi & apostrof)."""
    if isinstance(v, bool):
        return None
    if isinstance(v, (int, float)):
        return float(v) if 0 <= v <= 100 else None
    if isinstance(v, str):
        v = v.strip().lstrip("'")
        try:
            f = float(v)
            return f if 0 <= f <= 100 else None
        except ValueError:
            return None
    return None


def nis_str(v):
    """NIS dari sel (bisa float 19484.0)."""
    if isinstance(v, bool):
        return None
    if isinstance(v, (int, float)):
        return str(int(v))
    if isinstance(v, str):
        v = v.strip().lstrip("'")
        return v or None
    return None


def tahun_dari_teks(teks):
    m = re.search(r"(\d{4})\s*/\s*(\d{4})", str(teks or ""))
    return f"{m.group(1)}-{m.group(2)}" if m else None


class Importer:
    def __init__(self, db_path, grad_tahun):
        self.con = sqlite3.connect(db_path)
        self.con.row_factory = sqlite3.Row
        self.grad = grad_tahun  # '2021-2022'
        g = int(grad_tahun.split("-")[1])
        self.smt_tahun = {1: f"{g-3}-{g-2}", 2: f"{g-3}-{g-2}",
                          3: f"{g-2}-{g-1}", 4: f"{g-2}-{g-1}",
                          5: f"{g-1}-{g}", 6: f"{g-1}-{g}",
                          7: f"{g-1}-{g}", 8: f"{g-1}-{g}"}
        self.tahun_id = {r["nama"]: r["id"] for r in self.con.execute("SELECT id, nama FROM tahun_ajaran")}
        self.siswa_nis = {}
        self.siswa_nisn = {}
        for r in self.con.execute("SELECT id, nis, nisn, jurusan_id FROM siswa"):
            if r["nis"]:
                self.siswa_nis[str(r["nis"]).strip()] = (r["id"], r["jurusan_id"])
            if r["nisn"]:
                self.siswa_nisn[str(r["nisn"]).strip().lstrip("'")] = (r["id"], r["jurusan_id"])
        self.mapel = {}  # (nama_normal, jurusan_id) -> row
        self.mapel_umum = {}  # nama_normal -> row (jurusan NULL)
        for r in self.con.execute("SELECT id, nama, kelompok, jurusan_id, tingkat, urutan FROM mapel"):
            self.mapel[(normal(r["nama"]), r["jurusan_id"])] = dict(r)
            if r["jurusan_id"] is None:
                self.mapel_umum.setdefault(normal(r["nama"]), dict(r))
        self.stat = {"nilai": 0, "nilai_dup": 0, "nilai_timpa": 0,
                     "ledger": 0, "ledger_timpa": 0, "mapel_baru": 0,
                     "kelengkapan": 0, "ekskul": 0, "nis_hilang": 0}
        self.nis_hilang_contoh = []
        self.konflik_contoh = []

    def tingkat(self, smt):
        return "X" if smt <= 2 else ("XI" if smt <= 4 else ("XII" if smt <= 6 else "XIII"))

    def cari_mapel(self, nama, jurusan_id, tingkat):
        # Selesaikan alias dulu supaya varian penulisan bertemu di satu baris.
        key = normal(kanonik(nama))
        # Utamakan mapel khusus jurusan ini, lalu yang umum.
        if (key, jurusan_id) in self.mapel:
            return self.mapel[(key, jurusan_id)]
        return self.mapel_umum.get(key)

    def buat_mapel(self, nama, jurusan_id, tingkat):
        nama = kanonik(nama)
        key = normal(nama)
        # Mapel agama non-Islam: kelompok A, berlaku semua jurusan.
        if AGAMA_NON_ISLAM.match(key):
            cur = self.con.execute(
                "INSERT INTO mapel (nama, kelompok, jurusan_id, tingkat, urutan, kkm_default) "
                "VALUES (?, 'A', NULL, NULL, 1, 76)", (nama,))
            row = {"id": cur.lastrowid, "nama": nama, "kelompok": "A",
                   "jurusan_id": None, "tingkat": None, "urutan": 1}
            self.mapel[(key, None)] = row
            self.mapel_umum[key] = row
            self.stat["mapel_baru"] += 1
            return row
        kelompok = "C2" if tingkat == "X" else "C3"
        urutan = 13 if kelompok == "C2" else 16
        # Urutan berikutnya yang kosong di kelompok itu (per jurusan).
        ada = [m["urutan"] for m in self.mapel.values()
               if m["jurusan_id"] == jurusan_id and m["kelompok"] == kelompok]
        if ada:
            urutan = max(ada) + 1
        cur = self.con.execute(
            "INSERT INTO mapel (nama, kelompok, jurusan_id, tingkat, urutan, kkm_default) "
            "VALUES (?,?,?,NULL,?,76)", (nama, kelompok, jurusan_id, urutan))
        row = {"id": cur.lastrowid, "nama": nama, "kelompok": kelompok,
               "jurusan_id": jurusan_id, "tingkat": None, "urutan": urutan}
        self.mapel[(key, jurusan_id)] = row
        self.stat["mapel_baru"] += 1
        return row

    def cari_siswa(self, nis=None, nisn=None):
        if nis and nis in self.siswa_nis:
            return self.siswa_nis[nis]
        if nisn and nisn in self.siswa_nisn:
            return self.siswa_nisn[nisn]
        return None

    def import_file(self, path, smt):
        wb = openpyxl.load_workbook(path, data_only=True, read_only=True)
        ws = wb[wb.sheetnames[0]]
        rows = list(ws.iter_rows(values_only=True))
        # --- kenali layout ---
        header = None
        layout = None
        for i, r in enumerate(rows[:8]):
            if not r:
                continue
            teks = [normal(v) for v in r if v]
            if "nis" in teks and "nama" in teks:
                header, layout = i, "k2013"
                break
            if "nama peserta didik" in teks and "nisn" in teks:
                header, layout = i, "baru"
                break
        if layout is None:
            print(f"  LEWATI {path.name}: header NIS/NISN tidak ditemukan")
            return
        tingkat = self.tingkat(smt)
        # Tahun ajaran: baris TAHUN PELAJARAN (K2013) atau peta semester.
        tahun = None
        if layout == "k2013":
            for r in rows[:4]:
                for v in r or []:
                    if isinstance(v, str) and "TAHUN PELAJARAN" in v.upper():
                        tahun = tahun_dari_teks(v)
        if not tahun:
            tahun = self.smt_tahun[smt]
        tid = self.tahun_id.get(tahun)
        if tid is None:
            print(f"  LEWATI {path.name}: tahun ajaran {tahun} tidak ada di DB")
            return
        # --- petakan kolom mapel ---
        if layout == "k2013":
            baris_mapel = rows[header + 1]
            awal_data = header + 4
            col_nis, col_nama = 1, 2
            # Kolom Rata-Rata / S-I-A / Ekskul: dari baris header+3 (N/P/R).
            sub = rows[header + 3]
            col_ratarata = next((ci for ci, v in enumerate(baris_mapel)
                                 if isinstance(v, str) and normal(v) == "rata-rata"), None)
            col_s = next((ci for ci, v in enumerate(sub) if v == "S"), None)
            ekskul_cols = []
            for ci, v in enumerate(sub):
                if v == "Ekskul":
                    ekskul_cols.append((ci, ci + 1))
            # Lebar blok per mapel: jarak dua nama mapel pertama
            # (5 = ada kolom R, 4 = tanpa kolom R); fallback dari Rata-Rata.
            nama_pos = [ci for ci, v in enumerate(baris_mapel)
                        if ci >= 3 and (col_ratarata is None or ci < col_ratarata)
                        and isinstance(v, str) and v.strip()]
            if len(nama_pos) >= 2:
                blok = nama_pos[1] - nama_pos[0]
            elif col_ratarata is not None and nama_pos:
                blok = (col_ratarata - 3) // len(nama_pos)
            else:
                blok = 5
            if blok not in (4, 5):
                print(f"  PERINGATAN {path.name}: lebar blok {blok}, pakai 5")
                blok = 5
        else:
            blok = 3
            baris_mapel = rows[header]
            awal_data = header + 3
            col_nis, col_nama = None, 1
            col_ratarata = col_s = None
            ekskul_cols = []
        mapel_cols = []  # (col_nilai, col_R, nama_mapel)
        ci = 3
        while ci < len(baris_mapel):
            nama = baris_mapel[ci]
            if col_ratarata is not None and ci >= col_ratarata:
                break
            if isinstance(nama, str) and nama.strip():
                mapel_cols.append((ci, ci + blok - 1, nama.strip()))
            ci += blok
        # --- baris data ---
        def sel(r, i):
            return r[i] if i < len(r) else None

        for r in rows[awal_data:]:
            if not r:
                continue
            nis = nis_str(sel(r, col_nis)) if col_nis is not None else None
            nisn = nis_str(sel(r, 2)) if layout == "baru" else None
            if layout == "k2013" and (not nis or not sel(r, col_nama)):
                continue
            if layout == "baru" and (not nisn or not sel(r, col_nama)):
                continue
            got = self.cari_siswa(nis, nisn)
            if not got:
                self.stat["nis_hilang"] += 1
                if len(self.nis_hilang_contoh) < 10000:
                    self.nis_hilang_contoh.append((path.name, nis or nisn, sel(r, col_nama)))
                continue
            sid, jurusan_id = got
            r_vals = []
            for col_nilai, col_r, nama_mapel in mapel_cols:
                val = angka(sel(r, col_nilai))
                if layout == "baru":
                    rval = angka(sel(r, col_r))  # kolom R (sudah dibulatkan)
                else:
                    # K2013: R = (Peng+Ket)/2; kolom R file (bila ada) sama.
                    ket = angka(sel(r, col_nilai + 2))
                    rval = (val + ket) / 2 if (val is not None and ket is not None) else val
                if rval is not None:
                    r_vals.append(rval)
                if val is None:
                    continue
                m = self.cari_mapel(nama_mapel, jurusan_id, tingkat)
                if not m:
                    m = self.buat_mapel(nama_mapel, jurusan_id, tingkat)
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
                    if lama and float(lama[0]) != val:
                        # File Leger per kelas adalah sumber resmi (file MASTER
                        # hanya template) — nilai Leger MENIMPA nilai lama.
                        self.con.execute(
                            "UPDATE nilai SET nilai_angka=? WHERE siswa_id=? AND mapel_id=? "
                            "AND tahun_ajaran_id=? AND semester=?",
                            (val, sid, m["id"], tid, smt))
                        self.stat["nilai_timpa"] += 1
                        if len(self.konflik_contoh) < 10000:
                            self.konflik_contoh.append((path.name, nis or nisn, nama_mapel, lama[0], val))
                    else:
                        self.stat["nilai_dup"] += 1
            # Ledger: rata-rata kolom R.
            if r_vals:
                rata = round(sum(r_vals) / len(r_vals), 2)
                cur = self.con.execute(
                    "INSERT OR IGNORE INTO ledger (siswa_id, tahun_ajaran_id, semester, rata_rata) "
                    "VALUES (?,?,?,?)", (sid, tid, smt, rata))
                if cur.rowcount == 1:
                    self.stat["ledger"] += 1
                else:
                    lama = self.con.execute(
                        "SELECT rata_rata FROM ledger WHERE siswa_id=? AND tahun_ajaran_id=? AND semester=?",
                        (sid, tid, smt)).fetchone()
                    if lama and float(lama[0]) != rata:
                        self.con.execute(
                            "UPDATE ledger SET rata_rata=? WHERE siswa_id=? AND tahun_ajaran_id=? AND semester=?",
                            (rata, sid, tid, smt))
                        self.stat["ledger_timpa"] += 1
            # Kelengkapan S/I/A + ekskul (hanya layout K2013).
            if layout == "k2013" and col_s is not None:
                s, i_, a = angka(sel(r, col_s)), angka(sel(r, col_s + 1)), angka(sel(r, col_s + 2))
                if s is not None or i_ is not None or a is not None:
                    cur = self.con.execute(
                        "INSERT OR IGNORE INTO kelengkapan (siswa_id, tahun_ajaran_id, semester, sakit, izin, alpa) "
                        "VALUES (?,?,?,?,?,?)",
                        (sid, tid, smt, int(s or 0), int(i_ or 0), int(a or 0)))
                    if cur.rowcount == 1:
                        self.stat["kelengkapan"] += 1
                for col_keg, col_nil in ekskul_cols:
                    keg = sel(r, col_keg)
                    nil = sel(r, col_nil)
                    if isinstance(keg, str) and keg.strip() and keg.strip() != "-":
                        nilai_eks = SIKAP_EKSKUL.get(str(nil).strip().upper(), str(nil).strip() if nil else None)
                        ada = self.con.execute(
                            "SELECT id FROM ekstrakurikuler WHERE siswa_id=? AND tahun_ajaran_id=? "
                            "AND semester=? AND kegiatan=?", (sid, tid, smt, keg.strip())).fetchone()
                        if not ada:
                            self.con.execute(
                                "INSERT INTO ekstrakurikuler (siswa_id, tahun_ajaran_id, semester, kegiatan, nilai) "
                                "VALUES (?,?,?,?,?)", (sid, tid, smt, keg.strip(), nilai_eks))
                            self.stat["ekskul"] += 1

    def ringkas(self):
        print("nilai disisip:", self.stat["nilai"], "| duplikat:", self.stat["nilai_dup"],
              "| ditimpa (Leger menang atas MASTER):", self.stat["nilai_timpa"])
        print("ledger disisip:", self.stat["ledger"], "| ditimpa:", self.stat["ledger_timpa"],
              "| mapel baru:", self.stat["mapel_baru"])
        print("kelengkapan:", self.stat["kelengkapan"], "| ekskul:", self.stat["ekskul"],
              "| NIS/NISN tidak ditemukan:", self.stat["nis_hilang"])
        for c in self.nis_hilang_contoh:
            print("  NIS hilang:", c)
        for c in self.konflik_contoh:
            print("  KONFLIK:", c)
        print("nilai di DB:", self.con.execute("SELECT COUNT(*) FROM nilai").fetchone()[0],
              "| siswa bernilai:", self.con.execute("SELECT COUNT(DISTINCT siswa_id) FROM nilai").fetchone()[0],
              "| ledger:", self.con.execute("SELECT COUNT(*) FROM ledger").fetchone()[0],
              "| kelengkapan:", self.con.execute("SELECT COUNT(*) FROM kelengkapan").fetchone()[0],
              "| ekskul:", self.con.execute("SELECT COUNT(*) FROM ekstrakurikuler").fetchone()[0])

    def selesai(self):
        self.con.commit()
        self.ringkas()


def main():
    ap = argparse.ArgumentParser(description="Import nilai per mapel dari file Leger per kelas")
    ap.add_argument("--folder", default="2021-2022", help="folder tahun di storage/imports/source (kohort lulus)")
    ap.add_argument("--smt", type=int, choices=range(1, 9), help="hanya semester ini")
    ap.add_argument("--db", default=None)
    ap.add_argument("--dry-run", action="store_true")
    args = ap.parse_args()

    folder = SRC / args.folder / "LEDGER"
    if not folder.exists():
        print(f"Folder tidak ada: {folder}")
        sys.exit(1)
    db_path = args.db or str(DB)
    imp = Importer(db_path, args.folder)
    smts = [args.smt] if args.smt else range(1, 9)
    for smt in smts:
        d = folder / f"SMT{smt}"
        if not d.exists():
            continue
        for f in sorted(d.glob("*.xlsx")):
            if f.name.startswith("~$"):
                continue
            print(f"SMT{smt} {f.name}")
            imp.import_file(f, smt)
    if args.dry_run:
        imp.ringkas()
        imp.con.rollback()
        print("(dry-run: perubahan dibuang)")
    else:
        imp.selesai()


if __name__ == "__main__":
    main()
