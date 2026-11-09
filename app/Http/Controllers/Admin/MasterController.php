<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DataSekolah;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Mapel;
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
            'program_studi' => 'nullable|string|max:100',
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

    // ---------- Mata Pelajaran ----------
    public function mapelIndex()
    {
        return view('admin.master.mapel', [
            'mapel' => Mapel::with('jurusan')->orderBy('kelompok')->orderBy('urutan')->paginate(25),
            'jurusan' => Jurusan::orderBy('nama')->get(),
        ]);
    }

    public function mapelStore(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:150',
            'kelompok' => 'required|in:A,B,C1,C2,C3',
            'jurusan_id' => 'nullable|exists:jurusan,id',
            'tingkat' => 'nullable|in:X,XI,XII',
            'urutan' => 'nullable|integer|min:0',
            'kkm_default' => 'nullable|numeric|min:0|max:100',
        ]);
        Mapel::updateOrCreate(
            ['nama' => $data['nama'], 'jurusan_id' => $data['jurusan_id'] ?? null, 'tingkat' => $data['tingkat'] ?? null],
            $data
        );
        return back()->with('status', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function mapelDestroy(Mapel $mapel)
    {
        if ($mapel->nilai()->exists()) {
            return back()->withErrors(['mapel' => 'Mapel ini sudah dipakai data nilai, tidak bisa dihapus.']);
        }
        $mapel->delete();
        return back()->with('status', 'Mata pelajaran berhasil dihapus.');
    }

    /** Daftar C2/C3 yang akan tercetak untuk satu jurusan (Admin TU). */
    public function mapelPerJurusan(Request $request)
    {
        $jurusan = $request->filled('jurusan_id')
            ? Jurusan::findOrFail($request->jurusan_id)
            : Jurusan::orderBy('nama')->firstOrFail();

        // Daftar efektif per tingkat: gabungan mapel umum + khusus jurusan
        // (aturan Mapel::untukJurusanTingkat; salinan menggantikan asalnya).
        $daftar = [];
        foreach (['X', 'XI', 'XII'] as $tingkat) {
            $daftar[$tingkat] = Mapel::untukJurusanTingkat($jurusan->id, $tingkat)
                ->whereIn('kelompok', ['C2', 'C3'])
                ->groupBy('kelompok');
        }
        $punyaKhusus = Mapel::where('jurusan_id', $jurusan->id)
            ->whereIn('kelompok', ['C2', 'C3'])->exists();

        return view('admin.master.mapel-per-jurusan', [
            'jurusan' => $jurusan,
            'daftarJurusan' => Jurusan::orderBy('nama')->get(),
            'daftar' => $daftar,
            'punyaKhusus' => $punyaKhusus,
        ]);
    }

    /** Salin daftar C2/C3 umum menjadi daftar khusus jurusan ini (untuk diedit). */
    public function mapelSalinUmum(Request $request)
    {
        $data = $request->validate(['jurusan_id' => 'required|exists:jurusan,id']);
        $disalin = 0;
        foreach (Mapel::whereNull('jurusan_id')->whereIn('kelompok', ['C2', 'C3'])->get() as $m) {
            $baru = Mapel::firstOrCreate(
                ['nama' => $m->nama, 'jurusan_id' => $data['jurusan_id'], 'tingkat' => $m->tingkat],
                ['kelompok' => $m->kelompok, 'urutan' => $m->urutan, 'kkm_default' => $m->kkm_default,
                    'salinan_dari_id' => $m->id]
            );
            if ($baru->wasRecentlyCreated) {
                $disalin++;
            }
        }

        return back()->with('status', "$disalin mapel C2/C3 umum disalin ke jurusan ini. Silakan ubah nama atau hapus sesuai daftar jurusan.");
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
            'bidang_studi_keahlian' => 'required|string|max:100',
            'tempat_ttd' => 'nullable|string|max:100',
            'tanggal_rapor' => 'nullable|date',
        ]);
        $data['input_nilai_dibuka'] = $request->boolean('input_nilai_dibuka');
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

    /** Ganti password akun pegawai (Admin TU). Password baru wajib diketik 2x supaya tidak salah ketik. */
    public function penggunaPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);
        $user->update(['password' => Hash::make($data['password'])]);
        ActivityLog::catat('pengguna.password', "Password akun {$user->email} diganti.");
        return back()->with('status', "Password akun {$user->name} berhasil diganti.");
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
