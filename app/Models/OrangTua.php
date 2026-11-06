<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrangTua extends Model
{
    protected $table = 'orang_tua';

    protected $fillable = [
        'siswa_id', 'nama_ayah', 'pekerjaan_ayah', 'nama_ibu', 'pekerjaan_ibu',
        'nama_wali', 'pekerjaan_wali', 'no_hp_ortu', 'alamat_ortu',
    ];

    public function siswa() { return $this->belongsTo(Siswa::class); }
}
