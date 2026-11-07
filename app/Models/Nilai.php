<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nilai extends Model
{
    protected $table = 'nilai';

    protected $fillable = [
        'siswa_id', 'mapel_id', 'tahun_ajaran_id', 'semester', 'kkm', 'nilai_angka',
    ];

    protected function casts(): array
    {
        return [
            'kkm' => 'decimal:2',
            'nilai_angka' => 'decimal:2',
            'semester' => 'integer',
        ];
    }

    public function getTuntasAttribute(): ?bool
    {
        if ($this->nilai_angka === null || $this->kkm === null) {
            return null;
        }

        return (float) $this->nilai_angka >= (float) $this->kkm;
    }

    public function siswa() { return $this->belongsTo(Siswa::class); }
    public function mapel() { return $this->belongsTo(Mapel::class); }
    public function tahunAjaran() { return $this->belongsTo(TahunAjaran::class); }
}
