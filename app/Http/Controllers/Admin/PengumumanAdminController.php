<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use Illuminate\Http\Request;

class PengumumanAdminController extends Controller
{
    public function index()
    {
        return view('admin.pengumuman.index', ['pengumuman' => Pengumuman::latest()->paginate(15)]);
    }

    public function create()
    {
        return view('admin.pengumuman.form', ['pengumuman' => new Pengumuman(), 'mode' => 'create']);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);
        $data['is_active'] = $request->boolean('is_active');
        Pengumuman::create($data);
        return redirect()->route('admin.pengumuman.index')->with('status', 'Pengumuman berhasil ditambahkan.');
    }

    public function edit(Pengumuman $pengumuman)
    {
        return view('admin.pengumuman.form', ['pengumuman' => $pengumuman, 'mode' => 'edit']);
    }

    public function update(Request $request, Pengumuman $pengumuman)
    {
        $data = $this->validasi($request);
        $data['is_active'] = $request->boolean('is_active');
        $pengumuman->update($data);
        return redirect()->route('admin.pengumuman.index')->with('status', 'Pengumuman berhasil diperbarui.');
    }

    public function destroy(Pengumuman $pengumuman)
    {
        $pengumuman->delete();
        return redirect()->route('admin.pengumuman.index')->with('status', 'Pengumuman berhasil dihapus.');
    }

    private function validasi(Request $request): array
    {
        return $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
        ]);
    }
}
