#!/usr/bin/env python3
"""Unduh semua file Leger per kelas 2021-2022 (SEMESTER 1-8) dari Drive.

Daftar file dari /tmp/ledger_2021_files.json (id, nama, mimeType).
Tujuan: storage/imports/source/2021-2022/LEDGER/SMT{n}/<nama aman>.xlsx
Idempotent: file yang sudah ada dengan ukuran > 0 dilewati.
"""
import json
import subprocess
import sys
from pathlib import Path

BASE = Path("/home/hatch/workspace/buku-induk-smkn5/storage/imports/source/2021-2022/LEDGER")
MANIFEST = Path("/tmp/ledger_2021_files.json")


def aman(nama):
    return nama.replace(" ", "_").replace("(", "").replace(")", "")


def main():
    data = json.loads(MANIFEST.read_text())
    ok = lewati = gagal = 0
    for smt in sorted(data, key=int):
        folder = BASE / f"SMT{smt}"
        folder.mkdir(parents=True, exist_ok=True)
        for nama, fid, mt in data[smt]:
            tujuan = folder / aman(nama)
            if tujuan.exists() and tujuan.stat().st_size > 0:
                lewati += 1
                continue
            r = subprocess.run(
                ["/opt/hatch/bin/hatch_gws_cli", "drive", "files", "get",
                 "--params", json.dumps({"fileId": fid, "alt": "media"}),
                 "--output", str(tujuan)],
                capture_output=True, text=True)
            if r.returncode == 0 and tujuan.exists() and tujuan.stat().st_size > 0:
                ok += 1
            else:
                gagal += 1
                print(f"GAGAL SMT{smt} {nama}: {r.stderr.strip()[-200:]}", file=sys.stderr)
    print(f"diunduh={ok} dilewati={lewati} gagal={gagal}")


if __name__ == "__main__":
    main()
