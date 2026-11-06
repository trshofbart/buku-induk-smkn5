<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatKelas extends Model
{
    protected $table = 'riwayat_kelas';

    protected $fillable = ['siswa_id', 'kelas_id', 'tahun_ajaran_id', 'status_naik', 'catatan'];

    public function siswa() { return $this->belongsTo(Siswa::class); }
    public function kelas() { return $this->belongsTo(Kelas::class); }
    public function tahunAjaran() { return $this->belongsTo(TahunAjaran::class); }
}
