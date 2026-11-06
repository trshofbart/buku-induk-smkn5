<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Ledger;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Transkrip;
use Illuminate\Http\Request;

class BukuIndukController extends Controller
{
    /** Pilih Tahun Ajaran → daftar jurusan/kelas. */
    public function index(Request $request)
    {
        $tahun = $request->filled('tahun_ajaran_id')
            ? TahunAjaran::findOrFail($request->tahun_ajaran_id)
            : TahunAjaran::aktif();

        $jurusan = Jurusan::withCount(['siswa' => fn ($q) => $q->where('tahun_ajaran_id', $tahun?->id)])
            ->orderBy('nama')->get();

        $kelas = $tahun ? Kelas::with('jurusan')->where('tahun_ajaran_id', $tahun->id)->orderBy('nama')->get() : collect();

        return view('admin.buku-induk.index', [
            'tahun' => $tahun,
            'tahunAjaran' => TahunAjaran::orderByDesc('nama')->get(),
            'jurusan' => $jurusan,
            'kelas' => $kelas,
        ]);
    }

    /** Daftar induk per kelas (Master Siswa). */
    public function perKelas(Request $request, Kelas $kelas)
    {
        $siswa = Siswa::with(['jurusan', 'tahunAjaran'])
            ->where('kelas_id', $kelas->id)
            ->cari($request->q)
            ->orderBy('nama')->paginate(20)->withQueryString();

        return view('admin.buku-induk.per-kelas', [
            'kelas' => $kelas->load(['jurusan', 'tahunAjaran']),
            'siswa' => $siswa,
        ]);
    }

    /** Data Nominatif per kelas (pengganti file DATA NOMINATIF SISWA KELAS XII/XIII). */
    public function nominatif(Request $request)
    {
        $kelas = $request->filled('kelas_id') ? Kelas::with(['jurusan', 'tahunAjaran'])->findOrFail($request->kelas_id) : null;
        $siswa = $kelas
            ? Siswa::where('kelas_id', $kelas->id)->orderBy('nama')->get()
            : collect();

        return view('admin.buku-induk.nominatif', [
            'kelas' => $kelas,
            'siswa' => $siswa,
            'daftarKelas' => Kelas::with(['jurusan', 'tahunAjaran'])->orderByDesc('tahun_ajaran_id')->orderBy('nama')->get(),
            'sekolah' => \App\Models\DataSekolah::get(),
        ]);
    }

    /** Ledger per semester 1-8 (pengganti folder LEDGER ... SEMESTER 1-8). */
    public function ledger(Request $request)
    {
        $semester = (int) ($request->semester ?? 1);
        $semester = max(1, min(8, $semester));

        $data = Ledger::with(['siswa.kelas', 'siswa.jurusan'])
            ->when($request->filled('tahun_ajaran_id'), fn ($q) => $q->where('tahun_ajaran_id', $request->tahun_ajaran_id))
            ->where('semester', $semester)
            ->orderBy('siswa_id')->paginate(20)->withQueryString();

        return view('admin.buku-induk.ledger', [
            'data' => $data,
            'semester' => $semester,
            'tahunAjaran' => TahunAjaran::orderByDesc('nama')->get(),
            'tahunAktif' => $request->tahun_ajaran_id ?? TahunAjaran::aktif()?->id,
        ]);
    }

    /** Simpan / ubah satu baris ledger. */
    public function ledgerStore(Request $request)
    {
        abort_unless(auth()->user()?->canEdit(), 403);
        $data = $request->validate([
            'siswa_id' => 'required|exists:siswa,id',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|integer|min:1|max:8',
            'rata_rata' => 'nullable|numeric|min:0|max:100',
            'peringkat' => 'nullable|integer|min:1',
            'catatan' => 'nullable|string',
        ]);
        Ledger::updateOrCreate(
            ['siswa_id' => $data['siswa_id'], 'semester' => $data['semester'], 'tahun_ajaran_id' => $data['tahun_ajaran_id']],
            $data
        );
        return back()->with('status', 'Ledger semester '.$data['semester'].' berhasil disimpan.');
    }

    /** Database Transkrip (pengganti file DATABASE TRANSKRIP). */
    public function transkrip(Request $request)
    {
        $data = Transkrip::with(['siswa.kelas', 'tahunAjaran'])
            ->when($request->filled('q'), fn ($q) => $q->whereHas('siswa', fn ($s) => $s->where('nama', 'like', '%'.$request->q.'%')->orWhere('nis', 'like', '%'.$request->q.'%')))
            ->when($request->filled('tahun_ajaran_id'), fn ($q) => $q->where('tahun_ajaran_id', $request->tahun_ajaran_id))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.buku-induk.transkrip', [
            'data' => $data,
            'tahunAjaran' => TahunAjaran::orderByDesc('nama')->get(),
        ]);
    }

    public function transkripStore(Request $request)
    {
        abort_unless(auth()->user()?->canEdit(), 403);
        $data = $request->validate([
            'siswa_id' => 'required|exists:siswa,id',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'nomor_transkrip' => 'nullable|string|max:50|unique:transkrip,nomor_transkrip',
            'tanggal_terbit' => 'nullable|date',
            'keterangan' => 'nullable|string',
        ]);
        Transkrip::updateOrCreate(
            ['siswa_id' => $data['siswa_id'], 'tahun_ajaran_id' => $data['tahun_ajaran_id']],
            $data
        );
        return back()->with('status', 'Transkrip berhasil disimpan.');
    }
}
