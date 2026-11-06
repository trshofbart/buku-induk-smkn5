<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ProfilSekolah;
use Illuminate\Http\Request;

class ProfilAdminController extends Controller
{
    public function index()
    {
        return view('admin.profil.index', [
            'profil' => ProfilSekolah::orderBy('urutan')->get(),
        ]);
    }

    public function edit(ProfilSekolah $profil)
    {
        return view('admin.profil.form', compact('profil'));
    }

    public function update(Request $request, ProfilSekolah $profil)
    {
        $data = $request->validate([
            'judul' => 'required|string|max:200',
            'isi' => 'required|string',
            'urutan' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $profil->update($data);
        ActivityLog::catat('profil.update', "Ubah halaman profil: {$profil->judul}", $profil);
        return redirect()->route('admin.profil.index')->with('status', 'Halaman profil berhasil diperbarui dan langsung tampil di halaman depan.');
    }
}
