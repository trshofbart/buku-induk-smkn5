<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\KategoriBerita;
use App\Models\Pengumuman;
use App\Models\ProfilSekolah;
use App\Models\Siswa;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function home()
    {
        return view('public.home', [
            'berita' => Berita::published()->with('kategori')->latest('published_at')->take(6)->get(),
            'pengumuman' => Pengumuman::aktif()->latest()->take(5)->get(),
            'profil' => ProfilSekolah::where('is_active', true)->orderBy('urutan')->get(),
            'statistik' => [
                'siswa_aktif' => Siswa::where('status', 'aktif')->count(),
                'alumni' => Siswa::where('status', 'lulus')->count(),
                'berita' => Berita::published()->count(),
            ],
        ]);
    }

    public function profil()
    {
        return view('public.profil-index', [
            'profil' => ProfilSekolah::where('is_active', true)->orderBy('urutan')->get(),
        ]);
    }

    public function profilShow(string $slug)
    {
        $halaman = ProfilSekolah::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $daftar = ProfilSekolah::where('is_active', true)->orderBy('urutan')->get();

        return view('public.profil-show', compact('halaman', 'daftar'));
    }

    public function berita(Request $request)
    {
        $berita = Berita::published()->with('kategori')
            ->when($request->filled('q'), fn ($q) => $q->where(function ($w) use ($request) {
                $w->where('judul', 'like', '%'.$request->q.'%')->orWhere('isi', 'like', '%'.$request->q.'%');
            }))
            ->when($request->filled('kategori'), fn ($q) => $q->whereHas('kategori', fn ($k) => $k->where('slug', $request->kategori)))
            ->latest('published_at')
            ->paginate(9)->withQueryString();

        return view('public.berita-index', [
            'berita' => $berita,
            'kategori' => KategoriBerita::orderBy('nama')->get(),
            'q' => $request->q,
            'kategoriAktif' => $request->kategori,
        ]);
    }

    public function beritaShow(string $slug)
    {
        $berita = Berita::published()->with('kategori', 'penulis')->where('slug', $slug)->firstOrFail();
        $berita->increment('views');

        $terkait = Berita::published()->where('id', '!=', $berita->id)
            ->where('kategori_berita_id', $berita->kategori_berita_id)
            ->latest('published_at')->take(4)->get();

        return view('public.berita-show', compact('berita', 'terkait'));
    }

    public function pengumuman()
    {
        return view('public.pengumuman-index', [
            'pengumuman' => Pengumuman::aktif()->latest()->paginate(10),
        ]);
    }
}
