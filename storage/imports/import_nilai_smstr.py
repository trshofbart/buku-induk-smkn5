#!/usr/bin/env python3
"""Import nilai per mata pelajaran dari file MASTER ke database-imported.sqlite.

Sumber:
  - 2021-2022/MASTER-TEI.xlsm & MASTER [RPL A].xlsm, sheet SMSTR 1-6.
    Nilai angka = kolom pertama tiap blok mapel (nilai Pengetahuan,
    sesuai kolom NILAI di HAL_PRINT). Semester 1-2 = 2019/2020,
    3-4 = 2020/2021, 5-6 = 2021/2022.
  - 2020-2021/MASTER.xlsx, sheet LEGGER (kohort 2018, 41 siswa).
    Kolom per mapel-semester: A = angka, H = huruf (kosong di file).
    Semester 1-2 = 2018/2019, 3-4 = 2019/2020, 5-6 = 2020/2021.

KKM mengikuti HAL_PRINT (76). Huruf tidak disimpan — dihitung aplikasi
dengan skala sekolah (A>=92, B 84-91, C 76-83, D<76).
Idempotent: kunci unik (siswa, mapel, tahun, semester) mencegah duplikat.
"""
import sqlite3
import sys
from pathlib import Path

import openpyxl

BASE = Path(__file__).resolve().parent.parent.parent  # ~/workspace/buku-induk-smkn5
SRC = BASE / "storage" / "imports" / "source"
DB = BASE / "database" / "database-imported.sqlite"

# Daftar mapel — sama persis dengan database/seeders/MapelSeeder.php
MAPEL = [
    ("Pendidikan Agama Islam dan Budi Pekerti", "A", 1, None),
    ("Pendidikan Pancasila dan Kewarganegaraan", "A", 2, None),
    ("Bahasa Indonesia", "A", 3, None),
    ("Matematika (Umum)", "A", 4, None),
    ("Sejarah Indonesia", "A", 5, None),
    ("Bahasa Inggris", "A", 6, None),
    ("Seni Budaya", "B", 7, None),
    ("Pendidikan Jasmani, Olahraga dan Kesehatan", "B", 8, None),
    ("Bahasa Jawa", "B", 9, None),
    ("Simulasi dan Komunikasi Digital", "C1", 10, None),
    ("Fisika", "C1", 11, None),
    ("Kimia", "C1", 12, None),
    ("Kerja Bengkel dan Gambar Teknik", "C2", 13, None),
    ("Dasar Listrik dan Elektronika", "C2", 14, None),
    ("T. Pemograman. M. Prosesor dan M. Kontroler", "C2", 15, None),
    ("Produk Kreatif dan Kewirausahaan", "C3", 16, None),
    ("Penerapan Rangkaian Elektronika", "C3", 17, None),
    ("Sistem Pengendali Elektronik", "C3", 18, None),
    ("Mikroprosesor dan Mikrokontroler", "C3", 19, "X"),
    ("Mikroprosesor dan Mikrokontroler", "C3", 19, "XI"),
    ("Pengendali Sistem Robotik", "C3", 20, None),
    ("Pembuatan, Perbaik dan Pemel Peralatan Elektronika", "C3", 21, "XII"),
]

KODE_LEGGER = {
    "PABP": "Pendidikan Agama Islam dan Budi Pekerti",
    "PKn": "Pendidikan Pancasila dan Kewarganegaraan",
    "BI": "Bahasa Indonesia",
    "Mtk": "Matematika (Umum)",
    "Sjrh": "Sejarah Indonesia",
    "Bing": "Bahasa Inggris",
    "SB": "Seni Budaya",
    "PJOK": "Pendidikan Jasmani, Olahraga dan Kesehatan",
    "Bjawa": "Bahasa Jawa",
    "SimDig": "Simulasi dan Komunikasi Digital",
}

SMSTR_TAHUN = {1: "2019-2020", 2: "2019-2020", 3: "2020-2021",
               4: "2020-2021", 5: "2021-2022", 6: "2021-2022"}
LEGGER_TAHUN = {1: "2018-2019", 2: "2018-2019", 3: "2019-2020",
                4: "2019-2020", 5: "2020-2021", 6: "2020-2021"}


def tingkat_dari_semester(s):
    return "X" if s <= 2 else ("XI" if s <= 4 else "XII")


def siapkan_db(con):
    con.execute("""CREATE TABLE IF NOT EXISTS mapel (
        id INTEGER PRIMARY KEY AUTOINCREMENT, kode VARCHAR(20), nama VARCHAR(150) NOT NULL,
        kelompok VARCHAR(10) NOT NULL, jurusan_id INTEGER, tingkat VARCHAR(10),
        urutan INTEGER NOT NULL DEFAULT 0, kkm_default DECIMAL(5,2) NOT NULL DEFAULT 76,
        created_at DATETIME, updated_at DATETIME)""")
    con.execute("""CREATE TABLE IF NOT EXISTS nilai (
        id INTEGER PRIMARY KEY AUTOINCREMENT, siswa_id INTEGER NOT NULL, mapel_id INTEGER NOT NULL,
        tahun_ajaran_id INTEGER NOT NULL, semester INTEGER NOT NULL,
        kkm DECIMAL(5,2), nilai_angka DECIMAL(5,2), created_at DATETIME, updated_at DATETIME,
        UNIQUE(siswa_id, mapel_id, tahun_ajaran_id, semester))""")
    con.execute("CREATE INDEX IF NOT EXISTS nilai_mapel_idx ON nilai(mapel_id, tahun_ajaran_id, semester)")
    # Seed mapel (idempotent)
    for nama, kel, urutan, tingkat in MAPEL:
        row = con.execute(
            "SELECT id FROM mapel WHERE nama=? AND (tingkat IS ? OR tingkat=?)",
            (nama, tingkat, tingkat)).fetchone()
        if not row:
            con.execute(
                "INSERT INTO mapel (nama, kelompok, jurusan_id, tingkat, urutan, kkm_default) "
                "VALUES (?,?,NULL,?,?,76)", (nama, kel, tingkat, urutan))
    con.commit()


def peta_mapel(con):
    """(nama_normal, tingkat) -> id ; plus fallback (nama_normal, None)."""
    m = {}
    for mid, nama, tingkat in con.execute("SELECT id, nama, tingkat FROM mapel"):
        m[(nama.strip().lower(), tingkat)] = mid
    return m


def cari_mapel(peta, nama, tingkat):
    key = nama.strip().lower()
    return peta.get((key, tingkat)) or peta.get((key, None))


def import_smstr(con, peta, tahun_id, path):
    """Sheet SMSTR 1-6 satu file MASTER. Nilai = kolom pertama tiap blok
    mapel (nilai Pengetahuan): SMSTR 1 blok 5 kolom, SMSTR 2 blok 4 kolom,
    SMSTR 3-6 blok 3 kolom (P/K/R). Kembalikan (disisip, duplikat, nis_hilang, mapel_hilang)."""
    wb = openpyxl.load_workbook(path, data_only=True, read_only=True)
    ins = dup = nis_hilang = mapel_hilang = 0
    for smt in range(1, 7):
        nama_sheet = f"SMSTR {smt}"
        if nama_sheet not in wb.sheetnames:
            continue
        ws = wb[nama_sheet]
        rows = list(ws.iter_rows(values_only=True))
        header_mapel = rows[3]  # baris 4: nama mapel di kolom pertama tiap blok
        # Tiap nama mapel di baris 4 -> kolom nilai = kolom nama itu sendiri
        nilai_cols = {ci: str(v).strip() for ci, v in enumerate(header_mapel)
                      if ci >= 4 and v}
        tid = tahun_id[SMSTR_TAHUN[smt]]
        tingkat = tingkat_dari_semester(smt)
        for r in rows[7:]:
            if not r or r[1] is None:
                continue
            nis = str(r[1]).strip()
            srow = con.execute("SELECT id FROM siswa WHERE nis=?", (nis,)).fetchone()
            if not srow:
                nis_hilang += 1
                continue
            sid = srow[0]
            for ci, nama_mapel in nilai_cols.items():
                val = r[ci]
                if not isinstance(val, (int, float)):
                    continue
                mid = cari_mapel(peta, nama_mapel, tingkat)
                if not mid:
                    mapel_hilang += 1
                    continue
                cur = con.execute(
                    "INSERT OR IGNORE INTO nilai (siswa_id, mapel_id, tahun_ajaran_id, semester, kkm, nilai_angka) "
                    "VALUES (?,?,?,?,76,?)", (sid, mid, tid, smt, float(val)))
                if cur.rowcount == 1:
                    ins += 1
                else:
                    dup += 1
    return ins, dup, nis_hilang, mapel_hilang


def import_legger(con, peta, tahun_id, path):
    """Sheet LEGGER 2020-2021/MASTER.xlsx. Kembalikan (disisip, duplikat, nis_hilang, mapel_hilang)."""
    wb = openpyxl.load_workbook(path, data_only=True, read_only=True)
    ws = wb["LEGGER"]
    rows = list(ws.iter_rows(values_only=True))
    header = rows[0]
    # Tiap pasangan mapel-semester: kolom A di indeks kode, H di +1
    pasangan = []
    for ci, v in enumerate(header):
        if isinstance(v, str) and "_" in v:
            kode, suf = v.rsplit("_", 1)
            if kode in KODE_LEGGER and suf.isdigit():
                pasangan.append((ci, KODE_LEGGER[kode], int(suf)))
    ins = dup = nis_hilang = mapel_hilang = 0
    for r in rows[3:]:
        if not r or r[1] is None:
            continue
        nis = str(r[1]).strip()
        srow = con.execute("SELECT id FROM siswa WHERE nis=?", (nis,)).fetchone()
        if not srow:
            nis_hilang += 1
            continue
        sid = srow[0]
        for ci, nama_mapel, smt in pasangan:
            val = r[ci]
            if not isinstance(val, (int, float)):
                continue
            mid = cari_mapel(peta, nama_mapel, tingkat_dari_semester(smt))
            if not mid:
                mapel_hilang += 1
                continue
            cur = con.execute(
                "INSERT OR IGNORE INTO nilai (siswa_id, mapel_id, tahun_ajaran_id, semester, kkm, nilai_angka) "
                "VALUES (?,?,?,?,76,?)", (sid, mid, tahun_id[LEGGER_TAHUN[smt]], smt, float(val)))
            if cur.rowcount == 1:
                ins += 1
            else:
                dup += 1
    return ins, dup, nis_hilang, mapel_hilang


def main():
    con = sqlite3.connect(DB)
    siapkan_db(con)
    # Tabel nilai hanya berisi hasil import ini -> bersihkan dulu agar
    # perbaikan parser tidak meninggalkan nilai lama yang salah.
    con.execute("DELETE FROM nilai")
    con.commit()
    peta = peta_mapel(con)
    tahun_id = {nama: i for i, nama in con.execute("SELECT id, nama FROM tahun_ajaran")}
    total = [0, 0, 0, 0]
    for f in ["2021-2022/MASTER-TEI.xlsm", "2021-2022/MASTER [RPL A].xlsm"]:
        r = import_smstr(con, peta, tahun_id, SRC / f)
        print(f"{f}: disisip={r[0]} duplikat={r[1]} nis_hilang={r[2]} mapel_hilang={r[3]}")
        total = [a + b for a, b in zip(total, r)]
    r = import_legger(con, peta, tahun_id, SRC / "2020-2021/MASTER.xlsx")
    print(f"2020-2021 LEGGER: disisip={r[0]} duplikat={r[1]} nis_hilang={r[2]} mapel_hilang={r[3]}")
    total = [a + b for a, b in zip(total, r)]
    con.commit()
    print(f"TOTAL: disisip={total[0]} duplikat={total[1]} nis_hilang={total[2]} mapel_hilang={total[3]}")
    print("nilai di DB:", con.execute("SELECT COUNT(*) FROM nilai").fetchone()[0],
          "| siswa bernilai:", con.execute("SELECT COUNT(DISTINCT siswa_id) FROM nilai").fetchone()[0])
    # Spot-check lawan HAL_PRINT (nilai = Pengetahuan): ARDIANSYAH (20016)
    # PAI smt1=81, smt2=80, smt3=77, smt5=83; ADE (20013) PAI smt1=94.
    for nis, smt, nama_mapel, harap in [
            ("20016", 1, "Pendidikan Agama Islam dan Budi Pekerti", 81),
            ("20016", 2, "Pendidikan Agama Islam dan Budi Pekerti", 80),
            ("20016", 3, "Pendidikan Agama Islam dan Budi Pekerti", 77),
            ("20016", 5, "Pendidikan Agama Islam dan Budi Pekerti", 83),
            ("20013", 1, "Pendidikan Agama Islam dan Budi Pekerti", 94)]:
        got = con.execute(
            "SELECT n.nilai_angka FROM nilai n JOIN siswa s ON s.id=n.siswa_id "
            "JOIN mapel m ON m.id=n.mapel_id WHERE s.nis=? AND n.semester=? AND m.nama=?",
            (nis, smt, nama_mapel)).fetchone()
        print(f"cek NIS {nis} smt{smt} {nama_mapel[:20]}: {got[0] if got else None} (harap {harap})",
              "OK" if got and float(got[0]) == harap else "SALAH")


if __name__ == "__main__":
    main()
