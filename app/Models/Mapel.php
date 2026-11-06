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
