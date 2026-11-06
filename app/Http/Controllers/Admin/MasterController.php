<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DataSekolah;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class MasterController extends Controller
{
    // ---------- Tahun Ajaran ----------
    public function tahunIndex()
    {
        return view('admin.master.tahun-ajaran', ['tahun' => TahunAjaran::orderByDesc('nama')->paginate(15)]);
    }

    public function tahunStore(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:20|unique:tahun_ajaran,nama',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date',
        ]);
        TahunAjaran::create($data);
        return back()->with('status', 'Tahun ajaran berhasil ditambahkan.');
    }

    public function tahunAktif(TahunAjaran $tahun)
    {
        $tahun->setAktif();
        return back()->with('status', "Tahun ajaran {$tahun->nama} sekarang aktif.");
    }

    public function tahunDestroy(TahunAjaran $tahun)
    {
        if ($tahun->siswa()->exists() || $tahun->kelas()->exists()) {
            return back()->withErrors(['tahun' => 'Tahun ajaran ini masih dipakai data siswa/kelas, tidak bisa dihapus.']);
        }
        $tahun->delete();
        return back()->with('status', 'Tahun ajaran berhasil dihapus.');
    }

    // ---------- Jurusan ----------
    public function jurusanIndex()
    {
        return view('admin.master.jurusan', ['jurusan' => Jurusan::withCount('siswa')->orderBy('nama')->paginate(15)]);
    }

    public function jurusanStore(Request $request)
    {
        $data = $request->validate([
            'kode' => 'required|string|max:10|unique:jurusan,kode',
            'nama' => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
        ]);
        Jurusan::create($data);
        return back()->with('status', 'Jurusan berhasil ditambahkan.');
    }

    public function jurusanDestroy(Jurusan $jurusan)
    {
        if ($jurusan->siswa()->exists()) {
            return back()->withErrors(['jurusan' => 'Jurusan ini masih dipakai data siswa, tidak bisa dihapus.']);
        }
        $jurusan->delete();
        return back()->with('status', 'Jurusan berhasil dihapus.');
    }

    // ---------- Kelas ----------
    public function kelasIndex()
    {
        return view('admin.master.kelas', [
            'kelas' => Kelas::with(['jurusan', 'tahunAjaran'])->orderByDesc('tahun_ajaran_id')->orderBy('nama')->paginate(15),
            'tahunAjaran' => TahunAjaran::orderByDesc('nama')->get(),
            'jurusan' => Jurusan::orderBy('nama')->get(),
        ]);
    }

    public function kelasStore(Request $request)
    {
        $data = $request->validate([
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'jurusan_id' => 'required|exists:jurusan,id',
            'tingkat' => 'required|in:X,XI,XII,XIII',
            'nama' => 'required|string|max:50',
            'wali_kelas' => 'nullable|string|max:100',
        ]);
        Kelas::create($data);
        return back()->with('status', 'Kelas berhasil ditambahkan.');
    }

    public function kelasDestroy(Kelas $kelas)
    {
        if ($kelas->siswa()->exists()) {
            return back()->withErrors(['kelas' => 'Kelas ini masih dipakai data siswa, tidak bisa dihapus.']);
        }
        $kelas->delete();
        return back()->with('status', 'Kelas berhasil dihapus.');
    }

    // ---------- Data Sekolah ----------
    public function dataSekolahEdit()
    {
        return view('admin.master.data-sekolah', ['sekolah' => DataSekolah::get()]);
    }

    public function dataSekolahUpdate(Request $request)
    {
        $data = $request->validate([
            'nama_sekolah' => 'required|string|max:150',
            'npsn' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'website' => 'nullable|string|max:100',
            'kepala_sekolah' => 'nullable|string|max:150',
        ]);
        DataSekolah::get()->update($data);
        return back()->with('status', 'Data sekolah berhasil diperbarui.');
    }

    // ---------- Pengguna (hanya Admin TU — dijaga route role:admin_tu) ----------
    public function penggunaIndex()
    {
        return view('admin.master.pengguna', ['pengguna' => User::orderBy('name')->paginate(15)]);
    }

    public function penggunaStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin_tu,kepala_sekolah,guru',
            'nip' => 'nullable|string|max:30',
        ]);
        $data['password'] = Hash::make($data['password']);
        User::create($data);
        return back()->with('status', 'Akun pegawai berhasil dibuat.');
    }

    public function penggunaToggle(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['pengguna' => 'Anda tidak bisa menonaktifkan akun sendiri.']);
        }
        $user->update(['is_active' => ! $user->is_active]);
        return back()->with('status', 'Status akun berhasil diubah.');
    }

    public function penggunaDestroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['pengguna' => 'Anda tidak bisa menghapus akun sendiri.']);
        }
        $user->delete();
        return back()->with('status', 'Akun pegawai berhasil dihapus.');
    }

    // ---------- Log Aktivitas (read-only) ----------
    public function logIndex()
    {
        return view('admin.master.log', [
            'log' => ActivityLog::with('user')->latest('created_at')->paginate(25),
        ]);
    }
}
