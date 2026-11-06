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

class NilaiController extends Controller
{
    /** Menu Input Nilai: pilih Tahun Ajaran → Semester → Kelas → Mapel, lalu isi nilai per siswa. */
    public function index(Request $request)
    {
        $tahun = $request->filled('tahun_ajaran_id')
            ? TahunAjaran::findOrFail($request->tahun_ajaran_id)
            : TahunAjaran::aktif();
        $semester = max(1, min(8, (int) ($request->semester ?? 1)));
        $tingkat = $semester <= 2 ? 'X' : ($semester <= 4 ? 'XI' : 'XII');

        $kelas = $request->filled('kelas_id')
            ? Kelas::with(['jurusan', 'tahunAjaran'])->findOrFail($request->kelas_id)
            : null;
        $mapel = $request->filled('mapel_id') ? Mapel::findOrFail($request->mapel_id) : null;

        $daftarMapel = $kelas
            ? Mapel::untukJurusan($kelas->jurusan_id)->untukTingkat($tingkat)->terurut()->get()
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
