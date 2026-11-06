<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Berita;
use App\Models\Siswa;
use App\Models\TahunAjaran;

class DashboardController extends Controller
{
    public function index()
    {
        $tahunAktif = TahunAjaran::aktif();

        return view('admin.dashboard', [
            'tahunAktif' => $tahunAktif,
            'totalSiswa' => Siswa::count(),
            'siswaAktif' => Siswa::where('status', 'aktif')->count(),
            'siswaBaru' => $tahunAktif ? Siswa::where('tahun_ajaran_id', $tahunAktif->id)->count() : 0,
            'alumni' => Siswa::where('status', 'lulus')->count(),
            'beritaDraft' => Berita::where('status', 'draft')->count(),
            'beritaTerbaru' => Berita::latest()->take(5)->get(),
            'logTerbaru' => ActivityLog::with('user')->latest('created_at')->take(8)->get(),
            'perJurusan' => Siswa::selectRaw('jurusan_id, COUNT(*) as jumlah')->groupBy('jurusan_id')->with('jurusan')->get(),
        ]);
    }
}
