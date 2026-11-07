<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Satu kegiatan ekstrakurikuler seorang siswa dalam satu semester. */
class Ekstrakurikuler extends Model
{
    protected $table = 'ekstrakurikuler';

    protected $fillable = [
        'siswa_id', 'tahun_ajaran_id', 'semester',
        'kegiatan', 'nilai', 'keterangan',
    ];

    protected function casts(): array
    {
        return ['semester' => 'integer'];
    }

    public function siswa() { return $this->belongsTo(Siswa::class); }
    public function tahunAjaran() { return $this->belongsTo(TahunAjaran::class); }
}
