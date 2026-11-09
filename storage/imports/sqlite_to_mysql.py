#!/usr/bin/env python3
"""Convert database-imported.sqlite (data asli hasil import) menjadi dump MySQL.

Pakai: python3 sqlite_to_mysql.py
Output: ~/workspace/your_files/buku_induk_smkn5_data.sql
- CREATE TABLE diambil dari schema.sql (sudah sintaks MySQL).
- INSERT diambil dari SQLite, tabel data saja (tanpa cache/sessions/jobs).
- Tabel `migrations` IKUT disertakan (dengan semua barisnya) supaya
  setelah import, `php artisan migrate` langsung tahu semua migrasi
  sudah jalan — dulu dump tanpa tabel ini membuat migrate mencoba
  membuat ulang tabel `users` dan gagal.
- Tidak mencetak data pribadi ke stdout — hanya hitungan per tabel.
"""
import re
import sqlite3
import sys
from pathlib import Path

BASE = Path.home() / "workspace" / "buku-induk-smkn5"
SQLITE_DB = BASE / "database" / "database-imported.sqlite"
SCHEMA_SQL = BASE / "schema.sql"
OUT = Path.home() / "workspace" / "your_files" / "buku_induk_smkn5_data.sql"

SKIP_TABLES = {
    "cache", "cache_locks", "sessions",
    "jobs", "job_batches", "failed_jobs", "password_reset_tokens",
    "sqlite_sequence",
}

def mysql_escape(value):
    if value is None:
        return "NULL"
    if isinstance(value, int):
        return str(value)
    if isinstance(value, float):
        return repr(value)
    if isinstance(value, bytes):
        return "0x" + value.hex()
    s = str(value)
    s = s.replace("\\", "\\\\").replace("'", "\\'").replace("\n", "\\n").replace("\r", "\\r").replace("\0", "")
    return "'" + s + "'"

def _create_columns(stmt):
    """Kolom dari satu blok CREATE TABLE: baris yang diawali `nama`
    (definisi kolom, bukan baris CONSTRAINT/UNIQUE/KEY)."""
    cols = set()
    for line in stmt.splitlines():
        m = re.match(r"\s*`(\w+)`\s", line)
        if m:
            cols.add(m.group(1))
    return cols


def main():
    schema = SCHEMA_SQL.read_text(encoding="utf-8")
    # Ambil blok CREATE TABLE ... ; dari schema.sql (sudah MySQL).
    creates = re.findall(r"CREATE TABLE.*?;\s*\n", schema, flags=re.S | re.I)
    con = sqlite3.connect(f"file:{SQLITE_DB}?mode=ro", uri=True)
    cur = con.cursor()
    # Validasi: schema.sql harus sinkron dengan database SQLite (hasil
    # semua migrasi). Pernah kejadian (2026-11-09): migrasi 000026
    # menambah kolom mapel.salinan_dari_id tapi schema.sql lupa
    # di-update — dump yang dihasilkan jadi tidak bisa di-import
    # (CREATE TABLE tanpa kolomnya, INSERT menyebut kolomnya).
    creates_by_table = {}
    for stmt in creates:
        m = re.search(r"CREATE TABLE (?:IF NOT EXISTS )?`(\w+)`", stmt, flags=re.I)
        if m:
            creates_by_table[m.group(1)] = stmt
    db_tables = [r[0] for r in cur.execute(
        "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")]
    problems = []
    for table in db_tables:
        if table in SKIP_TABLES:
            continue
        if table not in creates_by_table:
            problems.append(f"tabel `{table}` tidak ada di schema.sql")
            continue
        db_cols = [r[1] for r in cur.execute(f"PRAGMA table_info(`{table}`)")]
        schema_cols = _create_columns(creates_by_table[table])
        absent = [c for c in db_cols if c not in schema_cols]
        if absent:
            problems.append(
                f"tabel `{table}` kekurangan kolom di schema.sql: "
                + ", ".join(absent))
    if problems:
        for p in problems:
            print("ERROR:", p, file=sys.stderr)
        print("Perbaiki schema.sql dulu (sesuaikan dengan migrasi "
              "terbaru), lalu jalankan lagi.", file=sys.stderr)
        return 1
    tables = db_tables
    out_lines = []
    out_lines.append("-- ============================================================")
    out_lines.append("-- Buku Induk SMKN 5 Surakarta — Dump MySQL (struktur + data)")
    out_lines.append("-- Sumber: database-imported.sqlite (hasil import data lama)")
    out_lines.append("-- BERISI DATA ASLI SISWA — jangan dibagikan sembarangan.")
    out_lines.append("-- Cara pakai: buat database kosong di phpMyAdmin, lalu Import file ini.")
    out_lines.append("-- ============================================================")
    out_lines.append("SET NAMES utf8mb4;")
    out_lines.append("SET FOREIGN_KEY_CHECKS=0;")
    out_lines.append("")
    # CREATE TABLE dulu untuk semua tabel data (urutkan sesuai schema.sql).
    for stmt in creates:
        m = re.search(r"CREATE TABLE (?:IF NOT EXISTS )?`(\w+)`", stmt, flags=re.I)
        if m and m.group(1) in SKIP_TABLES:
            continue
        out_lines.append(stmt.strip())
        out_lines.append("")
    counts = {}
    for table in tables:
        if table in SKIP_TABLES:
            continue
        cols = [r[1] for r in cur.execute(f"PRAGMA table_info(`{table}`)")]
        if not cols:
            continue
        rows = cur.execute(f"SELECT * FROM `{table}`").fetchall()
        counts[table] = len(rows)
        if not rows:
            continue
        col_list = ", ".join(f"`{c}`" for c in cols)
        out_lines.append(f"-- Data tabel `{table}` ({len(rows)} baris)")
        batch = []
        for row in rows:
            vals = ", ".join(mysql_escape(v) for v in row)
            batch.append(f"({vals})")
            if len(batch) >= 500:
                out_lines.append(f"INSERT INTO `{table}` ({col_list}) VALUES")
                out_lines.append(",\n".join(batch) + ";")
                batch = []
        if batch:
            out_lines.append(f"INSERT INTO `{table}` ({col_list}) VALUES")
            out_lines.append(",\n".join(batch) + ";")
        out_lines.append("")
    out_lines.append("SET FOREIGN_KEY_CHECKS=1;")
    OUT.write_text("\n".join(out_lines), encoding="utf-8")
    total = sum(counts.values())
    print("OK", OUT, OUT.stat().st_size, "bytes")
    for t in sorted(counts):
        print(f"  {t}: {counts[t]}")
    print("TOTAL baris data:", total)

if __name__ == "__main__":
    sys.exit(main())
