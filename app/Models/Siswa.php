<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    use HasFactory;

    protected $table = 'siswa';

    public const STATUS_AKTIF = 'aktif';
    public const STATUS_LULUS = 'lulus';
    public const STATUS_PINDAH = 'pindah';
    public const STATUS_KELUAR = 'keluar';

    public static function statusList(): array
    {
        return [
            self::STATUS_AKTIF => 'Aktif',
            self::STATUS_LULUS => 'Lulus',
            self::STATUS_PINDAH => 'Pindah',
            self::STATUS_KELUAR => 'Keluar',
        ];
    }

    protected $fillable = [
        'nis', 'nisn', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir',
        'agama', 'alamat', 'no_hp', 'tahun_ajaran_id', 'jurusan_id', 'kelas_id',
        'status', 'tanggal_masuk', 'tanggal_keluar', 'keterangan', 'foto',
        'sekolah_asal', 'alamat_sekolah_asal', 'tahun_lulus', 'no_ijazah',
        'diterima_tingkat', 'mutasi_ke', 'mutasi_dari',
    ];

    protected function casts(): array
    {
        return ['tanggal_lahir' => 'date', 'tanggal_masuk' => 'date', 'tanggal_keluar' => 'date'];
    }

    // --- Relasi ---
    public function tahunAjaran() { return $this->belongsTo(TahunAjaran::class); }
    public function jurusan() { return $this->belongsTo(Jurusan::class); }
    public function kelas() { return $this->belongsTo(Kelas::class); }
    public function orangTua() { return $this->hasOne(OrangTua::class); }
    public function riwayatKelas() { return $this->hasMany(RiwayatKelas::class)->orderByDesc('created_at'); }
    public function ledger() { return $this->hasMany(Ledger::class)->orderBy('semester'); }
    public function transkrip() { return $this->hasMany(Transkrip::class); }
    public function dokumen() { return $this->hasMany(DokumenSiswa::class); }

    // --- Scope untuk pencarian & filter (dipakai controller) ---
    public function scopeCari($query, ?string $q)
    {
        if (! $q) return $query;
        return $query->where(function ($w) use ($q) {
            $w->where('nis', 'like', "%{$q}%")
              ->orWhere('nisn', 'like', "%{$q}%")
              ->orWhere('nama', 'like', "%{$q}%");
        });
    }

    public function scopeFilter($query, array $f)
    {
        return $query
            ->when($f['tahun_ajaran_id'] ?? null, fn ($q, $v) => $q->where('tahun_ajaran_id', $v))
            ->when($f['jurusan_id'] ?? null, fn ($q, $v) => $q->where('jurusan_id', $v))
            ->when($f['kelas_id'] ?? null, fn ($q, $v) => $q->where('kelas_id', $v))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v));
    }

    public function statusLabel(): string { return self::statusList()[$this->status] ?? $this->status; }
    public function jenisKelaminLabel(): string { return $this->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'; }
}
