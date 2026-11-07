<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
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
}
