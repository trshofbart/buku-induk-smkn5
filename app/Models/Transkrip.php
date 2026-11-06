<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Pengganti file DATABASE TRANSKRIP. */
class Transkrip extends Model
{
    protected $table = 'transkrip';

    protected $fillable = ['siswa_id', 'tahun_ajaran_id', 'nomor_transkrip', 'tanggal_terbit', 'keterangan'];

    protected function casts(): array
    {
        return ['tanggal_terbit' => 'date'];
    }

    public function siswa() { return $this->belongsTo(Siswa::class); }
    public function tahunAjaran() { return $this->belongsTo(TahunAjaran::class); }
}
