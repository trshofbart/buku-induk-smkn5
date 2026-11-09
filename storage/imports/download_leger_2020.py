#!/usr/bin/env python3
"""Unduh semua file Leger per kelas 2020-2021 (SUDAH DI RANKING / SEMESTER 1-8) dari Drive.

Tujuan: storage/imports/source/2020-2021/LEDGER/SMT{n}/<nama aman>.xlsx
Hanya file .xlsx (bukan ~$ lock, bukan file lain). Idempotent: file yang
sudah ada dengan ukuran > 0 dilewati.
"""
import json
import subprocess
import sys
from pathlib import Path

BASE = Path("/home/hatch/workspace/buku-induk-smkn5/storage/imports/source/2020-2021/LEDGER")
# Folder SEMESTER 1-8 di dalam 'SUDAH DI RANKING' (2020-2021).
SMT_FOLDER = {
    1: "1s3yrVh7DVgWEwltrlhjzfuLs0ILTmwuD",
    2: "1wGwBrNVeHsZdQAkrpjqPWENxnzDjDHFQ",
    3: "1MVqyagPYpLtKlWlyyCAMovwxRTB34Gdi",
    4: "1MzMQGnA135sBJmjvbCmmavOVf2GvUtWn",
    5: "16GFgT5DHC--K6PtZZ0Y2-RdXr6wxXs2Y",
    6: "1FhZfQvDr2LkeOWVGIUZw_LNuZ3A0IXx0",
    7: "1P9yu5pw_eifU6v4wb1YmknDYAQSFt30p",
    8: "1OQ_GE9jq0YQphJiraJQP77znBIyJzbzv",
}
CLI = "/opt/hatch/bin/hatch_gws_cli"


def daftar(folder_id):
    r = subprocess.run(
        [CLI, "drive", "files", "list", "--params", json.dumps({
            "q": f"'{folder_id}' in parents and trashed=false",
            "pageSize": 100, "fields": "files(id,name,mimeType)"})],
        capture_output=True, text=True)
    d = json.loads(r.stdout)
    return [(f["name"], f["id"], f["mimeType"]) for f in d.get("files", [])]


def aman(nama):
    return nama.replace(" ", "_").replace("(", "").replace(")", "")


def main():
    ok = lewati = gagal = 0
    manifest = {}
    for smt, fid in sorted(SMT_FOLDER.items()):
        files = [(n, i, m) for n, i, m in daftar(fid)
                 if n.endswith(".xlsx") and not n.startswith("~$")]
        manifest[smt] = files
        folder = BASE / f"SMT{smt}"
        folder.mkdir(parents=True, exist_ok=True)
        for nama, file_id, _mt in files:
            tujuan = folder / aman(nama)
            if tujuan.exists() and tujuan.stat().st_size > 0:
                lewati += 1
                continue
            r = subprocess.run(
                [CLI, "drive", "files", "get", "--params",
                 json.dumps({"fileId": file_id, "alt": "media"}),
                 "--output", str(tujuan)],
                capture_output=True, text=True)
            if r.returncode == 0 and tujuan.exists() and tujuan.stat().st_size > 0:
                ok += 1
            else:
                gagal += 1
                print(f"GAGAL SMT{smt} {nama}: {r.stderr.strip()[-200:]}", file=sys.stderr)
    Path("/tmp/ledger_2020_files.json").write_text(json.dumps(manifest))
    print(f"diunduh={ok} dilewati={lewati} gagal={gagal}")


if __name__ == "__main__":
    main()
