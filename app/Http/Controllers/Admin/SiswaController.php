<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SiswaController extends Controller
{
    /** Daftar + pencarian NIS/NISN/nama + filter + pagination. */
    public function index(Request $request)
    {
        $siswa = Siswa::with(['jurusan', 'kelas', 'tahunAjaran'])
            ->cari($request->q)
            ->filter($request->only(['tahun_ajaran_id', 'jurusan_id', 'kelas_id', 'status']))
            ->orderBy('nama')
            ->paginate(15)->withQueryString();

        return view('admin.siswa.index', [
            'siswa' => $siswa,
            'tahunAjaran' => TahunAjaran::orderByDesc('nama')->get(),
            'jurusan' => Jurusan::orderBy('nama')->get(),
            'kelas' => Kelas::with('jurusan')->orderBy('nama')->get(),
            'filter' => $request->only(['q', 'tahun_ajaran_id', 'jurusan_id', 'kelas_id', 'status']),
        ]);
    }

    public function create()
    {
        $this->authorizeEdit();
        return view('admin.siswa.form', [
            'siswa' => new Siswa(),
            'tahunAjaran' => TahunAjaran::orderByDesc('nama')->get(),
            'jurusan' => Jurusan::orderBy('nama')->get(),
            'kelas' => Kelas::orderBy('nama')->get(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeEdit();
        $data = $this->validasi($request);

        $siswa = DB::transaction(function () use ($data, $request) {
            $siswa = Siswa::create($data);
            $siswa->orangTua()->create($this->validasiOrangTua($request));
            if ($siswa->kelas_id) {
                $siswa->riwayatKelas()->create([
                    'kelas_id' => $siswa->kelas_id,
                    'tahun_ajaran_id' => $siswa->tahun_ajaran_id,
                    'status_naik' => 'naik',
                ]);
            }
            return $siswa;
        });

        ActivityLog::catat('siswa.create', "Tambah siswa {$siswa->nama} (NIS {$siswa->nis})", $siswa);
        return redirect()->route('admin.siswa.show', $siswa)->with('status', 'Siswa berhasil ditambahkan.');
    }

    public function show(Siswa $siswa)
    {
        $siswa->load(['jurusan', 'kelas', 'tahunAjaran', 'orangTua', 'riwayatKelas.kelas', 'ledger', 'transkrip', 'dokumen']);
        return view('admin.siswa.show', compact('siswa'));
    }

    public function edit(Siswa $siswa)
    {
        $this->authorizeEdit();
        return view('admin.siswa.form', [
            'siswa' => $siswa->load('orangTua'),
            'tahunAjaran' => TahunAjaran::orderByDesc('nama')->get(),
            'jurusan' => Jurusan::orderBy('nama')->get(),
            'kelas' => Kelas::orderBy('nama')->get(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Siswa $siswa)
    {
        $this->authorizeEdit();
        $data = $this->validasi($request, $siswa->id);

        DB::transaction(function () use ($data, $request, $siswa) {
            $kelasLama = $siswa->kelas_id;
            $siswa->update($data);
            $siswa->orangTua()->updateOrCreate(['siswa_id' => $siswa->id], $this->validasiOrangTua($request));
            if ($siswa->kelas_id && $siswa->kelas_id !== $kelasLama) {
                $siswa->riwayatKelas()->create([
                    'kelas_id' => $siswa->kelas_id,
                    'tahun_ajaran_id' => $siswa->tahun_ajaran_id,
                    'status_naik' => 'naik',
                ]);
            }
        });

        ActivityLog::catat('siswa.update', "Ubah siswa {$siswa->nama} (NIS {$siswa->nis})", $siswa);
        return redirect()->route('admin.siswa.show', $siswa)->with('status', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Siswa $siswa)
    {
        $this->authorizeEdit();
        ActivityLog::catat('siswa.delete', "Hapus siswa {$siswa->nama} (NIS {$siswa->nis})", $siswa);
        $siswa->delete();
        return redirect()->route('admin.siswa.index')->with('status', 'Siswa berhasil dihapus.');
    }

    /** Export CSV — filter yang sedang aktif ikut terbawa. */
    public function export(Request $request)
    {
        $rows = Siswa::with(['jurusan', 'kelas', 'tahunAjaran'])
            ->cari($request->q)
            ->filter($request->only(['tahun_ajaran_id', 'jurusan_id', 'kelas_id', 'status']))
            ->orderBy('nama')->get();

        $namaFile = 'data-siswa-'.now()->format('Ymd-His').'.csv';
        $headers = ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => "attachment; filename=\"{$namaFile}\""];

        return response()->stream(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF"); // BOM supaya Excel baca UTF-8 dengan benar
            fputcsv($out, ['NIS', 'NISN', 'Nama', 'JK', 'Tempat Lahir', 'Tanggal Lahir', 'Agama', 'Alamat', 'No HP', 'Tahun Ajaran', 'Jurusan', 'Kelas', 'Status']);
            foreach ($rows as $s) {
                fputcsv($out, [
                    $s->nis, $s->nisn, $s->nama, $s->jenis_kelamin, $s->tempat_lahir,
                    $s->tanggal_lahir?->format('Y-m-d'), $s->agama, $s->alamat, $s->no_hp,
                    $s->tahunAjaran?->nama, $s->jurusan?->nama, $s->kelas?->nama, $s->status,
                ]);
            }
            fclose($out);
        }, 200, $headers);
    }

    public function importForm()
    {
        $this->authorizeEdit();
        return view('admin.siswa.import', [
            'tahunAjaran' => TahunAjaran::orderByDesc('nama')->get(),
            'jurusan' => Jurusan::orderBy('nama')->get(),
        ]);
    }

    /**
     * Import CSV. Baris gagal dilaporkan dan tidak menggagalkan baris lain.
     * Duplikat NIS ditolak — satu NIS hanya boleh satu record (single source of truth).
     */
    public function importStore(Request $request)
    {
        $this->authorizeEdit();
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'jurusan_id' => 'required|exists:jurusan,id',
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);
        $header = array_map(fn ($h) => strtolower(trim($h ?? '')), $header ?? []);

        $berhasil = 0;
        $gagal = [];
        $baris = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $baris++;
            if (count(array_filter($row)) === 0) continue;
            $d = array_combine($header, array_pad($row, count($header), null));
            $d = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $d);

            $alasan = [];
            if (empty($d['nis'])) $alasan[] = 'NIS kosong';
            if (! empty($d['nis']) && Siswa::where('nis', $d['nis'])->exists()) $alasan[] = 'NIS sudah ada';
            if (! empty($d['nisn']) && Siswa::where('nisn', $d['nisn'])->exists()) $alasan[] = 'NISN sudah ada';
            if (empty($d['nama'])) $alasan[] = 'Nama kosong';
            if (! empty($d['jenis_kelamin']) && ! in_array(strtoupper($d['jenis_kelamin']), ['L', 'P'])) $alasan[] = 'Jenis kelamin harus L/P';
            if (! empty($d['tanggal_lahir']) && ! strtotime($d['tanggal_lahir'])) $alasan[] = 'Tanggal lahir tidak valid';

            if ($alasan) {
                $gagal[] = "Baris {$baris} (NIS ".($d['nis'] ?? '-').'): '.implode('; ', $alasan);
                continue;
            }

            $kelas = null;
            if (! empty($d['kelas'])) {
                $kelas = Kelas::where('tahun_ajaran_id', $request->tahun_ajaran_id)
                    ->where('jurusan_id', $request->jurusan_id)
                    ->where('nama', $d['kelas'])->first();
            }

            $siswa = Siswa::create([
                'nis' => $d['nis'], 'nisn' => $d['nisn'] ?: null, 'nama' => $d['nama'],
                'jenis_kelamin' => strtoupper($d['jenis_kelamin'] ?? 'L'),
                'tempat_lahir' => $d['tempat_lahir'] ?? null,
                'tanggal_lahir' => ! empty($d['tanggal_lahir']) ? date('Y-m-d', strtotime($d['tanggal_lahir'])) : null,
                'agama' => $d['agama'] ?? null, 'alamat' => $d['alamat'] ?? null,
                'no_hp' => $d['no_hp'] ?? null,
                'tahun_ajaran_id' => $request->tahun_ajaran_id,
                'jurusan_id' => $request->jurusan_id,
                'kelas_id' => $kelas?->id,
                'status' => in_array($d['status'] ?? '', ['aktif', 'lulus', 'pindah', 'keluar']) ? $d['status'] : 'aktif',
                'tanggal_masuk' => TahunAjaran::find($request->tahun_ajaran_id)?->tanggal_mulai,
            ]);
            $siswa->orangTua()->create([
                'nama_ayah' => $d['nama_ayah'] ?? null,
                'nama_ibu' => $d['nama_ibu'] ?? null,
                'no_hp_ortu' => $d['no_hp_ortu'] ?? null,
            ]);
            $berhasil++;
        }
        fclose($handle);

        ActivityLog::catat('siswa.import', "Import CSV siswa: {$berhasil} berhasil, ".count($gagal).' gagal');
        return redirect()->route('admin.siswa.index')
            ->with('status', "Import selesai: {$berhasil} berhasil, ".count($gagal).' gagal.')
            ->with('import_gagal', $gagal);
    }

    /** Halaman cetak detail siswa (print-to-PDF via window.print). */
    public function cetak(Siswa $siswa)
    {
        $siswa->load(['jurusan', 'kelas', 'tahunAjaran', 'orangTua', 'ledger.tahunAjaran', 'transkrip']);

        // Ledger dikelompokkan per tingkat mengikuti HAL_PRINT file MASTER:
        // X = semester 1-2, XI = 3-4, XII = 5-6, beserta tahun diklatnya.
        $perSemester = $siswa->ledger->keyBy('semester');
        $tingkatData = [];
        foreach (['X' => [1, 2], 'XI' => [3, 4], 'XII' => [5, 6]] as $tingkat => $smts) {
            $tahun = null;
            foreach ($smts as $s) {
                if (($perSemester[$s] ?? null)?->tahunAjaran) {
                    $tahun = $perSemester[$s]->tahunAjaran->nama;
                    break;
                }
            }
            $tingkatData[$tingkat] = [
                'tahun' => $tahun,
                'smt1' => $perSemester[$smts[0]] ?? null,
                'smt2' => $perSemester[$smts[1]] ?? null,
            ];
        }

        return view('admin.siswa.cetak', [
            'siswa' => $siswa,
            'sekolah' => \App\Models\DataSekolah::get(),
            'tingkatData' => $tingkatData,
        ]);
    }

    private function authorizeEdit(): void
    {
        abort_unless(auth()->user()?->canEdit(), 403, 'Hanya Admin TU yang boleh mengubah data siswa.');
    }

    private function validasi(Request $request, ?int $kecualiId = null): array
    {
        return $request->validate([
            'nis' => ['required', 'string', 'max:20', Rule::unique('siswa', 'nis')->ignore($kecualiId)],
            'nisn' => ['nullable', 'string', 'max:20', Rule::unique('siswa', 'nisn')->ignore($kecualiId)],
            'nama' => 'required|string|max:150',
            'jenis_kelamin' => 'required|in:L,P',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'agama' => 'nullable|string|max:30',
            'alamat' => 'nullable|string',
            'no_hp' => 'nullable|string|max:20',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'jurusan_id' => 'required|exists:jurusan,id',
            'kelas_id' => 'nullable|exists:kelas,id',
            'status' => 'required|in:aktif,lulus,pindah,keluar',
            'tanggal_masuk' => 'nullable|date',
            'tanggal_keluar' => 'nullable|date',
            'keterangan' => 'nullable|string',
            'sekolah_asal' => 'nullable|string|max:150',
            'alamat_sekolah_asal' => 'nullable|string',
            'tahun_lulus' => 'nullable|string|max:9',
            'no_ijazah' => 'nullable|string|max:50',
            'diterima_tingkat' => 'nullable|string|max:30',
            'mutasi_ke' => 'nullable|string|max:100',
            'mutasi_dari' => 'nullable|string|max:100',
        ]);
    }

    private function validasiOrangTua(Request $request): array
    {
        $v = $request->validate([
            'nama_ayah' => 'nullable|string|max:150',
            'pekerjaan_ayah' => 'nullable|string|max:100',
            'nama_ibu' => 'nullable|string|max:150',
            'pekerjaan_ibu' => 'nullable|string|max:100',
            'nama_wali' => 'nullable|string|max:150',
            'pekerjaan_wali' => 'nullable|string|max:100',
            'no_hp_ortu' => 'nullable|string|max:20',
            'alamat_ortu' => 'nullable|string',
            'alamat_wali' => 'nullable|string',
            'no_hp_wali' => 'nullable|string|max:20',
        ]);
        return $v;
    }
}
