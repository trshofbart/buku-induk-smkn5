<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Ledger;
use App\Models\Mapel;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class NilaiController extends Controller
{
    /** Menu Input Nilai: pilih Tahun Ajaran → Semester → Kelas → Mapel, lalu isi nilai per siswa. */
    public function index(Request $request)
    {
        $tahun = $request->filled('tahun_ajaran_id')
            ? TahunAjaran::findOrFail($request->tahun_ajaran_id)
            : TahunAjaran::aktif();
        $semester = max(1, min(8, (int) ($request->semester ?? 1)));
        $tingkat = $request->filled('tingkat') ? $request->tingkat : null;
        if ($tingkat) {
            $range = ['X' => [1, 2], 'XI' => [3, 4], 'XII' => [5, 6]][$tingkat];
            $semester = max($range[0], min($range[1], $semester));
        } else {
            $tingkat = $semester <= 2 ? 'X' : ($semester <= 4 ? 'XI' : 'XII');
        }

        $kelas = $request->filled('kelas_id')
            ? Kelas::with(['jurusan', 'tahunAjaran'])->findOrFail($request->kelas_id)
            : null;
        $mapel = $request->filled('mapel_id') ? Mapel::findOrFail($request->mapel_id) : null;

        $daftarMapel = $kelas
            ? Mapel::untukJurusanTingkat($kelas->jurusan_id, $tingkat)
            : collect();

        $siswa = collect();
        $nilai = collect();
        if ($kelas && $mapel && $tahun) {
            $siswa = Siswa::where('kelas_id', $kelas->id)->orderBy('nama')->get();
            $nilai = Nilai::where('mapel_id', $mapel->id)
                ->where('tahun_ajaran_id', $tahun->id)
                ->where('semester', $semester)
                ->whereIn('siswa_id', $siswa->pluck('id'))
                ->get()->keyBy('siswa_id');
        }

        return view('admin.nilai.index', [
            'tahun' => $tahun,
            'tahunAjaran' => TahunAjaran::orderByDesc('nama')->get(),
            'semester' => $semester,
            'tingkat' => $tingkat,
            'kelas' => $kelas,
            'daftarKelas' => Kelas::with(['jurusan', 'tahunAjaran'])->orderByDesc('tahun_ajaran_id')->orderBy('nama')->get(),
            'mapel' => $mapel,
            'daftarMapel' => $daftarMapel,
            'siswa' => $siswa,
            'nilai' => $nilai,
        ]);
    }

    /** Simpan nilai satu mapel untuk satu kelas (satu semester + tahun ajaran). */
    public function store(Request $request)
    {
        abort_unless(auth()->user()?->canEdit(), 403, 'Hanya Admin TU yang boleh menginput nilai.');

        $data = $request->validate([
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|integer|min:1|max:8',
            'mapel_id' => 'required|exists:mapel,id',
            'nilai' => 'required|array',
            'nilai.*.kkm' => 'nullable|numeric|min:0|max:100',
            'nilai.*.angka' => 'nullable|numeric|min:0|max:100',
        ]);

        $mapel = Mapel::findOrFail($data['mapel_id']);
        $tersimpan = 0;
        $siswaDiubah = [];

        foreach ($data['nilai'] as $siswaId => $row) {
            if (($row['angka'] ?? null) === null || $row['angka'] === '') {
                continue;
            }
            if (! Siswa::whereKey($siswaId)->exists()) {
                continue;
            }
            Nilai::updateOrCreate(
                [
                    'siswa_id' => $siswaId,
                    'mapel_id' => $mapel->id,
                    'tahun_ajaran_id' => $data['tahun_ajaran_id'],
                    'semester' => $data['semester'],
                ],
                [
                    'kkm' => $row['kkm'] ?? $mapel->kkm_default,
                    'nilai_angka' => $row['angka'],
                ]
            );
            $tersimpan++;
            $siswaDiubah[] = (int) $siswaId;
        }

        foreach (array_unique($siswaDiubah) as $sid) {
            $this->hitungUlangLedger($sid, $data['tahun_ajaran_id'], $data['semester']);
        }

        return back()->with(
            'status',
            "Nilai {$mapel->nama} semester {$data['semester']} tersimpan untuk {$tersimpan} siswa; rata-rata ledger diperbarui."
        );
    }

    /** Unduh template import nilai (CSV) mengikuti kolom file MASTER: NO, NIS, NISN, NAMA, lalu satu kolom per mapel (nilai Pengetahuan). */
    public function template(Request $request)
    {
        $data = $request->validate([
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|integer|min:1|max:8',
            'kelas_id' => 'required|exists:kelas,id',
        ]);
        $kelas = Kelas::with('jurusan')->findOrFail($data['kelas_id']);
        $tingkat = $data['semester'] <= 2 ? 'X' : ($data['semester'] <= 4 ? 'XI' : 'XII');
        $mapel = Mapel::untukJurusanTingkat($kelas->jurusan_id, $tingkat);
        $siswa = Siswa::where('kelas_id', $kelas->id)->orderBy('nama')->get();

        $namaFile = 'template-nilai-'.str_replace(' ', '-', $kelas->nama).'-semester-'.$data['semester'].'.csv';
        $stream = fopen('php://temp', 'r+');
        fputs($stream, "\xEF\xBB\xBF"); // BOM supaya Excel membaca UTF-8
        fputcsv($stream, array_merge(['NO', 'NIS', 'NISN', 'NAMA'], $mapel->pluck('nama')->all()), ';');
        foreach ($siswa as $i => $s) {
            fputcsv($stream, array_merge([$i + 1, $s->nis, $s->nisn, $s->nama], array_fill(0, $mapel->count(), '')), ';');
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$namaFile.'"',
        ]);
    }

    /** Import nilai dari file CSV/Excel mengikuti kolom file MASTER (NO, NIS, NISN, NAMA + satu kolom per mapel). */
    public function import(Request $request)
    {
        abort_unless(auth()->user()?->canEdit(), 403, 'Hanya Admin TU yang boleh mengimport nilai.');

        $data = $request->validate([
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|integer|min:1|max:8',
            'kelas_id' => 'required|exists:kelas,id',
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);
        $kelas = Kelas::with('jurusan')->findOrFail($data['kelas_id']);
        $tingkat = $data['semester'] <= 2 ? 'X' : ($data['semester'] <= 4 ? 'XI' : 'XII');
        $mapel = Mapel::untukJurusanTingkat($kelas->jurusan_id, $tingkat);
        $petaMapel = $mapel->keyBy(fn ($m) => mb_strtolower(trim($m->nama)));

        try {
            $rows = $this->bacaFileNilai($request->file('file'));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }
        // Cari baris header (baris pertama yang berisi kolom NIS, maks. 8 baris pertama —
        // file MASTER menaruh header di baris ke-4, template di baris pertama).
        $idxHeader = null;
        foreach (array_slice($rows, 0, 8) as $i => $row) {
            $kecil = array_map(fn ($v) => mb_strtolower(trim((string) $v)), $row);
            if (in_array('nis', $kecil, true)) {
                $idxHeader = $i;
                break;
            }
        }
        if ($idxHeader === null || empty($rows)) {
            return back()->withErrors(['file' => 'Kolom NIS tidak ditemukan di 8 baris pertama file.']);
        }
        $header = array_map(fn ($v) => mb_strtolower(trim((string) $v)), $rows[$idxHeader]);
        $kolNis = array_search('nis', $header);
        $kolMapel = [];
        $kolomTakDikenal = [];
        foreach ($header as $i => $h) {
            if (in_array($h, ['no', 'nis', 'nisn', 'nama', ''], true)) {
                continue;
            }
            if (isset($petaMapel[$h])) {
                $kolMapel[$i] = $petaMapel[$h];
            } else {
                $kolomTakDikenal[] = $rows[$idxHeader][$i];
            }
        }
        if (empty($kolMapel)) {
            return back()->withErrors(['file' => 'Tidak ada kolom mata pelajaran yang cocok dengan daftar mapel tingkat '.$tingkat.' jurusan '.$kelas->jurusan?->nama.'.']);
        }

        $siswaKelas = Siswa::where('kelas_id', $kelas->id)->get()->keyBy(fn ($s) => (string) $s->nis);
        $diimport = 0;
        $dilewati = 0;
        $galat = [];
        $diubah = [];
        foreach (array_slice($rows, $idxHeader + 1) as $j => $row) {
            $nis = trim((string) ($row[$kolNis] ?? ''));
            if ($nis === '') {
                $dilewati++;
                continue;
            }
            $siswa = $siswaKelas->get($nis);
            if (! $siswa) {
                $galat[] = 'Baris '.($idxHeader + $j + 2).": NIS $nis bukan siswa kelas {$kelas->nama}.";
                continue;
            }
            foreach ($kolMapel as $i => $m) {
                $mentah = trim((string) ($row[$i] ?? ''));
                if ($mentah === '' || $mentah === '-') {
                    $dilewati++;
                    continue;
                }
                $angka = str_replace(',', '.', $mentah);
                if (! is_numeric($angka) || $angka < 0 || $angka > 100) {
                    $galat[] = 'Baris '.($idxHeader + $j + 2).": nilai '$mentah' untuk {$m->nama} (NIS $nis) tidak valid.";
                    continue;
                }
                Nilai::updateOrCreate(
                    ['siswa_id' => $siswa->id, 'mapel_id' => $m->id, 'tahun_ajaran_id' => $data['tahun_ajaran_id'], 'semester' => $data['semester']],
                    ['kkm' => $m->kkm_default, 'nilai_angka' => (float) $angka]
                );
                $diimport++;
                $diubah[$siswa->id] = true;
            }
        }
        foreach (array_keys($diubah) as $sid) {
            $this->hitungUlangLedger($sid, $data['tahun_ajaran_id'], $data['semester']);
        }

        $pesan = "Import selesai: $diimport nilai masuk, $dilewati sel kosong dilewati.";
        if ($kolomTakDikenal) {
            $pesan .= ' Kolom yang tidak dikenal (diabaikan): '.implode(', ', array_unique($kolomTakDikenal)).'.';
        }
        if ($galat) {
            $pesan .= ' '.count($galat).' galat.';
        }

        return back()->with('status', $pesan)->with('import_galat', array_slice($galat, 0, 20));
    }

    /** Baca file nilai CSV atau Excel menjadi array baris (semua sel sebagai string). */
    private function bacaFileNilai(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext === 'csv' || $ext === 'txt') {
            $rows = [];
            $handle = fopen($file->getRealPath(), 'r');
            $pertama = fgets($handle);
            rewind($handle);
            // Pemisah ';' (Excel Indonesia) atau ','.
            $delim = (substr_count($pertama, ';') > substr_count($pertama, ',')) ? ';' : ',';
            while (($row = fgetcsv($handle, 0, $delim)) !== false) {
                $rows[] = $row;
            }
            fclose($handle);

            return $rows;
        }
        if (! class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new \RuntimeException('PhpSpreadsheet belum terpasang. Jalankan: composer require phpoffice/phpspreadsheet');
        }

        return \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath())
            ->getActiveSheet()->toArray(null, true, true, false);
    }

    /** Rata-rata ledger = rata-rata semua nilai mapel siswa itu pada semester tersebut. */
    private function hitungUlangLedger(int $siswaId, int $tahunId, int $semester): void
    {
        $rata = Nilai::where('siswa_id', $siswaId)
            ->where('tahun_ajaran_id', $tahunId)
            ->where('semester', $semester)
            ->whereNotNull('nilai_angka')
            ->avg('nilai_angka');

        if ($rata === null) {
            return;
        }

        // Perbarui baris ledger semester itu bila sudah ada (tahun ajaran di
        // ledger lama memakai tahun kohort siswa); buat baru bila belum ada.
        $existing = Ledger::where('siswa_id', $siswaId)
            ->where('semester', $semester)
            ->orderBy('id')
            ->first();

        if ($existing) {
            $existing->update(['rata_rata' => round($rata, 2)]);
            return;
        }

        Ledger::create([
            'siswa_id' => $siswaId,
            'semester' => $semester,
            'tahun_ajaran_id' => $tahunId,
            'rata_rata' => round($rata, 2),
        ]);
    }

    /** Transkrip nilai per siswa, sesuai LAPORAN PENILAIAN file MASTER (HAL_PRINT). */
    public function transkrip(Siswa $siswa)
    {
        $siswa->load(['jurusan', 'kelas', 'tahunAjaran', 'orangTua']);

        return view('admin.nilai.transkrip', [
            'siswa' => $siswa,
            'sekolah' => \App\Models\DataSekolah::get(),
            'laporan' => $siswa->laporanNilai(),
            'transkrip' => $siswa->transkrip()->latest()->first(),
        ]);
    }
}
