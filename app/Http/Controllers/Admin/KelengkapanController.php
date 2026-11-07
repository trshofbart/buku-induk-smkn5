<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ekstrakurikuler;
use App\Models\Kelas;
use App\Models\Kelengkapan;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;

class KelengkapanController extends Controller
{
    /**
     * Kelengkapan per siswa per semester ala e-Rapor (diisi Wali Kelas;
     * di aplikasi ini oleh Admin TU): kehadiran, kepribadian, akhlak
     * mulia, catatan wali kelas, kenaikan kelas — plus ekstrakurikuler.
     * Data ini yang mengisi tabel-tabel di halaman Cetak Buku Induk.
     */
    public function index(Request $request)
    {
        $tahun = $request->filled('tahun_ajaran_id')
            ? TahunAjaran::findOrFail($request->tahun_ajaran_id)
            : TahunAjaran::aktif();
        $semester = max(1, min(8, (int) ($request->semester ?? 1)));
        $kelas = $request->filled('kelas_id')
            ? Kelas::with(['jurusan', 'tahunAjaran'])->findOrFail($request->kelas_id)
            : null;

        $siswa = collect();
        $kelengkapan = collect();
        $ekskul = collect();
        if ($kelas && $tahun) {
            $siswa = Siswa::where('kelas_id', $kelas->id)->orderBy('nama')->get();
            $kelengkapan = Kelengkapan::where('tahun_ajaran_id', $tahun->id)
                ->where('semester', $semester)
                ->whereIn('siswa_id', $siswa->pluck('id'))
                ->get()->keyBy('siswa_id');
            $ekskul = Ekstrakurikuler::with('siswa')
                ->where('tahun_ajaran_id', $tahun->id)
                ->where('semester', $semester)
                ->whereIn('siswa_id', $siswa->pluck('id'))
                ->orderBy('siswa_id')->orderBy('kegiatan')->get();
        }

        return view('admin.kelengkapan.index', [
            'tahun' => $tahun,
            'tahunAjaran' => TahunAjaran::orderByDesc('nama')->get(),
            'semester' => $semester,
            'kelas' => $kelas,
            'daftarKelas' => Kelas::with(['jurusan', 'tahunAjaran'])->orderByDesc('tahun_ajaran_id')->orderBy('nama')->get(),
            'siswa' => $siswa,
            'kelengkapan' => $kelengkapan,
            'ekskul' => $ekskul,
            'sikap' => Kelengkapan::SIKAP,
        ]);
    }

    /** Simpan kehadiran + sikap + catatan + kenaikan satu kelas satu semester. */
    public function store(Request $request)
    {
        abort_unless(auth()->user()?->canEdit(), 403, 'Hanya Admin TU yang boleh mengisi kelengkapan.');

        $data = $request->validate([
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|integer|min:1|max:8',
            'kelengkapan' => 'required|array',
            'kelengkapan.*.sakit' => 'nullable|integer|min:0|max:365',
            'kelengkapan.*.izin' => 'nullable|integer|min:0|max:365',
            'kelengkapan.*.alpa' => 'nullable|integer|min:0|max:365',
            'kelengkapan.*.kelakuan' => 'nullable|in:Amat Baik,Baik,Cukup,Kurang',
            'kelengkapan.*.kerapian' => 'nullable|in:Amat Baik,Baik,Cukup,Kurang',
            'kelengkapan.*.kerajinan' => 'nullable|in:Amat Baik,Baik,Cukup,Kurang',
            'kelengkapan.*.akhlak' => 'nullable|in:Amat Baik,Baik,Cukup,Kurang',
            'kelengkapan.*.catatan' => 'nullable|string|max:500',
            'kelengkapan.*.kenaikan' => 'nullable|string|max:100',
        ]);

        $tersimpan = 0;
        foreach ($data['kelengkapan'] as $siswaId => $row) {
            if (! Siswa::whereKey($siswaId)->exists()) {
                continue;
            }
            Kelengkapan::updateOrCreate(
                [
                    'siswa_id' => $siswaId,
                    'tahun_ajaran_id' => $data['tahun_ajaran_id'],
                    'semester' => $data['semester'],
                ],
                [
                    'sakit' => $row['sakit'] ?? 0,
                    'izin' => $row['izin'] ?? 0,
                    'alpa' => $row['alpa'] ?? 0,
                    'kelakuan' => $row['kelakuan'] ?? null,
                    'kerapian' => $row['kerapian'] ?? null,
                    'kerajinan' => $row['kerajinan'] ?? null,
                    'akhlak' => $row['akhlak'] ?? null,
                    'catatan' => $row['catatan'] ?? null,
                    'kenaikan' => $row['kenaikan'] ?? null,
                ]
            );
            $tersimpan++;
        }

        return back()->with('status', "Kelengkapan semester {$data['semester']} tersimpan untuk {$tersimpan} siswa.");
    }

    /** Tambah satu kegiatan ekstrakurikuler untuk seorang siswa. */
    public function ekstrakurikulerStore(Request $request)
    {
        abort_unless(auth()->user()?->canEdit(), 403, 'Hanya Admin TU yang boleh mengisi ekstrakurikuler.');

        $data = $request->validate([
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|integer|min:1|max:8',
            'siswa_id' => 'required|exists:siswa,id',
            'kegiatan' => 'required|string|max:150',
            'nilai' => 'nullable|string|max:30',
            'keterangan' => 'nullable|string|max:150',
        ]);
        Ekstrakurikuler::create($data);

        return back()->with('status', 'Kegiatan ekstrakurikuler berhasil ditambahkan.');
    }

    /** Hapus satu kegiatan ekstrakurikuler. */
    public function ekstrakurikulerDestroy(Ekstrakurikuler $ekstrakurikuler)
    {
        abort_unless(auth()->user()?->canEdit(), 403, 'Hanya Admin TU yang boleh menghapus ekstrakurikuler.');
        $ekstrakurikuler->delete();

        return back()->with('status', 'Kegiatan ekstrakurikuler berhasil dihapus.');
    }
}
