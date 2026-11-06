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

    /**
     * Skala predikat (huruf) sekolah, diturunkan dari data master sendiri
     * (sheet SMSTR 1-6 file MASTER 2021-2022, TEI + RPL A):
     * A = 92-100, B = 84-91, C = 76-83, D < 76 (C mulai tepat di KKM 76).
     */
    public static function predikat(?float $angka): ?string
    {
        if ($angka === null) {
            return null;
        }
        if ($angka >= 92) {
            return 'A';
        }
        if ($angka >= 84) {
            return 'B';
        }
        if ($angka >= 76) {
            return 'C';
        }

        return 'D';
    }

    public function getNilaiHurufAttribute(): ?string
    {
        return static::predikat($this->nilai_angka !== null ? (float) $this->nilai_angka : null);
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
