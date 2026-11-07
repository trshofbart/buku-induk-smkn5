<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Kelengkapan per siswa per semester: kehadiran, sikap, catatan, kenaikan. */
class Kelengkapan extends Model
{
    protected $table = 'kelengkapan';

    protected $fillable = [
        'siswa_id', 'tahun_ajaran_id', 'semester',
        'sakit', 'izin', 'alpa',
        'kelakuan', 'kerapian', 'kerajinan', 'akhlak',
        'catatan', 'kenaikan',
    ];

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'sakit' => 'integer',
            'izin' => 'integer',
            'alpa' => 'integer',
        ];
    }

    /** Nilai sikap yang dipakai di cetakan (sesuai header HAL_PRINT). */
    public const SIKAP = ['Amat Baik', 'Baik', 'Cukup', 'Kurang'];

    public function siswa() { return $this->belongsTo(Siswa::class); }
    public function tahunAjaran() { return $this->belongsTo(TahunAjaran::class); }
}
