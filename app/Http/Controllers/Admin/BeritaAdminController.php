<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Berita;
use App\Models\KategoriBerita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BeritaAdminController extends Controller
{
    public function index(Request $request)
    {
        $berita = $this->terapkanSort(
            $request,
            Berita::with('kategori')
                ->when($request->filled('q'), fn ($q) => $q->where('judul', 'like', '%'.$request->q.'%'))
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status)),
            ['judul', 'status', 'published_at'],
            'published_at', 'desc'
        )->paginate($this->perPage($request))->withQueryString();

        return view('admin.berita.index', ['berita' => $berita]);
    }

    public function create()
    {
        return view('admin.berita.form', [
            'berita' => new Berita(),
            'kategori' => KategoriBerita::orderBy('nama')->get(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);
        $data['user_id'] = auth()->id();
        $data['slug'] = Berita::buatSlug($data['judul']);
        $data['foto'] = $this->simpanFoto($request);
        if ($data['status'] === 'publish' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }
        $berita = Berita::create($data);
        ActivityLog::catat('berita.create', "Tambah berita: {$berita->judul}", $berita);
        return redirect()->route('admin.berita.index')->with('status', 'Berita berhasil ditambahkan.');
    }

    public function edit(Berita $berita)
    {
        return view('admin.berita.form', [
            'berita' => $berita,
            'kategori' => KategoriBerita::orderBy('nama')->get(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Berita $berita)
    {
        $data = $this->validasi($request, $berita->id);
        $data['slug'] = Berita::buatSlug($data['judul'], $berita->id);
        if ($foto = $this->simpanFoto($request)) {
            if ($berita->foto) Storage::disk('public')->delete($berita->foto);
            $data['foto'] = $foto;
        }
        if ($data['status'] === 'publish' && empty($berita->published_at) && empty($data['published_at'])) {
            $data['published_at'] = now();
        }
        $berita->update($data);
        ActivityLog::catat('berita.update', "Ubah berita: {$berita->judul}", $berita);
        return redirect()->route('admin.berita.index')->with('status', 'Berita berhasil diperbarui.');
    }

    public function destroy(Berita $berita)
    {
        if ($berita->foto) Storage::disk('public')->delete($berita->foto);
        ActivityLog::catat('berita.delete', "Hapus berita: {$berita->judul}", $berita);
        $berita->delete();
        return redirect()->route('admin.berita.index')->with('status', 'Berita berhasil dihapus.');
    }

    private function validasi(Request $request, ?int $kecualiId = null): array
    {
        return $request->validate([
            'kategori_berita_id' => 'nullable|exists:kategori_berita,id',
            'judul' => 'required|string|max:255',
            'ringkasan' => 'nullable|string',
            'isi' => 'required|string',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status' => 'required|in:draft,publish',
            'published_at' => 'nullable|date',
        ]);
    }

    private function simpanFoto(Request $request): ?string
    {
        if (! $request->hasFile('foto')) return null;
        return $request->file('foto')->store('berita', 'public');
    }
}
