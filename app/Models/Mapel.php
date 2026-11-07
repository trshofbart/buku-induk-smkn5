<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mapel extends Model
{
    protected $table = 'mapel';

    protected $fillable = [
        'kode', 'nama', 'kelompok', 'jurusan_id', 'tingkat', 'urutan', 'kkm_default',
    ];

    protected function casts(): array
    {
        return ['kkm_default' => 'decimal:2'];
    }

    public const KELOMPOK = [
        'A' => 'A. Muatan Nasional',
        'B' => 'B. Muatan Kewilayahan',
        'C1' => 'C1. Dasar Bidang Keahlian',
        'C2' => 'C2. Dasar Program Keahlian',
        'C3' => 'C3. Kompetensi Keahlian',
    ];

    public function jurusan() { return $this->belongsTo(Jurusan::class); }
    public function nilai() { return $this->hasMany(Nilai::class); }

    public function kelompokLabel(): string
    {
        return static::KELOMPOK[$this->kelompok] ?? $this->kelompok;
    }

    /** Mapel yang berlaku untuk jurusan ini (khusus jurusan itu + yang umum). */
    public function scopeUntukJurusan($query, ?int $jurusanId)
    {
        return $query->where(fn ($q) => $q->whereNull('jurusan_id')->orWhere('jurusan_id', $jurusanId));
    }

    /**
     * Daftar mapel untuk satu jurusan + tingkat, per kelompok:
     * bila jurusan punya mapel sendiri di suatu kelompok (mis. C3-nya
     * sendiri), mapel itu MENGGANTIKAN mapel umum di kelompok tersebut;
     * bila belum punya, dipakai mapel umum. Jadi tiap jurusan bisa punya
     * C2/C3 sendiri tanpa mengubah mapel umum A/B.
     */
    public static function untukJurusanTingkat(?int $jurusanId, ?string $tingkat): \Illuminate\Support\Collection
    {
        $semua = static::untukTingkat($tingkat)->terurut()->get();
        $hasil = collect();
        foreach (['A', 'B', 'C1', 'C2', 'C3'] as $kel) {
            $khusus = $semua->where('kelompok', $kel)->where('jurusan_id', $jurusanId);
            $hasil = $hasil->merge(
                $khusus->isNotEmpty()
                    ? $khusus
                    : $semua->where('kelompok', $kel)->whereNull('jurusan_id')
            );
        }

        return $hasil->sortBy([['urutan', 'asc'], ['nama', 'asc']])->values();
    }

    /** Mapel yang berlaku untuk tingkat ini (khusus tingkat itu + yang umum). */
    public function scopeUntukTingkat($query, ?string $tingkat)
    {
        return $query->where(fn ($q) => $q->whereNull('tingkat')->orWhere('tingkat', $tingkat));
    }

    public function scopeTerurut($query)
    {
        return $query->orderBy('urutan')->orderBy('nama');
    }
}
